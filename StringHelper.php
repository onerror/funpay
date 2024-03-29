<?php

namespace FpDbTest;

/**
 * Класс, содержащий методы для работы со строками
 */
class StringHelper
{
    
    public static function removeSurroundingCharacters(string $inputString): string
    {
        return substr($inputString, 1, -1);
    }
    
    public static function wrapWithTicks(string $inputString): string
    {
        return "`{$inputString}`";
    }
    
    public static function wrapWithQuotes(string $inputString): string
    {
        return "'{$inputString}'";
    }
}