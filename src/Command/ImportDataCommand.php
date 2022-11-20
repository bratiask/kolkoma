<?php declare(strict_types=1);

namespace App\Command;

use App\Entity\Measurement;
use App\Repository\MeasurementRepository;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Generator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(name: 'app:import:data')]
class ImportDataCommand extends Command
{
    private DateTimeZone $bratislavaTimezone;

    function __construct(
        private readonly HttpClientInterface    $httpClient,
        private readonly EntityManagerInterface $entityManager,
        private readonly MeasurementRepository  $measurementRepository,
        private readonly ParameterBagInterface  $parameterBag
    )
    {
        $this->bratislavaTimezone = new DateTimeZone('Europe/Bratislava');

        parent::__construct();
    }

    protected function execute(
        InputInterface  $input,
        OutputInterface $output
    ): int
    {
        $measurements = [];

        do {
            $count = 0;
            $lastMeasuredAt = $this->measurementRepository->lastMeasuredAt(Measurement::LOCATION_BA_ZP);
            $records = $this->getRecords($lastMeasuredAt);
            $output->write(sprintf('Retrieving data from InfluxDB (starting at %s)... ', $lastMeasuredAt->format('Y-m-d H:i:s')));

            foreach ($records as $record) {
                $count++;

                if (null === $record) {
                    continue;
                }

                $key = sprintf("%s-%s", Measurement::LOCATION_BA_ZP, $record['time']->format(DateTimeInterface::RFC3339));

                $measurement = ($measurements[$key] ?? new Measurement())
                    ->setLocation(Measurement::LOCATION_BA_ZP)
                    ->setMeasuredAt($record['time'])
                    ->setValue($record['value']);

                $measurements[$key] = $measurement;

                $this->entityManager->persist($measurement);
            }

            $output->writeln(sprintf('Done. %d record(s)', $count));
            sleep(1);

            $this->entityManager->flush();
        } while ($count > 0);

        return self::SUCCESS;
    }

    /**
     * @return Generator<null | array{"time": DateTimeImmutable, "value": int}>
     */
    private function getRecords(DateTimeImmutable $lastMeasuredAt): Generator
    {
        $response = $this->httpClient->request('POST', 'http://zlatepiesky.blava.net:8086/api/v2/query?orgID=e247631a214b4866', [
            'headers' => [
                'Content-Type' => 'application/vnd.flux',
                'Authorization' => sprintf('Token %s', $this->parameterBag->get('influx_auth_token')),
                'Accept' => 'application/csv',
            ],
            'body' => sprintf('
                        from(bucket:"my-bucket")
                            |> range(start:%s)
                            |> filter(fn: (r) => r.location == "zp" and r._field == "last" and r.type == "temperature")
                            |> group()
                            |> sort(columns: ["_time"], desc: false)
                            |> limit(n:100)', $lastMeasuredAt->format(DateTimeInterface::RFC3339)),
        ]);

        if (200 !== $response->getStatusCode()) {
            $message = (json_decode($response->getContent(false), true) ?? ['message' => ''])['message'];

            if (str_contains($message, 'cannot query an empty range')) {
                return;
            }
        }

        $twoSeconds = new DateInterval('PT2S');

        foreach ((new CsvEncoder)->decode(trim($response->getContent(false)), 'csv') as $record) {

            $value = (float)substr($record['_value'], 0, 5);

            if ($value < -1 || $value > 40) {
                yield null;
            }

            yield [
                "time" => (new DateTimeImmutable($record['_time']))->add($twoSeconds)->setTimezone($this->bratislavaTimezone),
                "value" => (int)round($value * 1000),
            ];
        }
    }
}
