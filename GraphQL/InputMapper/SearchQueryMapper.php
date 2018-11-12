<?php
/**
 * Created by PhpStorm.
 * User: bdunogier
 * Date: 21/09/2018
 * Time: 16:50
 */

namespace BD\EzPlatformGraphQLBundle\GraphQL\InputMapper;

use eZ\Publish\API\Repository\Values\Content\Query;
use GraphQL\Error\UserError;
use InvalidArgumentException;

class SearchQueryMapper
{
    /**
     * @return \eZ\Publish\API\Repository\Values\Content\Query
     */
    public function mapInputToQuery(array $inputArray)
    {
        $query = new Query();
        $criteria = [];

        if (isset($inputArray['ContentTypeIdentifier'])) {
            $criteria[] = new Query\Criterion\ContentTypeIdentifier($inputArray['ContentTypeIdentifier']);
        }

        if (isset($inputArray['Text'])) {
            foreach ($inputArray['Text'] as $text) {
                $criteria[] = new Query\Criterion\FullText($text);
            }
        }

        if (isset($inputArray['Field'])) {
            $inputArray['Fields'] = [$inputArray['Field']];
        }

        if (isset($inputArray['Fields'])) {
            foreach ($inputArray['Fields'] as $fieldFilter) {
                foreach (['in', 'eq', 'like', 'contains', 'between', 'lt', 'lte', 'gt', 'gte'] as $opString) {
                    if (!isset($fieldFilter['target'])) {
                        throw new UserError("Field filters require a 'target' attribute");
                    }

                    if (isset($fieldFilter[$opString])) {
                        $criteria[] = new Query\Criterion\Field(
                            $fieldFilter['target'],
                            constant(Query\Criterion\Operator::class . '::' . strtoupper($opString)),
                            $fieldFilter[$opString]
                        );
                        continue 2;
                    }
                }

                throw new UserError("Invalid filter, missing operator");
            }
        }

        $criteria = array_merge($criteria, $this->mapDateMetadata($inputArray, 'Modified'));
        $criteria = array_merge($criteria, $this->mapDateMetadata($inputArray, 'Created'));

        if (count($criteria) === 0) {
            return $query;
        }

        if (count($criteria)) {
            $query->filter = count($criteria) > 1 ? new Query\Criterion\LogicalAnd($criteria) : $criteria[0];
        }

        return $query;
    }

    /**
     * @param array $queryArg
     * @param $dateMetadata
     * @return \eZ\Publish\API\Repository\Values\Content\Query\Criterion\DateMetadata[]
     */
    private function mapDateMetadata(array $queryArg = [], $dateMetadata)
    {
        if (!isset($queryArg[$dateMetadata]) || !is_array($queryArg[$dateMetadata])) {
            return [];
        }

        $targetMap = [
            'Created' => Query\Criterion\DateMetadata::CREATED,
            'Modified' => Query\Criterion\DateMetadata::MODIFIED,
        ];

        if (!isset($targetMap[$dateMetadata])) {
            echo "Not a date metadata\n";
            return [];
        }

        $dateOperatorsMap = [
            'on' => Query\Criterion\Operator::EQ,
            'before' => Query\Criterion\Operator::LTE,
            'after' => Query\Criterion\Operator::GTE,
        ];

        $criteria = [];
        foreach ($queryArg[$dateMetadata] as $operator => $dateString) {
            if (!isset($dateOperatorsMap[$operator])) {
                echo "Not a valid operator\n";
                continue;
            }

            $criteria[] = new Query\Criterion\DateMetadata(
                $targetMap[$dateMetadata],
                $dateOperatorsMap[$operator],
                strtotime($dateString)
            );
        }

        return $criteria;
    }
}