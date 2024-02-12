<?php

namespace FpDbTest;

/**
 * Часть запроса, которая не нуждается в обработке и подставляется в запрос как есть
 */
class TextQueryPart implements TextPartInterface
{
    
    protected string $templateQueryPartAsString;
    
    final public function __construct(string $queryPartAsString)
    {
        $this->templateQueryPartAsString = $queryPartAsString;
    }
    
    final public function __toString(): string
    {
        return $this->templateQueryPartAsString;
    }
    
}