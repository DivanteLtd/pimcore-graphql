<?php
/**
 * @category    pimcore5-graphQl
 * @date        14/02/2019 08:48
 * @author      Kamil Janik <kjanik@divante.co>
 */

namespace Divante\GraphQlBundle\DataManagement\Query;

use Pimcore\Db;

/**
 * Class Basic
 */
class Basic
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

            foreach ($args as $name => $value) {
                if (!in_array($name, ['id', 'offset', 'limit', 'language', 'unpublished'])) {
                    $list->addConditionParam($name . " = ?", $value);
                }
            }

            if (isset($args["limit"]) && isset($args["offset"])) {
                $collection = $list->getItems($args["offset"], $args["limit"]);
            } else {
                $collection = $list->getObjects() ?? [];
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
    public function getResolveFunction($object, $fieldName, $args = [])
    {
        $getter = "get" . ucfirst($fieldName);
        if (!method_exists($object, $getter)) {
           return null;
        }
        $result = $object->$getter();
        $filter = function ($item) use ($args) {
            foreach ($args as $name => $value) {
                $getter = "get" . ucfirst($name);
                if ($item->$getter() != $value) {
                    return false;
                }
            }
            return true;
        };

        if (!empty($args)) {
            if (is_array($result)) {
                $result = array_filter(
                    $result,
                    $filter
                );
            } elseif (!$filter($result)) {
                $result = null;
            }
        }

        return $result;
    }

    /**
     * @param $object
     * @param $fieldName
     * @return mixed
     */
    public function getResolveLocalizedFunction($object, $fieldName)
    {
        $getter = "get" . ucfirst($fieldName);
        if (!method_exists($object, $getter)) {
            return null;
        }
        return $object->$getter($this->currentLanguage);
    }
}
