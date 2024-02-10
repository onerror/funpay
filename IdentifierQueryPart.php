<?php

namespace FpDbTest;


class IdentifierQueryPart extends AbstractQueryPart implements SpecifierInterface
{
    protected string $templateQueryPartAsString;
    
    public function formatParameterValue($value): string
    {
        if (is_array($value)) {
            $result = $this->formatIdentifiersSet($value);
        } else {
            $result = $this->formatIdentifier($value);
        }
        return $result;
    }
    
    protected function formatIdentifiersSet(array $value): string
    {
        return implode(', ', array_map(function ($scalarValue) {
            return $this->formatIdentifier($scalarValue);
        }, $value));
    }
    
    protected function formatIdentifier(string $value): string
    {
        return StringHelper::wrapWithTicks($value);
    }
}