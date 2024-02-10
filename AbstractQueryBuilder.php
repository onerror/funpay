<?php

namespace FpDbTest;

use mysqli;


abstract class AbstractQueryBuilder implements QueryBuilderInterface
{
    private mysqli $mysqli;
    
    private string $specialValueForMarkingSkippedBlocksInQueries;
    
    final public function __construct(mysqli $mysqli, string $specialValueForMarkingSkippedBlocksInQueries)
    {
        $this->mysqli = $mysqli;
        $this->specialValueForMarkingSkippedBlocksInQueries = $specialValueForMarkingSkippedBlocksInQueries;
    }
    
    final public function buildQuery(string $query, array $queryParameters): string
    {
        try {
            $resultingQueryParameterValues = $queryParameters;
            $queryParts = $this->splitQueryTemplateToProcessableParts($query);
            $resultingQueryPartsArray = [];
            
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
                            $queryPart = $this->formatGenericScalarValueAsIs($value);
                            break;
                    }
                }
                $resultingQueryPartsArray[] = $queryPart;
            }
            
            $result = $this->postProcessQuery(implode('', $resultingQueryPartsArray));
            return $result;
        } catch (\Throwable $t) {
            throw new \Exception(
                'Ошибка при построении запроса: ' . $t->getMessage(), $t->getCode(), $t->getPrevious()
            );
        }
    }
    
    protected function postProcessQuery(string $query): string
    {
        return $query;
    }
    
    protected function getQueryPartsWithBlocksUnfoldedAndFiltered(array $queryParts, array $queryParameterValues): array
    {
        $newParts = [];
        $newArguments = $queryParameterValues;
        $argumentIndex = 0;
        foreach ($queryParts as $part) {
            if ($part[0] === '{') {
                $startingIndexOfBlockArguments = $argumentIndex;
                $block = StringHelper::removeSurroundingCharacters($part);
                $argumentsInBlockTally = $this->countParametersInCurlyBracesBlock($block);
                
                $blockParts = $this->splitQueryTemplateToProcessableParts($block);
                
                $blockParameterValues = array_slice($queryParameterValues, $argumentIndex, $argumentsInBlockTally);
                
                if ($this->isBlockNeededToBeSkipped($blockParts, $blockParameterValues)) {
                    $blockPartsToProcess = [];
                    for ($indexToUnset = $startingIndexOfBlockArguments; $indexToUnset < $startingIndexOfBlockArguments + $argumentsInBlockTally; $indexToUnset++) {
                        unset($queryParameterValues[$indexToUnset]);
                        
                    }
                } else {
                    $blockPartsToProcess = $blockParts;
                }
                $newParts = array_merge($newParts, $blockPartsToProcess);
            } else {
                $newParts[] = $part;
                if ($this->isQueryPartAParameter($part)) {
                    $argumentIndex++;
                }
            }
        }
        return [$newParts, $newArguments];
    }
    
    protected function splitQueryTemplateToProcessableParts(string $query): array
    {
        $parts = preg_split('~(\?[#daf]?|\{.*}?+)~u', $query, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        return $parts;
    }
    
    protected function isArrayAssociative(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }
    
    private function countParametersInCurlyBracesBlock(string $query): int
    {
        return substr_count($query, '?');
    }
    
    protected function isQueryPartAParameter(string $part): bool
    {
        return $part[0] == '?';
    }
    
    protected function getFormattedValue($value): float|int|string
    {
        return $this->formatGenericScalarValueAsIs($value);
    }
    
    protected function getSpecifierTypeFromQueryPart(string $queryPart): string
    {
        $type = substr($queryPart, 1, 1);
        return $type;
    }
    
    /**
     * Делаем строку безопасной для использования в запросе
     */
    protected function getRealEscapedString(string $value): string
    {
        return $this->mysqli->real_escape_string($value);
    }
    
    protected function formatIdentifiersSet(array $value): string
    {
        return implode(', ', array_map(function ($scalarValue) {
            return $this->formatIdentifier($scalarValue);
        }, $value));
    }
    
    protected function formatIdentifier(string $value): string
    {
        return StringHelper::wrapWithTicks( $this->getRealEscapedString($value));
    }
    
    protected function formatArrayValue(array $value): string
    {
        $set = [];
        $isAssociative = $this->isArrayAssociative($value);
        foreach ($value as $k => $v) {
            $set[] = ($isAssociative ? StringHelper::wrapWithTicks($this->getRealEscapedString($k)) . ' = ' : '') .
                (is_null($v) ? 'NULL' : $this->getFormattedValue(
                    $v
                ));
        }
        $part = implode(', ', $set);
        return $part;
    }
    
    
    protected function formatNullableIntValue(mixed $value): string
    {
        return is_null($value) ? 'NULL' : (string)$value;
    }
    
    protected function formatNullableFloatValue(mixed $value): string
    {
        return is_null($value) ? 'NULL' : (string)$value;
    }
    
    /**
     * Скалярное значение любого типа оставляем, как есть, только экранируем
     */
    public function formatGenericScalarValueAsIs(mixed $value): string
    {
        if (is_null($value)){
            $formattedValue = 'NULL';
        }else {
            $formattedValue = is_numeric($value) ? $value : "'" . $this->getRealEscapedString($value) . "'";
        }
        return $formattedValue;
    }
    
    /**
     * @param array $blockParts
     * @param array $blockParameterValues
     *
     * @return bool
     */
    public function isBlockNeededToBeSkipped(array $blockParts, array $blockParameterValues): bool
    {
        $toSkip = false;
        $currentBlockParameterIndex = 0;
        
        foreach ($blockParts as $blockPart) {
            if ($this->isQueryPartAParameter($blockPart)) {
                if ($blockParameterValues[$currentBlockParameterIndex] === $this->specialValueForMarkingSkippedBlocksInQueries) {
                    $toSkip = true;
                    break;
                }
                $currentBlockParameterIndex++;
            }
        }
        return $toSkip;
    }
    
}