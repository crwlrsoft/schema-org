# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.3.4] - 2025-04-08
### Fixed
* An error when a `@graph` property contains only a single object instead of an array of objects. See issue: https://github.com/crwlrsoft/schema-org/issues/7

## [0.3.3] - 2025-01-10
### Fixed
* Enable reading multiple schema.org objects from a JSON-LD script block containing an array of schema.org objects.

## [0.3.2] - 2024-11-06
### Fixed
* Updated types list from spatie/schema-org v3.23.

## [0.3.1] - 2023-11-30
### Fixed
* Support usage with the new Symfony major version v7.

## [0.3.0] - 2023-09-25
### Added
* Objects in an array with key `@graph` like in this example https://schema.org/Article#eg-0399, can now also be parsed. As well as child objects in an array (see the test case with the graph notation).

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
