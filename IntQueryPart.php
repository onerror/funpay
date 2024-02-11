<?php

namespace FpDbTest;


class IntQueryPart extends AbstractSpecifier implements SpecifierInterface
{
    public function formatParameterValue(): string
    {
        return is_null($this->rawValue) ? 'NULL' : (string)$this->rawValue;
    }
    
}