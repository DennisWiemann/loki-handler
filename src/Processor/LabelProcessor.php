<?php declare(strict_types=1);

namespace DennisWiemann\LokiHandler\Processor;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class LabelProcessor implements ProcessorInterface
{

    public function __construct(
        private ParameterBagInterface $params)
    {
    }
    /**
     * {@inheritDoc}
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        if($this->params->has("monolog.labelprocessor.service_name")) {
            $record->extra['labels']['service_name'] = $this->params->get('monolog.labelprocessor.service_name');
        }
        $record->extra['labels']["level"] = $record->level->getName();
        $record->extra['labels']["channel"] = $record->channel;
        return $record;
    }
}