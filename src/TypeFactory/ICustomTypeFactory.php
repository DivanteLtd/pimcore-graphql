<?php

namespace Divante\GraphQlBundle\TypeFactory;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Pimcore\Model\DataObject\ClassDefinition\Data;

interface ICustomTypeFactory
{
    public function supports(string $type) : bool;
    public function getCustomType(Data $definition) : Type;
}
