<?php
/**
 * @category    pimcore5-graphQl
 * @date        30/05/2018 08:48
 * @author      Kamil Janik <kjanik@divante.co>
 */

namespace Divante\GraphQlBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;

/**
 * Class DivanteGraphQlBundle
 * @package Divante\GraphQlBundle
 */
class DivanteGraphQlBundle extends AbstractPimcoreBundle
{
    /**
     * @inheritdoc
     */
    public function getNiceName()
    {
        return 'GraphQl';
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return 'Adds graphQl endpoint for manipulating DataObjects';
    }

    /**
     * @inheritdoc
     */
    public function getVersion()
    {
        return '1.0.0';
    }
}
