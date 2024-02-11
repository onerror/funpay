<?php

namespace FpDbTest;


class ArrayQueryPart extends AbstractSpecifier
{
    protected string $templateQueryPartAsString;
    
    public function formatParameterValue(): string
    {
        $set = [];
        $isAssociative = $this->isArrayAssociative($this->rawValue);
        foreach ($this->rawValue as $k => $v) {
            $set[] = ($isAssociative ? StringHelper::wrapWithTicks($k) . ' = ' : '') .
                (is_null($v) ? 'NULL' : $this->formatGenericScalarValueAsIs($v));
        }
        $part = implode(', ', $set);
        return $part;
    }
    
    protected function isArrayAssociative(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }
    
    /**
     * Скалярное значение любого типа оставляем, как есть, только экранируем
     */
    protected function formatGenericScalarValueAsIs(mixed $value): string
    {
        if (is_null($value)) {
            $formattedValue = 'NULL';
        } else {
            $formattedValue = is_numeric($value) ? $value : StringHelper::wrapWithQuotes($value);
        }
        return $formattedValue;
    }
    
    protected function valueIsValid(): bool
    {
        return is_array($this->rawValue);
    }
}