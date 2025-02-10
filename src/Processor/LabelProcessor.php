<?php declare(strict_types=1);

namespace DennisWiemann\LokiHandler\Processor;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class LabelProcessor implements ProcessorInterface
{
    public function __construct(
        private string|null $serviceName = null)
    {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $labels = [
            'level' => $record->level->getName(),
            'channel' => $record->channel
        ];
        if ($this->serviceName !== null) {
            $labels['service_name'] = $this->serviceName;
        }
        $record->extra['labels'] = $labels;

        return $record;
    }
}
