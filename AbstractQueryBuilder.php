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
            if ($this->countSpecifiersInString($queryTemplateString) !== count($queryParameterValues)) {
                throw new \Exception('Количество параметров не совпадает с количеством спецификаторов в запросе');
            }
            
            $queryPartsCollection = new QueryPartCollection(
                $queryTemplateString,
                array_map(fn($value) => is_string($value) ? $this->getRealEscapedString($value) : $value
                    , $queryParameterValues),
                $this->specialValueForMarkingSkippedBlocksInQueries
            );
            
            $result = $this->postProcessQuery((string)$queryPartsCollection);
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
    
    private function countSpecifiersInString(string $query): int
    {
        return substr_count($query, '?');
    }
    
    
    /**
     * Делаем строку безопасной для использования в запросе
     */
    protected function getRealEscapedString(string $value): string
    {
        return $this->mysqli->real_escape_string($value);
    }
    
}