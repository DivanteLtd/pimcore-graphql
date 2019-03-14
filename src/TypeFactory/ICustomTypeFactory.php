<?php

namespace Divante\GraphQlBundle\TypeFactory;

use GraphQL\Type\Definition\ObjectType;

interface ICustomTypeFactory
{
    public function supports(string $type) : bool;
    public function getCustomType() : ObjectType;
}