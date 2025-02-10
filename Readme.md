# LokiHandler for Monolog

## Installation
```bash
composer config repositories.lokihandler vcs git@github.com:DennisWiemann/loki-handler.git
composer require denniswiemann/loki-handler
```
## Usage
### Using the LokiHandler

```yaml
monolog:
    handlers:
        main:
            type:         fingers_crossed
            action_level: info
            handler:      loki
            formatter:  monolog.formatter.json
        deduplicated:
            type:    deduplication
            handler: loki
        loki:
            type: service
            id: DennisWiemann\LokiHandler\Handler\LokiHandler
```
## Usage
### Using the LabelProcessor
```yaml
services:
    DennisWiemann\LokiHandler\Handler\LokiHandler: 
        arguments:
            $lokiUrl: 'http://grafana-loki:3100'
            $level: 'error'
    
    DennisWiemann\LokiHandler\Processor\LabelProcessor: 
        arguments:
            $serviceName: 'pimcore-loki-handler-1'
        tags:
            - { name: monolog.processor }

```
## Open
- [x] Quality Tools
- [ ] CI
- [ ] Unit Tests