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
    
    protected bool $needToTestOnSkipping = false;
    
    private string $specialValueForMarkingSkippedBlocksInQueries;
    
    private array $specifierToClassMap = [
        'd' => IntQueryPart::class,
        'f' => FloatQueryPart::class,
        '#' => IdentifierQueryPart::class,
        'a' => ArrayQueryPart::class,
    ];
    
    /**
     * @throws \Exception
     */
    public function __construct(
        string $queryTemplateString,
        array $queryParameterValues,
        $specialValueForMarkingSkippedBlocksInQueries,
        bool $needToTestOnSkipping = false
    ) {
        $this->needToTestOnSkipping = $needToTestOnSkipping;
        $this->specialValueForMarkingSkippedBlocksInQueries = $specialValueForMarkingSkippedBlocksInQueries;
        
        if ($this->countSpecifiersInString($queryTemplateString) !== count($queryParameterValues)) {
            throw new \Exception('Количество параметров не совпадает с количеством спецификаторов в запросе');
        }
        
        if ($this->needToTestOnSkipping && $this->containsSpecialValueForSkippedBlocks($queryParameterValues)
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
    
    private function countSpecifiersInString(string $query): int
    {
        return substr_count($query, '?');
    }
    
    protected function splitQueryTemplateToProcessableParts(string $query): array
    {
        $parts = preg_split('~(\?[#daf]?|\{.*}?+)~u', $query, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        return $parts;
    }
    
    protected function getSpecifierTypeFromQueryPart(string $queryPartString): string
    {
        $type = substr($queryPartString, 1, 1);
        return $type;
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
                $specifierType = $this->getSpecifierTypeFromQueryPart($queryPartString);
                
                if (in_array($specifierType, array_keys($this->specifierToClassMap))) {
                    $this->queryParts[] = new $this->specifierToClassMap[$specifierType](
                        $queryPartString,
                        $queryParameterValues[$parameterIndex]
                    );
                } else {
                    $this->queryParts[] = new GenericScalarQueryPart(
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
    
}