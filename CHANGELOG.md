# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.2.1] - 2023-05-25
### Fixed
* If a schema.org object has a non string @type, it is ignored and a warning is logged, if the class has a logger.

## [0.2.0] - 2023-05-17
### Added
* You can now optionally pass a PSR-3 LoggerInterface to the `SchemaOrg` class, so it'll log decoding errors.

### Fixed
* The `Json` class from the crwlr/utils is now used to decode JSON strings. It tries to fix keys without quotes, which is allowed in relaxed JSON. Further, JSON-LD <script> blocks containing an invalid JSON string are ignored and don't lead to an error anymore.

## [0.1.0] - 2022-09-22
Initial version containing `SchemaOrg` class that finds schema.org JSON-LD objects in HTML documents and converts them to instances of the classes from the spatie schema-org package.
