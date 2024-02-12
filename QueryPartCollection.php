<?php

namespace FpDbTest;

/**
 * Коллекция частей запроса, включающая весь запрос целиком. При вызове конструктора происходит разбор запроса на части,
 * при использовании в качестве строки возвращает готовый запрос с подставленными значениями.
 */
class QueryPartCollection
{
    /**
     * @var QueryPartInterface[] $queryParts
     */
    protected array $queryParts = [];
    
    protected bool $needToTestForSkipping = false;
    
    private string $specialValueForMarkingSkippedBlocksInQueries;
    
    /**
     * Когда структура проекта прояснится, эту константу можно вынести на верхний уровень, чтобы закрыть данный класс
     * от изменений (SOLID Open/Closed Principle) в случае добавления новых спецификаторов
     */
    private const array SPECIFIER_TO_CLASS_MAP = [
        '?d' => IntQueryPart::class,
        '?f' => FloatQueryPart::class,
        '?#' => IdentifierQueryPart::class,
        '?a' => ArrayQueryPart::class,
        '?' => GenericScalarQueryPart::class,
    ];
    
    /**
     * @throws \Exception
     */
    public function __construct(
        string $queryTemplateString,
        array $queryParameterValues,
        $specialValueForMarkingSkippedBlocksInQueries,
        bool $needToTestForSkipping = false
    ) {
        $this->needToTestForSkipping = $needToTestForSkipping;
        $this->specialValueForMarkingSkippedBlocksInQueries = $specialValueForMarkingSkippedBlocksInQueries;
        
        if ($this->countSpecifiersInString($queryTemplateString) !== count($queryParameterValues)) {
            throw new \Exception('Количество параметров не совпадает с количеством спецификаторов в запросе');
        }
        
        if ($this->needToTestForSkipping && $this->containsSpecialValueForSkippedBlocks($queryParameterValues)
        ) {
            return;
        }
        
        $this->buildQueryParts(
            $queryParameterValues,
            $queryTemplateString,
            $specialValueForMarkingSkippedBlocksInQueries
        );
    }
    
    public function __toString(): string
    {
        $queryPartsStrings = [];
        foreach ($this->queryParts as $queryPart) {
            $queryPartsStrings[] = (string)$queryPart;
        }
        return implode('', $queryPartsStrings);
    }
    
    /**
     * @throws \Exception
     */
    protected function buildQueryParts(
        array $queryParameterValues,
        string $queryTemplateString,
        $specialValueForMarkingSkippedBlocksInQueries
    ): void {
        $queryParameterValues = array_values($queryParameterValues);
        $parameterIndex = 0;
        $queryPartStrings = $this->splitQueryTemplateToProcessableParts($queryTemplateString);
        
        foreach ($queryPartStrings as $queryPartString) {
            if ($this->isQueryPartASpecifier($queryPartString)) {
                $specifierClass = $this->getSpecifierClassName($queryPartString);
                if ($specifierClass === null) {
                    throw new \Exception('Неизвестный спецификатор в запросе');
                } else {
                    $this->queryParts[] = new $specifierClass(
                        $queryPartString,
                        $queryParameterValues[$parameterIndex]
                    );
                }
                $parameterIndex++;
            } elseif ($this->isBlock($queryPartString)) {
                $queryPartString = StringHelper::removeSurroundingCharacters($queryPartString);
                $argumentsInBlockTally = $this->countSpecifiersInString($queryPartString);
                
                $blockParameterValues = array_values(
                    array_slice($queryParameterValues, $parameterIndex, $argumentsInBlockTally)
                );
                $this->queryParts[] = new QueryPartCollection(
                    $queryPartString,
                    $blockParameterValues,
                    $specialValueForMarkingSkippedBlocksInQueries,
                    true
                );
                $parameterIndex += $argumentsInBlockTally;
            } else {
                $this->queryParts[] = new TextQueryPart($queryPartString);
            }
        }
    }
    
    private function countSpecifiersInString(string $query): int
    {
        return substr_count($query, '?');
    }
    
    protected function splitQueryTemplateToProcessableParts(string $query): array
    {
        $parts = preg_split('~(\?[#daf]?|\{.*}?+)~u', $query, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        return $parts;
    }
    protected function getSpecifierClassName(string $queryPartString): ?string
    {
        foreach (self::SPECIFIER_TO_CLASS_MAP as $specifierType => $specifierClass) {
            if (str_starts_with($queryPartString, $specifierType)) {
                return $specifierClass;
            }
        }
       
        return null;
    }
    
    protected function isQueryPartASpecifier(string $queryPartString): bool
    {
        return str_starts_with($queryPartString, '?');
    }
    
    protected function isBlock($queryPartString): bool
    {
        return str_starts_with($queryPartString, '{');
    }
    
    protected function containsSpecialValueForSkippedBlocks(array $blockParameterValues): bool
    {
        $result = in_array($this->specialValueForMarkingSkippedBlocksInQueries, $blockParameterValues, true);
        return $result;
    }
    
}