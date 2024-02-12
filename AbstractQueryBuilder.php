<?php

namespace FpDbTest;

use mysqli;

/**
 * Класс для построения запросов по шаблону и заданному набору значений спецификаторов, указанных в шаблоне
 */
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
    
    /**
     * Делаем строку безопасной для использования в запросе
     */
    final protected function getRealEscapedString(string $value): string
    {
        return $this->mysqli->real_escape_string($value);
    }
    
}