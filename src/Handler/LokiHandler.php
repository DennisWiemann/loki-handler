<?php

declare(strict_types=1);

namespace DennisWiemann\LokiHandler\Handler;

use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\AbstractHandler;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\Handler\MissingExtensionException;
use Monolog\Level;
use Monolog\LogRecord;
use RuntimeException;

class LokiHandler extends AbstractHandler implements FormattableHandlerInterface
{
    private FormatterInterface $formatter;

    public function setFormatter(FormatterInterface $formatter): self
    {
        $this->formatter = $formatter;

        return $this;
    }

    public function getFormatter(): FormatterInterface
    {
        return $this->formatter ?? $this->getDefaultFormatter();
    }

    public function __construct(
        private string $lokiUrl,
        int|string|Level $level = Level::Debug,
        bool $bubble = true
    ) {
        if (!\extension_loaded('curl')) {
            throw new MissingExtensionException('The curl extension is needed to use the LokiHandler');
        }
        parent::__construct($level, $bubble);
    }

    public function handle(LogRecord $record): bool
    {
        if (!$this->isHandling($record)) {
            return false;
        }

        $this->write($record);

        return true; // todo: check if we need to return false
    }

    public function write(LogRecord $record): void
    {
        /** @var string $message */
        $message = $message = $this->getFormatter()->format($record);
        /** @var array<string, string> $labels */
        $labels = $record->extra['labels'] ?? [];
        $timestamp = $record->datetime->getTimestamp() * 1000000000;
        $this->send($timestamp, $message, $labels);
    }

    /**
     * @param array<string,string> $labels
     */
    private function send(int $timestamp, string $message, array $labels): void
    {
        $curlSession = curl_init();
        $url = $this->lokiUrl . '/loki/api/v1/push';
        $data = [
            'streams' => [
                [
                    'stream' => $labels,
                    'values' => [
                        [(string) $timestamp, $message]
                    ]
                ]
            ]
        ];
        // @var string $dataString
        $dataString = json_encode($data, JSON_THROW_ON_ERROR);

        curl_setopt($curlSession, CURLOPT_URL, $url);
        curl_setopt($curlSession, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curlSession, CURLOPT_POSTFIELDS, $dataString);
        curl_setopt($curlSession, CURLOPT_POST, true);

        curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $curlSession,
            CURLOPT_HTTPHEADER,
            [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($dataString)
            ]
        );

        $result = curl_exec($curlSession);

        $retry = 0;
        while (curl_errno($curlSession) == 28 && $retry < 3) {
            $result = curl_exec($curlSession);
            $retry++;
        }

        if (!\is_string($result)) {
            throw new RuntimeException('Loki API error. Description: No response');
        }
        $result = json_decode($result, true);

        if (is_scalar($result) && $result !== '') {
            throw new RuntimeException('Loki API error. Description: ' . (string) $result);
        }
    }

    protected function getDefaultFormatter(): FormatterInterface
    {
        return new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, false);
    }
}
