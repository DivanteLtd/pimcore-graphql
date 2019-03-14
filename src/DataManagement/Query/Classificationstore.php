<?php
/**
 * @category    pimcore5-graphQl
 * @date        14/02/2019 08:48
 * @author      Kamil Janik <kjanik@divante.co>
 */

namespace Divante\GraphQlBundle\DataManagement\Query;

use Pimcore\Db;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;

/**
 * Class Classificationstore
 */
class Classificationstore
{
    /**
     * @var string
     */
    private $currentLanguage;

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
