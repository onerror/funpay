<?php

namespace FpDbTest;

use Exception;
use mysqli;

class Database implements DatabaseInterface
{
    /* Я не знаю структуру проекта, частью которого должно являться это решение.
    Эта константа должна быть в конфигурационном файле проекта или в БД или в каком-то другом общем классе или интерфейсе,
     потому что она должна использоваться по всему проекту - в коде, генерирующем значения параметров запросов, например.
    */
    const string SPECIAL_VALUE_FOR_MARKING_SKIPPED_BLOCKS_IN_QUERY = '__SKIP_THIS_BLOCK__';
    private mysqli $mysqli;
    
    /**
     * @var QueryBuilderInterface[] $queryBuilders
     */
    private array $queryBuilders;

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }
    
    /**
     * @throws Exception
     */
    public function buildQuery(string $query, array $args = []): string
    {
        try {
            /**
             * @var QueryBuilderInterface $queryBuilder
             */
            $queryBuilderClass = GenericQueryBuilder::class;
            if (stripos($query, 'SELECT') === 0) {
                $queryBuilderClass = SelectQueryBuilder::class;
            }
            if (!isset($this->queryBuilders[$queryBuilderClass])) {
                $this->queryBuilders[$queryBuilderClass] = new $queryBuilderClass(
                    $this->mysqli,
                    self::SPECIAL_VALUE_FOR_MARKING_SKIPPED_BLOCKS_IN_QUERY
                );
            }
            $queryBuilder = $this->queryBuilders[$queryBuilderClass];
            
            return $queryBuilder->buildQuery($query, $args);
        }catch(\Throwable $t){
            throw new Exception('Ошибка при построении запроса: ' . $t->getMessage(),$t->getCode(), $t->getPrevious());
        }
        
    }

    public function skip(): string
    {
        return self::SPECIAL_VALUE_FOR_MARKING_SKIPPED_BLOCKS_IN_QUERY;
    }
}
