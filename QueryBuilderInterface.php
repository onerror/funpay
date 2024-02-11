<?php

namespace FpDbTest;

use mysqli;

interface QueryBuilderInterface
{
    public function __construct(mysqli $mysqli, string $specialValueForMarkingSkippedBlocksInQueries);
    
    public function buildQuery(string $queryTemplateString, array $queryParameterValues): string;
}