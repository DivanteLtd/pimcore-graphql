<?php
/**
 * @category    pimcore5-graphQl
 * @date        30/05/2018 08:48
 * @author      Kamil Janik <kjanik@divante.co>
 */

namespace Divante\GraphQlBundle\Controller;

use Divante\GraphQlBundle\Builder\Query;
use GraphQL\Error\Debug;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Pimcore\Bundle\AdminBundle\Controller\Rest\Element\AbstractElementController;
use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;

/**
 * Class GraphQlController
 * @package Divante\GraphQlBundle\Controller
 */
class GraphQlController extends AbstractElementController
{
    /**
     * @Method({"POST", "PUT"})
     * @Route("/graph")
     * @param Request $request
     * @param Query $builder
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function graphQlAction(Request $request, Query $builder)
    {
        try {
            $queryType = $builder->getSchema();
            $schema = new Schema([
                'query' => $queryType
            ]);
            $input = json_decode($request->getContent(), true);
            $query = $input['query'];

            $variableValues = $input['variables'] ?? null;
            $result = GraphQL::executeQuery($schema, $query, null, null, $variableValues);
            $output = $result->toArray();

        } catch(\MissingTypeException $e) {

            $output = [
                'errors' => [
                    [
                        'message' => "There is no Type implemented for : " . $e->getMessage() . " please, check ...",
                    ]
                ]
            ];
        } catch (\Throwable $e) {
            $output = [
                'errors' => [
                    [
                        'message' => $e->getMessage(),
                        'stack' => $e->getTraceAsString()
                    ]
                ]
            ];

        }
        return $this->json($output);
    }
}
