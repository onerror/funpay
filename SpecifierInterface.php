<?php

namespace FpDbTest;

interface SpecifierInterface extends QueryPartInterface
{
    public function __construct(string $queryPartAsString, mixed $value);
}