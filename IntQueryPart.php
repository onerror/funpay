<?php

namespace FpDbTest;


class IntQueryPart extends AbstractQueryPart implements SpecifierInterface
{
    protected string $templateQueryPartAsString;
    
    public function formatParameterValue($value): string
    {
        return is_null($value) ? 'NULL' : (string)$value;
    }
    
}