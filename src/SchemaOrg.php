<?php

namespace Crwlr\SchemaOrg;

use Crwlr\Utils\Exceptions\InvalidJsonException;
use Crwlr\Utils\Json;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Spatie\SchemaOrg\BaseType;
use Symfony\Component\DomCrawler\Crawler;

class SchemaOrg
{
    private TypeList $types;

    private static ?self $singletonInstance = null;

    public function __construct(protected ?LoggerInterface $logger = null)
    {
        $this->types = new TypeList();
    }

    /**
     * @return BaseType[]
     */
    public static function fromHtml(string $html, ?LoggerInterface $logger = null): array
    {
        if (!self::$singletonInstance) {
            self::$singletonInstance = new self($logger);
        } elseif ($logger) {
            self::$singletonInstance->logger = $logger;
        }

        return self::$singletonInstance->getFromHtml($html);
    }

    /**
     * @param string $html
     * @return BaseType[]
     */
    public function getFromHtml(string $html): array
    {
        $jsonLdScriptBlocks = (new Crawler($html))->filterXPath(
            'descendant-or-self::script[@type = \'application/ld+json\']'
        );

        $schemaOrgObjects = [];

        foreach ($jsonLdScriptBlocks as $jsonLdScriptBlockElement) {
            $schemaOrgObject = $this->getSchemaOrgObjectFromScriptBlock(new Crawler($jsonLdScriptBlockElement));

            if (! $schemaOrgObject) {
                continue;
            }

            if (is_array($schemaOrgObject)) {
                $schemaOrgObjects = array_merge($schemaOrgObjects, $schemaOrgObject);
            } else {
                $schemaOrgObjects[] = $schemaOrgObject;
            }
        }

        return $schemaOrgObjects;
    }

    private function getSchemaOrgObjectFromScriptBlock(Crawler $domCrawler): BaseType|array|null
    {
        try {
            $jsonData = Json::stringToArray($domCrawler->text());
        } catch (InvalidJsonException) {
            $snippetWithReducedSpaces = preg_replace('/\s+/', ' ', $domCrawler->text()) ?? $domCrawler->text();

            $this->logger?->warning(
                'Failed to parse content of JSON-LD script block as JSON: ' . substr($snippetWithReducedSpaces, 0, 100)
            );

            return null;
        }

        if (! isset($jsonData['@graph'])) {
            return $this->convertJsonDataToSchemaOrgObject($jsonData);
        }

        $graphData = $jsonData['@graph'];

        $schemaOrgObjects = [];

        foreach ($graphData as $graphDataItem) {
            $schemaOrgObject = $this->convertJsonDataToSchemaOrgObject($graphDataItem, true);

            if ($schemaOrgObject) {
                $schemaOrgObjects[] = $schemaOrgObject;
            }
        }

        return $schemaOrgObjects;
    }

    /**
     * @param mixed[] $json
     */
    private function convertJsonDataToSchemaOrgObject(array $json, bool $isChild = false): ?BaseType
    {
        if (!$isChild && !$this->isSchemaOrgJsonLdData($json)) {
            return null;
        }

        if (!is_string($json['@type'])) {
            $this->logger?->warning('Can\'t convert schema.org object with non-string type.');

            return null;
        }

        $className = $this->types->getClassName($json['@type']);

        if (!$className) {
            return null;
        }

        return $this->createObjectFromJson($json, $className);
    }

    /**
     * @param mixed[] $json
     */
    private function createObjectFromJson(array $json, string $className): BaseType
    {
        $object = new $className();

        if (!$object instanceof BaseType) {
            throw new InvalidArgumentException('Class ' . $className . ' is not a child of the BaseType class.');
        }

        foreach ($json as $key => $value) {

            if (is_array($value) && isset($value['@type'])) {
                $value = $this->convertJsonDataToSchemaOrgObject($value, true);
            } elseif (is_array($value)) {
                foreach ($value as $k => $v) {
                    if (is_array($v) && isset($v['@type'])) {
                        $value[$k] = $this->convertJsonDataToSchemaOrgObject($v, true);
                    }
                }
            }

            $object->setProperty($key, $value);
        }

        return $object;
    }

    /**
     * @param mixed[] $data
     */
    private function isSchemaOrgJsonLdData(array $data): bool
    {
        return isset($data['@context']) &&
            isset($data['@type']) &&
            str_contains($data['@context'], 'schema.org');
    }
}
