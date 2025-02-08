<?php

declare(strict_types=1);

namespace DennisWiemann\LokiHandler\Handler;

use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\AbstractHandler;
use Monolog\Handler\Curl;
use Monolog\Handler\MissingExtensionException;
use Monolog\LogRecord;
use RuntimeException;

class LokiHandler extends AbstractHandler
{

    public function __construct( private  string $lokiUrl)
    {
        if (!\extension_loaded('curl')) {
            throw new MissingExtensionException('The curl extension is needed to use the LokiHandler');
        }
    }


    /**
     * @inheritDoc
     */
    public function handle(LogRecord $record): bool
    {
        if (!$this->isHandling($record)) {
            return false;
        }

        $this->write($record);

        return true; // todo: check if we need to return false
    }

    function write(LogRecord $record): void
    {
        $message = $this->getDefaultFormatter()->format($record); // TODO: json format
        $labels = $record->extra['labels'] ?? [];
        $timestamp = $record->datetime->getTimestamp() * 1000000000;
        $this->send($timestamp, $message, $labels);
    }

    private function send(int $timestamp, string $message, array $labels)
    {
        $ch = curl_init();
        $url = $this->lokiUrl . '/loki/api/v1/push';
        $data = array(
            'streams' => [
                [
                    'stream' => $labels,
                    'values' => [
                        [(string) $timestamp, $message]
                    ]
                ]
            ]
        );
        $data_string = json_encode($data);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string)
            )
        );
        $result = Curl\Util::execute($ch);

        if (!\is_string($result)) {
            throw new RuntimeException('Loki API error. Description: No response');
        }
        $result = json_decode($result, true);

        if ($result !== "" && $result !== null) {
            throw new RuntimeException('Loki API error. Description: ' . $result);
        }
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultFormatter(): FormatterInterface
    {
        return new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, false);
    }
}
