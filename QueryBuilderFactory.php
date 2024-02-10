<?php

namespace FpDbTest;
use mysqli;

class QueryBuilderFactory
{
    public static function createQueryBuilder(mysqli $mysqli, string $specialValueForMarkingSkippedBlocksInQueries): QueryBuilderInterface
    {
        return new GenericQueryBuilder($mysqli, $specialValueForMarkingSkippedBlocksInQueries);
    }
}