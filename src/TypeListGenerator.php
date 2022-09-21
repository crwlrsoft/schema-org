<?php

namespace Crwlr\SchemaOrg;

use Spatie\SchemaOrg\BaseType;

/**
 * Generates the TypeList class from all the classes in the spatie package.
 */

class TypeListGenerator
{
    public function generate(): void
    {
        $typeListClass = <<<TYPELISTCLASS
            <?php
            
            namespace Crwlr\SchemaOrg;
            
            class TypeList
            {
                /**
                 * @var array<string, string>
                 */
                private array \$types = [
            
            TYPELISTCLASS;

        foreach ($this->getTypes() as $type => $className) {
            $typeListClass .= '        \'' . $type . '\' => \'' . $className . '\',' . PHP_EOL;
        }

        $typeListClass .= <<<TYPELISTCLASS
                ];
            
                public function getClassName(string \$type): ?string
                {
                    return \$this->types[\$type] ?? null;
                }
            }
            
            TYPELISTCLASS;

        file_put_contents(__DIR__ . '/TypeList.php', $typeListClass);
    }

    /**
     * @return array<string, string>
     */
    private function getTypes(): array
    {
        $types = [];

        $files = scandir(__DIR__ . '/../vendor/spatie/schema-org/src');

        if (is_array($files)) {
            foreach ($files as $filename) {
                if ($filename === '.' || $filename === '..') {
                    continue;
                }

                $className = "Spatie\\SchemaOrg\\" .substr($filename, 0, -4);

                if (
                    class_exists($className) &&
                    $className !== "Spatie\\SchemaOrg\\BaseType" &&
                    method_exists($className, 'getType')
                ) {
                    $class = new $className();

                    if (!$class instanceof BaseType) {
                        continue;
                    }

                    $types[$class->getType()] = $className;
                }
            }
        }

        return $types;
    }
}
