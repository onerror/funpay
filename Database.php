<?php

namespace FpDbTest;

use Exception;
use mysqli;

class Database implements DatabaseInterface
{
    private mysqli $mysqli;

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }
    
    public function buildQuery(string $query, array $args = []): string
    {
        $resultingQueryArguments = $args;
        $queryParts = $this->splitTemplateToProcessableParts($query);

        $result = [];
        
        [$partsWithBlocksUnfoldedAndFiltered, $resultingQueryArguments] = $this->getQueryPartsWithBlocksUnfoldedAndFiltered($queryParts, $resultingQueryArguments);
        
        foreach ($partsWithBlocksUnfoldedAndFiltered as $part) {
            if ($part[0] == '?') {
                $specifierType = $this->getSpecifierTypeFromQueryPart($part);
                $value = array_shift($resultingQueryArguments);
                
                switch ($specifierType) {
                    case 'd':
                        $part = $this->formatIntOrNull($value);
                        break;
                    case 'f':
                        $part = $this->formatFloatOrNull($value);
                        break;
                    case '#':
                        if (is_array($value)) {
                            $part = $this->formatIdentifiersSet($value);
                        } else {
                            $part = $this->formatIdentifier($value);
                        }
                        break;
                    case 'a':
                        $part = $this->formatArrayValue($value);
                        break;
                    default:
                        if (is_array($value)) {
                            $part = "(" . implode(", ", array_map(function ($val) {
                                    return "'" . $this->getRealEscapeString($val) . "'";
                                }, $value)) . ")";
                        } else {
                            $part = "'" . $this->getRealEscapeString($value) . "'";
                        }
                        break;
                }
            }
            $result[] = $part;
        }
        
        return implode('', $result);
        
    }

    public function skip(): string
    {
        return '__SKIP__';
    }
    
    private function splitTemplateToProcessableParts(string $query): array
    {
        $parts = preg_split('~(\?[#daf]?|\{.*}?+)~u', $query, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        return $parts;
    }
    
    private function arrayIsAssociative(array $array):bool{
        return array_keys($array) !== range(0, count($array) - 1);
    }
    
    private function countParameters(string $query): int
    {
        return substr_count($query, '?');
    }
    
    private function isPartAParameter(string $part): bool
    {
        return $part[0] == '?';
    }
    
    private function getFormattedValue($value): float|int|string
    {
        return (is_numeric($value))? $value : "'". $this->getRealEscapeString($value) ."'";
    }
    
    private function getQueryPartsWithBlocksUnfoldedAndFiltered(array $queryParts, array $queryArguments):array
    {
        $newParts = [];
        $newArguments = $queryArguments;
        $argumentIndex = 0;
        foreach ($queryParts as $part) {
            if ($part[0] === '{') {
                $startingIndexOfBlockArguments = $argumentIndex;
                $block = substr($part, 1, -1);
                // todo check if block should disappear as a method
                $argumentsInBlockTally = $this->countParameters($block);
                $blockParts = $this->splitTemplateToProcessableParts($block);
                
                $toSkip = false;
                foreach ($blockParts as $blockPart) {
                    if ($this->isPartAParameter($blockPart)) {
                        if ($queryArguments[$argumentIndex] === $this->skip()) {
                            $toSkip = true;
                            break;
                        }
                        $argumentIndex++;
                    }
                }
                if ($toSkip) {
                    for ($indexToUnset = $startingIndexOfBlockArguments; $indexToUnset < $startingIndexOfBlockArguments + $argumentsInBlockTally; $indexToUnset++) {
                        unset($queryArguments[$indexToUnset]);
                    }
                } else {
                    $newParts = array_merge($newParts, $blockParts);
                }
            } else {
                $newParts[] = $part;
                if ($this->isPartAParameter($part)) {
                    $argumentIndex++;
                }
            }
        }
        return [$newParts, $newArguments];
        
    }
    
    /**
     * @param mixed $part
     *
     * @return string
     */
    public function getSpecifierTypeFromQueryPart(mixed $part): string
    {
        $type = substr($part, 1, 1);
        return $type;
    }
    
    /**
     * @param mixed $value
     *
     * @return string
     */
    public function getRealEscapeString(mixed $value): string
    {
        return $this->mysqli->real_escape_string($value);
    }
    
    /**
     * @param array $value
     *
     * @return string
     */
    public function formatIdentifiersSet(array $value): string
    {
        return "`" . implode('`, `', array_map(function ($val) {
                return $this->getRealEscapeString($val);
            }, $value)) . "`";
    }
    
    /**
     * @param mixed $value
     *
     * @return string
     */
    public function formatIdentifier(mixed $value): string
    {
        return '`' . $this->getRealEscapeString($value) . '`';
    }
    
    /**
     * @param mixed $value
     *
     * @return string
     */
    public function formatIntOrNull(mixed $value): string
    {
        // todo what if SELECT? then IS null instead of = null
        return is_null($value) ? 'NULL' :(string)$value;
    }
    
    public function formatArrayValue(array $value):string
    {
        $set = [];
        $isAssociative = $this->arrayIsAssociative($value);
        foreach ($value as $k => $v) {
            $set[] = ($isAssociative ? '`' . $this->getRealEscapeString($k) . '` = ' : '') .
                (is_null($v) ? 'NULL' : $this->getFormattedValue(
                    $v
                )); // todo in all such cases wrap in ' if not a number
        }
        $part = implode(', ', $set);
        return $part;
    }
    
    private function formatFloatOrNull(mixed $value): string
    {
        // todo what if SELECT? then IS null instead of = null
        return is_null($value) ?'NULL':(string)$value;
    }
}
