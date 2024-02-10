<?php

namespace FpDbTest;
class StringHelper
{
    
    /**
     * @param mixed $part
     *
     * @return string
     */
    public static function removeSurroundingCharacters(string $part): string
    {
        return substr($part, 1, -1);
    }
}