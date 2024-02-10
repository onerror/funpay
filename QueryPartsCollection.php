<?php

namespace FpDbTest;

class QueryPartsCollection
{
    private array $specifierToClassMap = [
        'd' => IntQueryPart::class,
        'f' => FloatQueryPart::class,
        '#' => IdentifierQueryPart::class,
        'a' => ArrayQueryPart::class,
    ];
    
    private array $queryParts;
    
    public function __construct(array $queryPartsStrings)
    {
        foreach ($queryPartsStrings as $queryPartString) {
            if ($this->isQueryPartASpecifier($queryPartString)) {
                $specifierType = $this->getSpecifierTypeFromQueryPart($queryPartString);
                
                if (in_array($specifierType, array_keys($this->specifierToClassMap))) {
                    $this->queryParts[] = new $this->specifierToClassMap[$specifierType]($queryPartString);
                } else {
                    $this->queryParts[] = new GenericScalarQueryPart($queryPartString);
                }
            } else {
                $this->queryParts[] = new TextQueryPart($queryPartString);
            }
        }
    }
    
    public function getQueryParts(): \Generator
    {
        foreach ($this->queryParts as $queryPart) {
            yield $queryPart;
        }
    }
    
    protected function getSpecifierTypeFromQueryPart(string $queryPart): string
    {
        $type = substr($queryPart, 1, 1);
        return $type;
    }
    
    protected function isQueryPartASpecifier(string $part): bool
    {
        return $part[0] == '?';
    }
}