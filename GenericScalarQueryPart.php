<?php

namespace FpDbTest;

class GenericScalarQueryPart extends AbstractQueryPart implements SpecifierInterface
{
    protected string $templateQueryPartAsString;
    
    public function formatParameterValue($value): string
    {
        if (is_null($value)) {
            $formattedValue = 'NULL';
        } else {
            $formattedValue = is_numeric($value) ? $value : StringHelper::wrapWithQuotes($value);
        }
        return $formattedValue;
    }
}