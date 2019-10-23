<?php
/**
 * @category    pimcore5-graphQl
 * @date        14/02/2019 08:48
 * @author      Kamil Janik <kjanik@divante.co>
 */

namespace Divante\GraphQlBundle\Builder;

use Divante\GraphQlBundle\DataManagement;
use Divante\GraphQlBundle\TypeFactory\Basic;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\UnionType;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Tool;
use Symfony\Component\DependencyInjection\Tests\Compiler\PriorityTaggedServiceTraitImplementation;

/**
 * Class Query
 */
class Query
{
    /**
     * @var \stdClass
     */
    protected $typeList;
    /**
     * @var DataManagement\Query\Basic
     */
    protected $dataProvider;
    /**
     * @var Basic
     */
    protected $fieldFactory;

    /**
     * @param Basic $fieldFactory
     * @required
     */
    public function setFieldFactory(Basic $fieldFactory)
    {
        $this->fieldFactory = $fieldFactory;
    }

    /**
     * @param DataManagement\Query\Basic $dataProvider
     * @required
     */
    public function setDataProvider(DataManagement\Query\Basic $dataProvider)
    {
        $this->dataProvider = $dataProvider;
    }

    /**
     * @return DataManagement\Query\Basic
     */
    public function getDataProvider(): DataManagement\Query\Basic
    {
        return $this->dataProvider;
    }

    /**
     * TypeFactory constructor.
     */
    public function __construct()
    {
        $this->typeList = new \stdClass();
        $this->typeList->arithmeticFilter =  new InputObjectType([
            'name' => 'arithmeticFilter',
            'fields' => [
                'eq' => [
                    'type' => Type::float(),
                ],
                'neq' => [
                    'type' => Type::float(),
                ],
                'gt' => [
                    'type' => Type::float(),
                ],
                'gte' => [
                    'type' => Type::float(),
                ],
                'lt' => [
                    'type' => Type::float(),
                ],
                'lte' => [
                    'type' => Type::float(),
                ]
            ]
        ]);
    }

    /**
     * @return ObjectType
     */
    public function getSchema()
    {
        return new ObjectType([
            'name' => 'Query',
            'fields' => array_merge(
                array_reduce(
                    $this->dataProvider->getClassList(),
                    function ($carry, $item) {
                        $carry[$item] = [
                            'type' => Type::listOf($this->getConfig($item)),
                            'args' => array_merge($this->getFilters($item), [
                                "id" => Type::int(),
                                'language' => [
                                    'type' => Type::string(),
                                    'defaultValue' => Tool::getDefaultLanguage()
                                ],
                                'o_published' => [
                                    'type' => Type::boolean(),
                                    'defaultValue' => true
                                ],
                                'limit' => [
                                    'type' => Type::int()
                                ],
                                'offset' => [
                                    'type' => Type::int()
                                ],
                                'o_modificationDate' => [
                                    'type' => $this->typeList->arithmeticFilter
                                ],
                                'o_creationDate' => [
                                    'type' => $this->typeList->arithmeticFilter
                                ]
                            ])
                        ];
                        return $carry;
                    }
                ),
                []
            ),
            'resolveField' => function ($val, $args, $context, ResolveInfo $info) {
                $this->dataProvider->setCurrentLanguage($args["language"]);
                return $this->dataProvider->getDataObject($info->fieldName, $args);
            }
        ]);
    }

    /**
     * @param string $className
     * @return array
     */
    protected function getFilters(string $className)
    {
        $result = [];
        $definition = ClassDefinition::getByName($className);
        if ($definition instanceof ClassDefinition) {
            $layouts = $definition->getLayoutDefinitions();
            if ($layouts && !is_array($layouts)) {
                $layouts = [$layouts];
            }
            $collection = array_reduce(
                $layouts ?? [],
                function ($carry, $item) {
                    $carry = array_merge($carry, $this->getFieldDefinitionsRecursive($item));
                    return $carry;
                }, []
            );

            foreach ($collection as $item) {
                $result[$item->getName()] = $this->fieldFactory->getFilers($item);
            }
        }

        return $result;
    }

    /**
     * @param $definitions
     * @return mixed
     */
    protected function getFieldDefinitionsRecursive($definitions)
    {
        return array_reduce(
            $definitions->getChilds(),
            function ($carry, $item) {
                if ($item instanceof ClassDefinition\Layout || $item instanceof ClassDefinition\Data\Localizedfields) {
                    $carry = array_merge($carry, $this->getFieldDefinitionsRecursive($item));
                } elseif ($item instanceof ClassDefinition\Data) {
                    $carry = array_merge($carry, [$item]);
                }
                return $carry;
            }, []
        );
    }

    /**
     * @param string $className
     * @return mixed
     */
    protected function getConfig(string $className)
    {
        $definition = ClassDefinition::getByName($className);
        $this->typeList->$className = true;
        if ($definition instanceof ClassDefinition) {
            $this->typeList->$className = new ObjectType([
                'name' => $className,
                'fields' => function () use ($className) {
                    return $this->getFieldsDefinition($className);
                },
                'resolveField' => function ($value, $args, $context, ResolveInfo $info) {
                    return $this->dataProvider->getResolveFunction($value, $info->fieldName, $args);
                }
            ]);
            return $this->typeList->$className;
        } else {
            return Type::int();
        }
    }


    /**
     * @param string $className
     * @return array
     */
    protected function getFieldsDefinition(string $className)
    {
        $definition = ClassDefinition::getByName($className);
        if ($definition instanceof ClassDefinition) {
            $layouts = $definition->getLayoutDefinitions();
            if ($layouts && !is_array($layouts)) {
                $layouts = [$layouts];
            }
            $collection = array_reduce(
                $layouts ?? [],
                function ($carry, $item) {
                    $carry = array_merge($carry, $this->getFieldDefinitionsRecursive($item));
                    return $carry;
                }, []
            );
            $def = [];
            $this->addSystemFields($def);
            foreach ($collection as $item) {
                $def[$item->getName()] = $this->getFieldType($item);
            }
        }

        return $def;
    }

    /**
     * @param array $def
     */
    protected function addSystemFields(array &$def)
    {
        $def["id"] = Type::int();
        $def["key"] = Type::string();
        $def["o_modificationDate"] = $this->typeList->modificationDate = $this->typeList->modificationDate ?? new ObjectType([
                'name' => "o_modificationDate",
                'fields' => [
                    'timestamp' => [
                        'type' => Type::float(),
                        'resolve' => function ($val) {
                            return $val;
                        }
                    ],'dateTime' => [
                        'type' => Type::string(),
                        'resolve' => function ($val) {
                            return  gmdate("Y-m-d\TH:i:s\Z", $val);
                        }
                    ],
                ]
            ]);
        $def["o_creationDate"] = $this->typeList->creationDate = $this->typeList->creationDate ?? new ObjectType([
                'name' => "o_creationDate",
                'fields' => [
                    'timestamp' => [
                        'type' => Type::float(),
                        'resolve' => function ($val) {
                            return $val;
                        }
                    ],'dateTime' => [
                        'type' => Type::string(),
                        'resolve' => function ($val) {
                            return  gmdate("Y-m-d\TH:i:s\Z", $val);
                        }
                    ],
                ]
            ]);
        $def['o_published'] = Type::int();
    }

    /**
     * @param $item
     * @param array $def
     */
    public function parseLocalizedfields($item, &$def)
    {
        foreach ($item->getChilds() as $child) {
            if (!$child instanceof ClassDefinition\Data) {
                $this->parseLocalizedfields($child, $def);
            } else {
                $def[$child->getName()] = $this->getLocalizedFieldType($child);
            }
        }
    }

    /**
     * @param ClassDefinition\Data $fieldDefinition
     * @return array|\GraphQL\Type\Definition\IntType|\GraphQL\Type\Definition\ListOfType|mixed
     */
    public function getFieldType(ClassDefinition\Data $fieldDefinition)
    {
        if ($this->fieldFactory->isReferenceType($fieldDefinition)) {
            return $this->getReferencedType($fieldDefinition);
        } else {
            return $this->fieldFactory->getSimpleType($fieldDefinition);
        }
    }

    /**
     * @param ClassDefinition\Data $fieldDefinition
     * @return array|\GraphQL\Type\Definition\ListOfType|mixed
     */
    protected function getReferencedType(ClassDefinition\Data $fieldDefinition)
    {
        if ($this->fieldFactory->isUnionType($fieldDefinition)) {
            //todo unions
            return Type::int();
        } else {
            $className = $this->fieldFactory->getClassName($fieldDefinition);
            if (!$className) {
                //todo Assets, Documents
                return Type::int();
            }
            if ($this->typeList->$className) {
                $type = $this->typeList->$className;
            } else {
                $type = $this->getConfig($className);
            }
        }

        if ($this->fieldFactory->isCollectionReferenceType($fieldDefinition)) {
            return [
                'type' => Type::listOf($type),
                'args' => $this->getFilters($className)
            ];
        } else {
            return $type;
        }
    }

    /**
     * @param ClassDefinition\Data $fieldDefinition
     * @return array
     */
    protected function getLocalizedFieldType($fieldDefinition)
    {
        return [
            "type" => $this->fieldFactory->getSimpleType($fieldDefinition),
            'resolve' => function ($value, $args, $context, ResolveInfo $info) {
                return $this->dataProvider->getResolveLocalizedFunction($value, $info->fieldName);
            }
        ];
    }

    /**
     * @return \stdClass
     */
    public function getTypeList()
    {
        return $this->typeList;
    }
}
