<?php
/**
 * @category    pimcore5-graphQl
 * @date        30/05/2018 08:48
 * @author      Kamil Janik <kjanik@divante.co>
 */

namespace Divante\GraphQlBundle\Data;

use Divante\AOSExtensionBundle\Filter\FieldDefinitionAdapter\Classificationstore;
use Pimcore\Db;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;

/**
 * Class Provider
 * @package Divante\GraphQlBundle\Data
 */
class Provider
{
    const DATA_OBJECT_NAMESPACE = "Pimcore\\Model\\DataObject\\";

    /**
     * @var string
     */
    private $currentLanguage;

    /**
     * @var bool
     */
    private $unpublished;

    /**
     * @param bool $unpublished
     */
    public function setUnpublished(bool $unpublished)
    {
        $this->unpublished = $unpublished;
    }

    /**
     * @param string $currentLanguage
     */
    public function setCurrentLanguage(string $currentLanguage)
    {
        $this->currentLanguage = $currentLanguage;
    }

    /**
     * @return array
     */
    public function getClassList()
    {
        return array_map(
            function ($item) {
                return $item["name"];
            },
            Db::get()->fetchAll("SELECT name FROM classes") ?? []
        );
    }

    /**
     * @param string $className
     * @param array $args
     * @return mixed
     */
    public function getDataObject(string $className, array $args)
    {
        $name = self::DATA_OBJECT_NAMESPACE . ucfirst($className);
        if (!isset($args["id"])) {
            $name = $name. "\\Listing";
            $list = new $name();
            $list->setUnpublished($this->unpublished);

            if (isset($args["limit"]) && isset($args["offset"])) {
                $collection = $list->getItems($args["offset"], $args["limit"]);
            } else {
                $collection = $list->getObjects();
            }

            return $collection;
        }
        $obj =  $name::getById($args["id"]);

        return [$obj];
    }

    /**
     * @param $object
     * @param $fieldName
     * @return mixed
     */
    public function getResolveFunction($object, $fieldName)
    {
        $getter = "get" . ucfirst($fieldName);
        return $object->$getter();
    }

    /**
     * @param $object
     * @param $fieldName
     * @return mixed
     */
    public function getResolveLocalizedFunction($object, $fieldName)
    {
        $getter = "get" . ucfirst($fieldName);
        return $object->$getter($this->currentLanguage);
    }

    /**
     * @param $cs
     * @param $fieldName
     * @return array
     */
    public function getGroupColl($cs, $fieldName)
    {
        $csData = $cs->getItems();
        return  array_map(
            function ($key, $value) {
                $group = new \stdClass();
                $group->id = $key;
                $group->name = GroupConfig::getById($key)->getName();
                $group->attribute = $value;
                return $group;
            },
            array_keys($csData),
            $csData
        );
    }

    /**
     * @param $attribute
     * @param $fieldName
     * @return mixed
     */
    public function getAttributeData($attribute, $fieldName)
    {
        return $attribute->$fieldName;
    }

    /**
     * @param $group
     * @param $fieldName
     * @return array
     */
    public function getGroupData($group, $fieldName)
    {
        if ($fieldName == "attribute") {
            return array_map(
                function ($key, $value) {
                    $attribute = new \stdClass();
                    $attribute->id = $key;
                    $attribute->name = KeyConfig::getById($key)->getName();
                    $attrData = $value[$this->currentLanguage] ?? $value["default"];
                    if (!is_object($attrData)) {
                        $attribute->value = $attrData;
                    } else {
                        $attribute->value = $attrData->value;
                    }
                    $attribute->unit = $attrData->unit ?? null;
                    return $attribute;
                },
                array_keys($group->attribute),
                $group->attribute
            );
        }
        return $group->$fieldName;
    }

    /**
     * @param $unit
     * @param $fieldName
     * @return null
     */
    public function getUnitData($unit, $fieldName)
    {
            return $unit->$fieldName ?? null;
    }
}
