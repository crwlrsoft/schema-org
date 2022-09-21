<?php

namespace Crwlr\SchemaOrg;

use InvalidArgumentException;
use Spatie\SchemaOrg\BaseType;
use Symfony\Component\DomCrawler\Crawler;

class SchemaOrg
{
    private TypeList $types;

    private static ?self $singletonInstance = null;

    public function __construct()
    {
        $this->types = new TypeList();
    }

    /**
     * @return BaseType[]
     */
    public static function fromHtml(string $html): array
    {
        if (!self::$singletonInstance) {
            self::$singletonInstance = new self();
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

            if ($schemaOrgObject) {
                $schemaOrgObjects[] = $schemaOrgObject;
            }
        }

        return $schemaOrgObjects;
    }

    private function getSchemaOrgObjectFromScriptBlock(Crawler $domCrawler): ?BaseType
    {
        return $this->convertJsonDataToSchemaOrgObject(json_decode($domCrawler->text(), true));
    }

    /**
     * @param mixed[] $json
     */
    private function convertJsonDataToSchemaOrgObject(array $json, bool $isChild = false): ?BaseType
    {
        if (!$isChild && !$this->isSchemaOrgJsonLdData($json)) {
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
