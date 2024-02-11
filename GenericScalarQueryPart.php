<?php

namespace FpDbTest;

class GenericScalarQueryPart extends AbstractSpecifier
{
    
    public function formatParameterValue(): string
    {
        if (is_null($this->rawValue)) {
            $formattedValue = 'NULL';
        } else {
            $formattedValue = is_numeric($this->rawValue) ? $this->rawValue : StringHelper::wrapWithQuotes(
                $this->rawValue
            );
        }
        return $formattedValue;
    }
    
}