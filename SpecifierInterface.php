<?php

namespace FpDbTest;

interface SpecifierInterface extends QueryPartInterface
{
    public function formatParameterValue($value): string;
}