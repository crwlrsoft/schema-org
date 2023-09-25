<?php

namespace Crwlr\SchemaOrg;

use Crwlr\Utils\Exceptions\InvalidJsonException;
use Crwlr\Utils\Json;
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
            $schemaOrgObjects = array_merge(
                $schemaOrgObjects,
                $this->getSchemaOrgObjectsFromScriptBlock(new Crawler($jsonLdScriptBlockElement)),
            );
        }

        return $schemaOrgObjects;
    }

    /**
     * @param Crawler $domCrawler
     * @return BaseType[]
     */
    private function getSchemaOrgObjectsFromScriptBlock(Crawler $domCrawler): array
    {
        $jsonData = $this->scriptBlockToDataArray($domCrawler);

        if (!$jsonData->isSchemaOrgJsonLdData()) {
            return [];
        } elseif ($jsonData->hasGraphKey()) {
            return $this->getSchemaOrgObjectsFromGraph($jsonData);
        }

        $schemaOrgObject = $this->convertJsonDataToSchemaOrgObject($jsonData);

        return $schemaOrgObject ? [$schemaOrgObject] : [];
    }

    private function scriptBlockToDataArray(Crawler $scriptBlockElement): DataArray
    {
        try {
            return DataArray::make(Json::stringToArray($scriptBlockElement->text()));
        } catch (InvalidJsonException) {
            $snippetWithReducedSpaces = preg_replace('/\s+/', ' ', $scriptBlockElement->text());

            if ($snippetWithReducedSpaces === null) {
                $snippetWithReducedSpaces = $scriptBlockElement->text();
            }

            $this->logger?->warning(
                'Failed to parse content of JSON-LD script block as JSON: ' . substr($snippetWithReducedSpaces, 0, 100)
            );

            return DataArray::make([]);
        }
    }

    /**
     * @param DataArray $jsonData
     * @return BaseType[]
     */
    private function getSchemaOrgObjectsFromGraph(DataArray $jsonData): array
    {
        $schemaOrgObjects = [];

        foreach ($jsonData->getGraph() as $graphDataItem) {
            $schemaOrgObject = $this->convertJsonDataToSchemaOrgObject($graphDataItem);

            if ($schemaOrgObject) {
                $schemaOrgObjects[] = $schemaOrgObject;
            }
        }

        return $schemaOrgObjects;
    }

    private function convertJsonDataToSchemaOrgObject(DataArray $json): ?BaseType
    {
        if (!$json->hasTypeKey()) {
            return null;
        }

        $type = $json->getType();

        if (!is_string($type)) {
            $this->logger?->warning('Can\'t convert schema.org object with non-string type.');

            return null;
        }

        $className = $this->types->getClassName($type);

        if (!$className) {
            return null;
        }

        return $this->createObjectFromData($json, $className);
    }

    private function createObjectFromData(DataArray $json, string $className): ?BaseType
    {
        $object = $this->createObjectFromClassName($className);

        if ($object) {
            foreach ($json as $key => $value) {
                if ($value instanceof DataArray && $value->hasTypeKey()) {
                    $value = $this->convertJsonDataToSchemaOrgObject($value);
                } elseif ($value instanceof DataArray) {
                    foreach ($value as $k => $v) {
                        if ($v instanceof DataArray && $v->hasTypeKey()) {
                            $value->set($k, $this->convertJsonDataToSchemaOrgObject($v));
                        }
                    }

                    $value = $value->toArray(true);
                }

                $object->setProperty((string) $key, $value);
            }
        }

        return $object;
    }

    private function createObjectFromClassName(string $className): ?BaseType
    {
        if (!class_exists($className)) {
            $this->logger?->warning(
                'Something is wrong, the class ' . $className . ' does not exist.'
            );
        }

        $object = new $className();

        if (!$object instanceof BaseType) {
            $this->logger?->warning(
                'Something is wrong, the class ' . $className . ' is not an instance of the spatie BaseType class.'
            );

            return null;
        }

        return $object;
    }
}
