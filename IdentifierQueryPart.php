<?php

namespace FpDbTest;


class IdentifierQueryPart extends AbstractSpecifier
{
    
    public function formatParameterValue(): string
    {
        if (is_array($this->rawValue)) {
            $result = $this->formatIdentifiersSet($this->rawValue);
        } else {
            $result = $this->formatIdentifier($this->rawValue);
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
    
    protected function valueIsValid(): bool
    {
        return is_string($this->rawValue) || is_array($this->rawValue);
    }
    
}