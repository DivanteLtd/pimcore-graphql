<?php
/**
 * @category    pimcore5-graphQl
 * @date        30/05/2018 08:48
 * @author      Kamil Janik <kjanik@divante.co>
 */

namespace Divante\GraphQlBundle\Controller;

use GraphQL\Error\Debug;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Divante\GraphQlBundle\Type\Factory;
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
     * @param Factory $typeFactory
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function graphQlAction(Request $request, Factory $typeFactory)
    {
        try {
            $queryType = $typeFactory->getQueryType();
            $schema = new Schema([
                'query' => $queryType
            ]);
            $input = json_decode($request->getContent(), true);
            $query = $input['query'];

            $variableValues = $input['variables'] ?? null;
            $result = GraphQL::executeQuery($schema, $query, null, null, $variableValues);
            $output = $result->toArray();

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
