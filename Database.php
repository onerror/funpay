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
    
    private QueryBuilderInterface $queryBuilder;

    public function __construct(mysqli $mysqli)
    {
        $this->queryBuilder = QueryBuilderFactory::createQueryBuilder($mysqli, self::SPECIAL_VALUE_FOR_MARKING_SKIPPED_BLOCKS_IN_QUERY);
    }
    
    /**
     * @throws Exception
     */
    public function buildQuery(string $query, array $args = []): string
    {
        try {
            return $this->queryBuilder->buildQuery($query, $args);
        }catch(\Throwable $t){
            throw new Exception('Ошибка при построении запроса: ' . $t->getMessage(),$t->getCode(), $t->getPrevious());
        }
        
    }

    public function skip(): string
    {
        return self::SPECIAL_VALUE_FOR_MARKING_SKIPPED_BLOCKS_IN_QUERY;
    }
}
