<?php
/**
 * @category    pimcore5-graphQl
 * @date        30/05/2018 08:48
 * @author      Kamil Janik <kjanik@divante.co>
 */

namespace Divante\GraphQlBundle\TypeFactory;

use \Divante\GraphQlBundle\TypeFactory\ICustomTypeFactory;
use \Divante\GraphQlBundle\DataManagement;
use GraphQL\Type\Definition\ResolveInfo;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;

/**
 * Class Classificationstore
 */
class Classificationstore implements ICustomTypeFactory
{
    const SUPPORTED_TYPE = "\\Pimcore\\Model\\DataObject\\Classificationstore";

    /**
     * @var DataManagement\Query\Classificationstore
     */
    private $dataProvider;

    /**
     * @var array
     */
    private $typeList = [];

    /**
     * @param DataManagement\Query\Classificationstore $dataProvider
     * @required
     */
    public function setDataProvider(DataManagement\Query\Classificationstore $dataProvider)
    {
        $this->dataProvider = $dataProvider;
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
        if (!(($this->typeList['classificationstore'] ?? null) instanceof  ObjectType)) {
            $this->typeList['classificationstore'] = new ObjectType([
                'name' => 'classificationstore',
                'fields' => [
                    'group' => [
                        'type' => Type::listOf($this->getGroupType())
                    ]
                ],
                'resolveField' => function ($value, $args, $context, ResolveInfo $info) {
                    return $this->dataProvider->getGroupColl($value, $info->fieldName);
                }
            ]);
        }
        return $this->typeList['classificationstore'];
    }

    /**
     * @return mixed
     */
    private function getGroupType()
    {
        if (!(($this->typeList['group'] ?? null) instanceof  ObjectType)) {
            $this->typeList['group'] = new ObjectType([
                'name' => 'group',
                'fields' => [
                    'id' => [
                        'type' => Type::int()
                    ],
                    'name' => [
                        'type' => Type::string()
                    ],
                    'attribute' => [
                        'type' => Type::listOf($this->getAttributeType())
                    ]
                ],
                'resolveField' => function ($value, $args, $context, ResolveInfo $info) {
                    return $this->dataProvider->getGroupData($value, $info->fieldName);
                }
            ]);
        }
        return $this->typeList['group'];
    }

    /**
     * @return mixed
     */
    private function getAttributeType() // todo fields should be generated from definitions
    {
        if (!(($this->typeList['attribute'] ?? null) instanceof  ObjectType)) {
            $this->typeList['attribute'] = new ObjectType([
                'name' => 'attribute',
                'fields' => [
                    'id' => [
                        'type' => Type::int()
                    ],
                    'name' => [
                        'type' => Type::string()
                    ],
                    'value' => [
                        'type' => Type::string()
                    ],
                    'unit' => [
                        'type' => $this->getUnitType()
                    ]
                ],
                'resolveField' => function ($value, $args, $context, ResolveInfo $info) {
                    return $this->dataProvider->getAttributeData($value, $info->fieldName);
                }
            ]);
        }
        return $this->typeList['attribute'];
    }

    /**
     * @return mixed
     */
    private function getUnitType() // todo fields should be generated from definitions
    {
        if (!(($this->typeList['unit'] ?? null) instanceof  ObjectType)) {
            $this->typeList['unit'] = new ObjectType([
                'name' => 'unit',
                'fields' => [
                    'id' => [
                        'type' => Type::int()
                    ],
                    'abbreviation' => [
                        'type' => Type::string()
                    ],
                    'longname' => [
                        'type' => Type::string()
                    ],
                    'reference' => [
                        'type' => Type::string()
                    ]
                ],
                'resolveField' => function ($value, $args, $context, ResolveInfo $info) {
                    return $this->dataProvider->getUnitData($value, $info->fieldName);
                }
            ]);
        }
        return $this->typeList['unit'];
    }
}
