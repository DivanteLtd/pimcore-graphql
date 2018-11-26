<?php
/**
 * @category    pimcore5-graphQl
 * @date        30/05/2018 08:48
 * @author      Kamil Janik <kjanik@divante.co>
 */

namespace Divante\GraphQlBundle\Type;

use Divante\GraphQlBundle\Data\Provider;
use GraphQL\Type\Definition\ResolveInfo;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;

/**
 * Class FieldType
 * @package Divante\GraphQlBundle\Type
 */
class FieldType
{
    /**
     * @var Provider
     */
    private $dataProvider;

    /**
     * @var array
     */
    private $typeList = [];

    /**
     * @param Provider $dataProvider
     * @required
     */
    public function setDataProvider(Provider $dataProvider)
    {
        $this->dataProvider = $dataProvider;
    }

    /**
     * @param Data $fieldDefinition
     * @return bool
     */
    public function isReferenceType(Data $fieldDefinition)
    {
        return in_array($fieldDefinition->getFieldtype(), ["objects","href"]);
    }

    /**
     * @param Data $fieldDefinition
     * @return bool
     */
    public function isUnionType(Data $fieldDefinition)
    {
        if (!$this->isReferenceType($fieldDefinition)) {
            return false;
        }
        $classes = $fieldDefinition->getClasses();
        return count($classes) > 1;
    }

    /**
     * @param Data $fieldDefinition
     * @return bool
     */
    public function isCollectionReferenceType(Data $fieldDefinition)
    {
        return in_array($fieldDefinition->getFieldtype(), ["objects"]);
    }

    /**
     * @param Data $fieldDefinition
     * @return mixed
     */
    public function getClassName(Data $fieldDefinition)
    {
        $classes = $fieldDefinition->getClasses();
        return $classes[0]["classes"] ?? null;
    }

    /**
     * @param Data $fieldDefinition
     * @return array
     */
    public function getClassNameCollection(Data $fieldDefinition)
    {
        return [];
    }

    /**
     * @param Data $fieldDefinition
     * @return \GraphQL\Type\Definition\BooleanType|\GraphQL\Type\Definition\FloatType|\GraphQL\Type\Definition\IntType|\GraphQL\Type\Definition\ListOfType|\GraphQL\Type\Definition\StringType
     */
    public function getSimpleType(Data $fieldDefinition)
    {
        switch ($fieldDefinition->getPhpdocType()) {
            case "string":
                return Type::string();
                break;
            case "boolean":
                return Type::boolean();
                break;
            case "array":
                return Type::listOf(Type::string());
                break;
            case "float":
                return Type::float();
                break;
            case "int":
                return Type::int();
                break;
            default:
                return $this->getCustomType($fieldDefinition);
        }
    }

    /**
     * @param Data $fieldDefinition
     * @return \GraphQL\Type\Definition\IntType|mixed
     */
    private function getCustomType(Data $fieldDefinition)
    {
        switch ($fieldDefinition->getPhpdocType()) {
            case "\\Pimcore\\Model\\DataObject\\Classificationstore":
                return $this->getClassificationstoreType();
                break;
            default:
                return Type::int();
        }
    }

    /**
     * @return mixed
     */
    private function getClassificationstoreType()
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
