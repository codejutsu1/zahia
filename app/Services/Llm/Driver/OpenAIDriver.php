<?php

namespace App\Services\Llm\Driver;

use App\Contracts\InteractWithLlm;
use App\Models\User;
use App\Prism\Tools\Cart\CreateCartTool;
use App\Prism\Tools\Cart\ListCartTool;
use App\Prism\Tools\Order\CheckoutOrderTool;
use App\Prism\Tools\Order\CreateOrderTool;
use App\Prism\Tools\Order\ListOrdersTool;
use App\Prism\Tools\Order\RepeatOrderTool;
use App\Prism\Tools\Product\ListProductsTool;
use App\Prism\Tools\Profile\UpdateEmailTool;
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
        return Prism::text()
            ->using(Provider::OpenAI, 'gpt-4')
            ->withTools([
                ListOrdersTool::make($user),
                ListProductsTool::make($user),
                CreateCartTool::make($user),
                ListCartTool::make($user),
                CheckoutOrderTool::make($user),
                CreateOrderTool::make($user),
                RepeatOrderTool::make($user),
                UpdateEmailTool::make($user),
            ])
            ->withMaxSteps(2)
            ->withMessages($prismMessages)
            ->asText()->text;
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
