<?php

namespace FpDbTest;

class SelectQueryBuilder extends AbstractQueryBuilder
{
    protected function postProcessQuery(string $query): string
    {
        $result = parent::postProcessQuery($query);
        // Этот код здесь просто для иллюстрации. Я создавал новые тест кейсы для проверки этого метода, но не стал
        // добавлять их в DataBaseTest.php, потому что непонятно, могу ли я вносить правки в тот класс.
        // Если = NULL не нужно менять на IS NULL, то две следующие строки надо будет просто удалить
        $result = preg_replace('/\s*(<>|!=|<|>)\s*NULL/', ' IS NOT NULL', $result);
        $result = preg_replace('/\s*=\s*NULL/', ' IS NULL', $result);
        
        return $result;
    }
    
}