<?php

namespace FpDbTest;


/**
 * Часть запроса для числовых значений с плавающей точкой, подставляемых на место спецификатора
 */
class FloatQueryPart extends AbstractSpecifier
{
    
    public function formatParameterValue(): string
    {
        return is_null($this->rawValue) ? 'NULL' : (string)$this->rawValue;
    }
}