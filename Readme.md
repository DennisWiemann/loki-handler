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
            id: DennisWiemann\Handler\LokiHandler
```
## Usage
### Using the LabelProcessor
```yaml
services:
    DennisWiemann\Processor\LabelProcessor: 
        tags:
            - { name: monolog.processor }
```
## Open
- [x] Quality Tools
- [ ] CI
- [ ] Unit Tests