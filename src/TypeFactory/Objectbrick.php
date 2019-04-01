<?php
/**
 * @category    pimcore5-graphQl
 * @date        30/05/2018 08:48
 * @author      Kamil Janik <kjanik@divante.co>
 */

namespace Divante\GraphQlBundle\TypeFactory;

use Divante\GraphQlBundle\Builder\Query;
use \Divante\GraphQlBundle\TypeFactory\ICustomTypeFactory;
use \Divante\GraphQlBundle\DataManagement;
use GraphQL\Type\Definition\ResolveInfo;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use Pimcore\Model\DataObject\Objectbrick\Definition;

/**
 * Class Objectbrick
 */
class Objectbrick implements ICustomTypeFactory
{
    const SUPPORTED_TYPE = "\\Pimcore\\Model\\DataObject\\Objectbrick";
//
//    /**
//     * @var DataManagement\Query\Classificationstore
//     */
//    private $dataProvider;

    /**
     * @var \stdClass
     */
    private $typeList;

    /**
     * @var Query
     */
    private $builder;

    /**
     * @var
     */
    private $dataProvider;

    /**
     * @param DataManagement\Query\Basic $dataProvider
     */
    public function setDataProvider(DataManagement\Query\Basic $dataProvider): void
    {
        $this->dataProvider = $dataProvider;
    }

    /**
     * @param Query $builder
     * @required
     */
    public function setBuilder(Query $builder): void
    {
        $this->builder = $builder;
    }

    /**
     * @param \stdClass $typeList
     */
    public function setTypeList(\stdClass $typeList): void
    {
        $this->typeList = $typeList;
    }
//
//    /**
//     * @param DataManagement\Query\Classificationstore $dataProvider
//     * @required
//     */
//    public function setDataProvider(DataManagement\Query\Classificationstore $dataProvider)
//    {
//        $this->dataProvider = $dataProvider;
//    }

    private function init()
    {
        if (!($this->typeList instanceof \stdClass)) {
            $this->typeList = $this->builder->getTypeList();
        }
        if (!$this->dataProvider instanceof DataManagement\Query\Basic) {
            $this->dataProvider = $this->builder->getDataProvider();
        }
    }

    /**
     * @param string $type
     * @return bool
     */
    public function supports(string $type) : bool
    {
        return self::SUPPORTED_TYPE == $type;
    }

    /**
     * @return ObjectType
     */
    public function getCustomType(Data $definition) : ObjectType
    {
        $this->init();
        if (!(($this->typeList->{'objectbrick'} ?? null) instanceof ObjectType)) {
            $this->typeList->{'objectbrick'} = new ObjectType([
                'name' => 'objectbrick',
                'fields' =>
                    array_reduce(
                        $this->getAllowedTypes($definition),
                        function ($carry, $name) {
                            $carry[$name] = $this->getBrickConfig($name);
                            return $carry;
                        },
                        []
                    ),
                'resolveField' => function ($value, $args, $context, ResolveInfo $info) {

                    $getter = "get" . $info->fieldName;
                    if (method_exists($value, $getter)) {
                        $result = $value->$getter();
                        return $result;
                    } else {
                        return null;
                    }
                }
            ]);
        }
        return $this->typeList->{'objectbrick'};
    }

    private function getAllowedTypes(Data $definition)
    {
        return $definition->getAllowedTypes();
    }

    /**
     * @param string $className
     * @return mixed
     */
    private function getBrickConfig(string $className)
    {
        $definition = Definition::getByKey($className);
        $this->typeList->{$className} = true;
        if ($definition instanceof Definition) {
            $this->typeList->{$className} = new ObjectType([
                'name' => $className,
                'fields' => function () use ($className, $definition) {
                    foreach ($definition->getFieldDefinitions() as $item) {
                        if ($item->getName() == "localizedfields") {
                            $this->builder->parseLocalizedfields($item, $def);
                        } else {
                            $def[$item->getName()] = $this->builder->getFieldType($item);
                        }
                    }

                    return $def;
                },
                'resolveField' => function ($value, $args, $context, ResolveInfo $info) {
//                    file_put_contents("/var/www/html/var/logs/my.log", print_r(["No 1"], true), FILE_APPEND);
                    return $this->dataProvider->getResolveFunction($value, $info->fieldName, $args);
                }
            ]);
            return $this->typeList->{$className};
        } else {
            return Type::int();
        }
    }
}
