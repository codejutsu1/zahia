<?php

namespace App\Contracts;

interface InteractWithLlm
{
    public function prompt(string $prompt): string;
}
