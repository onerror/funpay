<?php

namespace FpDbTest;

interface QueryPartInterface
{
    public function __construct(string $queryPartAsString);
    
    public function __toString(): string;
}