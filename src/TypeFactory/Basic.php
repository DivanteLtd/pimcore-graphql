<?php
/**
 * @category    pimcore5-graphQl
 * @date        30/05/2018 08:48
 * @author      Kamil Janik <kjanik@divante.co>
 */

namespace Divante\GraphQlBundle\TypeFactory;

use \Divante\GraphQlBundle\DataManagement;
use GraphQL\Type\Definition\ResolveInfo;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;

/**
 * Class Basic
 */
class Basic
{
    /**
     * @var DataManagement\Query\Basic
     */
    private $dataProvider;

    private $customTypeColl = [];

    /**
     * @param iterable $customTypeColl
     */
    public function setCustomTypes(iterable $customTypeColl)
    {
        $this->customTypeColl = $customTypeColl;
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
     * @param Data $fieldDefinition
     * @return bool
     */
    public function isReferenceType(Data $fieldDefinition)
    {
        return in_array(
            $fieldDefinition->getFieldtype(),
            ["objects","manyToManyObjectRelation", "href", "manyToOneRelation"]
        );
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
        return in_array($fieldDefinition->getFieldtype(), ["objects", "manyToManyObjectRelation"]);
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
     * @return mixed
     * @throws \MissingTypeException
     */
    private function getCustomType(Data $fieldDefinition)
    {
        foreach ($this->customTypeColl as $customType) {
            if ($customType->supports($fieldDefinition->getPhpdocType())) {
                return $customType->getCustomType();
            }
        }

        throw new \MissingTypeException($fieldDefinition->getPhpdocType());
    }
}
