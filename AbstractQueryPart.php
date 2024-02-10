<?php

namespace FpDbTest;

class AbstractQueryPart implements QueryPartInterface
{
    protected string $templateQueryPartAsString;
    
    public function __construct(string $queryPartAsString)
    {
        $this->templateQueryPartAsString = $queryPartAsString;
    }
    
    public function __toString(): string
    {
        return $this->templateQueryPartAsString;
    }
}