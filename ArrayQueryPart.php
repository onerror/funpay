<?php

namespace FpDbTest;


class ArrayQueryPart extends AbstractQueryPart implements SpecifierInterface
{
    protected string $templateQueryPartAsString;
    
    public function formatParameterValue($value): string
    {
        $set = [];
        $isAssociative = $this->isArrayAssociative($value);
        foreach ($value as $k => $v) {
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
}