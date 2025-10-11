<?php

namespace App\Contracts;

use App\Models\User;

interface InteractWithLlm
{
    public function prompt(array $prismMessages, User $user): string;
}
