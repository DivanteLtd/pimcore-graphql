<?php

namespace Divante\GraphQlBundle\TypeFactory;

interface ICustomTypeFactory
{
    public function supports(string $type);
    public function getCustomType();
}