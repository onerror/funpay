<?php

namespace FpDbTest;


class FloatQueryPart extends AbstractSpecifier
{
    
    public function formatParameterValue(): string
    {
        return is_null($this->rawValue) ? 'NULL' : (string)$this->rawValue;
    }
}