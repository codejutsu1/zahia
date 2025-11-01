<?php

namespace App\Services\Llm\Driver;

use App\Contracts\InteractWithLlm;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;
use Prism\Prism\ValueObjects\Media\Audio;

class OpenAIDriver implements InteractWithLlm
{
    /**
     * Create a new class instance.
     */
    public function __construct() {}

    public function prompt(array $prismMessages, User $user): string
    {
        return '';
    }

    public function audio(string $path): string
    {
        $path = Storage::disk('public')->path($path);
        $audioFile = Audio::fromLocalPath($path);

        return Prism::audio()
            ->using(Provider::OpenAI, 'whisper-1')
            ->withInput($audioFile)
            ->asText()->text;
    }
}
