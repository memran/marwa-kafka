# Repository Guidelines

## Project Structure & Module Organization
This repository is a small Composer library for Kafka messaging. Core code lives in `src/`, split by responsibility: `src/Producer/`, `src/Consumer/`, `src/Contracts/`, and `src/Support/`. Example entry points for local manual checks live in `example/Producer.php` and `example/Consumer.php`. Runtime infrastructure for development is defined in `docker-compose.yml`.

PSR-4 autoloading maps `Marwa\\Kafka\\` to `src/`. `composer.json` also reserves `Marwa\\Kafka\\Tests\\` for `tests/`, but that directory is not currently committed.

## Build, Test, and Development Commands
Use Composer for dependency and test workflows:

- `composer install`: install PHP dependencies.
- `composer test`: run PHPUnit through the Composer script.
- `vendor/bin/phpunit`: run the test suite directly.
- `docker compose up -d --build`: start Kafka, PHP, and Kafka UI for local development.
- `docker compose exec php bash`: open a shell in the PHP container.

When validating examples, run them from the container so `ext-rdkafka` and Kafka are available.

## Coding Style & Naming Conventions
Follow the existing PHP style in `src/`: strict typing where practical, `final` classes for concrete services, typed properties, and PSR-4 namespaces that mirror the directory layout. Use 4-space indentation and one class per file.

Class names use `StudlyCase` (`KafkaProducer`, `KafkaConfig`), methods and properties use `camelCase`, and interfaces end with `Interface`. Keep public APIs small and explicit; shared configuration belongs in `src/Support/`.

## Testing Guidelines
PHPUnit 10 is configured in `composer.json`. Add new tests under `tests/` with names ending in `Test.php`, mirroring the production namespace structure where possible. Cover producer and consumer behavior, especially envelope validation, Kafka config handling, and commit/poll edge cases.

If you add tests that depend on Kafka, document whether they are integration tests and how to run them with Docker.

## Commit & Pull Request Guidelines
Recent history uses short, imperative commit messages such as `Fix Issues`, `Refactor code`, and `add listen function`. Prefer clearer versions of that style, for example: `Add consumer listen alias` or `Refactor producer topic caching`.

Pull requests should include a short summary, testing notes (`composer test`, manual Docker validation), and any config or API changes. Include example usage updates when public behavior changes.
