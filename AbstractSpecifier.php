<?php

namespace FpDbTest;

/**
 * Класс спецификатора для запроса. Примеры спецификаторов: ?d, ?#
 */
abstract class AbstractSpecifier implements SpecifierInterface
{
    protected string $templateQueryPartAsString;
    protected mixed $rawValue;
    
    final public function __construct(string $queryPartAsString, mixed $value)
    {
        $this->templateQueryPartAsString = $queryPartAsString;
        $this->rawValue = $value;
        if (!$this->valueIsValid()) {
            throw new \InvalidArgumentException(
                'Значение ' . $this->rawValue . ' не подходит для спецификатора ' . $queryPartAsString
            );
        }
    }
    
    final public function __toString(): string
    {
        return $this->formatParameterValue();
    }
    
    protected function valueIsValid(): bool
    {
        return true;
    }
    
    protected function formatParameterValue(): string
    {
        return is_null($this->rawValue) || is_float($this->rawValue) || is_integer(
                $this->rawValue
            ) || is_string($this->rawValue) || is_bool($this->rawValue);
    }
}