<?php

namespace FpDbTest;

use mysqli;


abstract class AbstractQueryBuilder implements QueryBuilderInterface
{
    private mysqli $mysqli;
    
    private string $specialValueForMarkingSkippedBlocksInQueries;
    
    public function __construct(mysqli $mysqli, string $specialValueForMarkingSkippedBlocksInQueries)
    {
        $this->mysqli = $mysqli;
        $this->specialValueForMarkingSkippedBlocksInQueries = $specialValueForMarkingSkippedBlocksInQueries;
    }
    
    public function buildQuery(string $query, array $queryParameters): string
    {
        try {
            $resultingQueryParameterValues = $queryParameters;
            $queryParts = $this->splitQueryTemplateToProcessableParts($query);
            $result = [];
            
            [
                $partsWithBlocksUnfoldedAndFiltered,
                $resultingQueryParameterValues
            ] = $this->getQueryPartsWithBlocksUnfoldedAndFiltered($queryParts, $resultingQueryParameterValues);
            
            foreach ($partsWithBlocksUnfoldedAndFiltered as $queryPart) {
                if ($queryPart[0] == '?') {
                    $specifierType = $this->getSpecifierTypeFromQueryPart($queryPart);
                    $value = array_shift($resultingQueryParameterValues);
                    
                    switch ($specifierType) {
                        case 'd':
                            $queryPart = $this->formatNullableIntValue($value);
                            break;
                        case 'f':
                            $queryPart = $this->formatNullableFloatValue($value);
                            break;
                        case '#':
                            if (is_array($value)) {
                                $queryPart = $this->formatIdentifiersSet($value);
                            } else {
                                $queryPart = $this->formatIdentifier($value);
                            }
                            break;
                        case 'a':
                            $queryPart = $this->formatArrayValue($value);
                            break;
                        default:
                            $queryPart = "'" . $this->getRealEscapedStringForValue($value) . "'"; // todo which type?
                            break;
                    }
                }
                $result[] = $queryPart;
            }
            
            return implode('', $result);
        } catch (\Throwable $t) {
            throw new \Exception(
                'Ошибка при построении запроса: ' . $t->getMessage(), $t->getCode(), $t->getPrevious()
            );
        }
    }
    
    private function getQueryPartsWithBlocksUnfoldedAndFiltered(array $queryParts, array $queryArguments): array
    {
        $newParts = [];
        $newArguments = $queryArguments;
        $argumentIndex = 0;
        foreach ($queryParts as $part) {
            if ($part[0] === '{') {
                $startingIndexOfBlockArguments = $argumentIndex;
                $block = StringHelper::removeSurroundingCharacters($part);
                // todo check if block should disappear as a method
                $argumentsInBlockTally = $this->countParameters($block);
                $blockParts = $this->splitQueryTemplateToProcessableParts($block);
                
                $toSkip = false;
                foreach ($blockParts as $blockPart) {
                    if ($this->isQueryPartAParameter($blockPart)) {
                        if ($queryArguments[$argumentIndex] === $this->specialValueForMarkingSkippedBlocksInQueries) {
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
                if ($this->isQueryPartAParameter($part)) {
                    $argumentIndex++;
                }
            }
        }
        return [$newParts, $newArguments];
    }
    
    private function splitQueryTemplateToProcessableParts(string $query): array
    {
        $parts = preg_split('~(\?[#daf]?|\{.*}?+)~u', $query, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        return $parts;
    }
    
    private function arrayIsAssociative(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }
    
    private function countParameters(string $query): int
    {
        return substr_count($query, '?');
    }
    
    private function isQueryPartAParameter(string $part): bool
    {
        return $part[0] == '?';
    }
    
    private function getFormattedValue($value): float|int|string
    {
        return (is_numeric($value)) ? $value : "'" . $this->getRealEscapedStringForValue($value) . "'";
    }
    
    /**
     * @param string $queryPart
     *
     * @return string
     */
    public function getSpecifierTypeFromQueryPart(string $queryPart): string
    {
        $type = substr($queryPart, 1, 1);
        return $type;
    }
    
    /**
     * @param mixed $value
     *
     * @return string
     */
    public function getRealEscapedStringForValue(mixed $value): string
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
                return $this->getRealEscapedStringForValue($val);
            }, $value)) . "`";
    }
    
    /**
     * @param mixed $value
     *
     * @return string
     */
    public function formatIdentifier(mixed $value): string
    {
        return '`' . $this->getRealEscapedStringForValue($value) . '`';
    }
    
    public function formatArrayValue(array $value): string
    {
        $set = [];
        $isAssociative = $this->arrayIsAssociative($value);
        foreach ($value as $k => $v) {
            $set[] = ($isAssociative ? '`' . $this->getRealEscapedStringForValue($k) . '` = ' : '') .
                (is_null($v) ? 'NULL' : $this->getFormattedValue(
                    $v
                )); // todo in all such cases wrap in ' if not a number
        }
        $part = implode(', ', $set);
        return $part;
    }
    
    
    public function formatNullableIntValue(mixed $value, $useIsNullInsteadOfAssignment = false): string
    {
        // todo what if SELECT? then IS null instead of = null
        return is_null($value) ? 'NULL' : (string)$value;
    }
    
    protected function formatNullableFloatValue(mixed $value, $useIsNullInsteadOfAssignment = false): string
    {
        // todo what if SELECT? then IS null instead of = null
        return is_null($value) ? 'NULL' : (string)$value;
    }
    
}