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
    
    final public function buildQuery(string $queryTemplateString, array $queryParameterValues): string
    {
        try {
            $resultingQueryParameterValues = $queryParameterValues;
            $queryPartStrings = $this->splitQueryTemplateToProcessableParts($queryTemplateString);
            $resultingQueryPartsArray = [];
            
            [
                $partsWithBlocksUnfoldedAndFiltered,
                $resultingQueryParameterValues
            ] = $this->getQueryPartsWithBlocksUnfoldedAndFiltered($queryPartStrings, $resultingQueryParameterValues);
            
            $queryPartStrings = (new QueryPartsCollection($partsWithBlocksUnfoldedAndFiltered))->getQueryParts();
            
            foreach ($queryPartStrings as $queryPart) {
                if ($queryPart instanceof SpecifierInterface) {
                    $value = array_shift($resultingQueryParameterValues);
                    $value = is_string($value) ? $this->getRealEscapedString($value) : $value;
                    $resultingQueryPartsArray[] = $queryPart->formatParameterValue(
                        $value
                    );
                } else {
                    $resultingQueryPartsArray[] = (string)$queryPart;
                }
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
    
    // Блоки в будущем тоже можно выделить в отдельные классы по аналогии с QueryPartsCollection, но пока по ТЗ это слишком малозначимая часть
    protected function getQueryPartsWithBlocksUnfoldedAndFiltered(array $queryParts, array $queryParameterValues): array
    {
        $resultingParts = [];
        $resultingArguments = $queryParameterValues;
        $argumentIndex = 0;
        foreach ($queryParts as $queryPart) {
            if ($this->isBlock($queryPart[0])) {
                $startingIndexOfBlockArguments = $argumentIndex;
                $block = StringHelper::removeSurroundingCharacters($queryPart);
                $argumentsInBlockTally = $this->countSpecifiersInString($block);
                
                $blockParts = $this->splitQueryTemplateToProcessableParts($block);
                
                $blockParameterValues = array_slice($queryParameterValues, $argumentIndex, $argumentsInBlockTally);
                
                if ($this->containsSpecialValueForSkippedBlocks($blockParameterValues)) {
                    $blockPartsToProcess = [];
                    for ($indexToUnset = $startingIndexOfBlockArguments; $indexToUnset < $startingIndexOfBlockArguments + $argumentsInBlockTally; $indexToUnset++) {
                        unset($queryParameterValues[$indexToUnset]);
                    }
                } else {
                    $blockPartsToProcess = $blockParts;
                }
                $resultingParts = array_merge($resultingParts, $blockPartsToProcess);
            } else {
                $resultingParts[] = $queryPart;
                if ($this->isQueryPartAParameter($queryPart)) {
                    $argumentIndex++;
                }
            }
        }
        return [$resultingParts, $resultingArguments];
    }
    
    protected function splitQueryTemplateToProcessableParts(string $query): array
    {
        $parts = preg_split('~(\?[#daf]?|\{.*}?+)~u', $query, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        return $parts;
    }
    
    private function countSpecifiersInString(string $query): int
    {
        return substr_count($query, '?');
    }
    
    protected function isQueryPartAParameter(string $part): bool
    {
        return $part[0] == '?';
    }
    
    /**
     * Делаем строку безопасной для использования в запросе
     */
    protected function getRealEscapedString(string $value): string
    {
        return $this->mysqli->real_escape_string($value);
    }
    
    public function containsSpecialValueForSkippedBlocks(array $blockParameterValues): bool
    {
        $result = in_array($this->specialValueForMarkingSkippedBlocksInQueries, $blockParameterValues, true);
        return $result;
    }
    
    protected function isBlock($queryPartString): bool
    {
        return $queryPartString === '{';
    }
    
}