<?php

namespace App\Providers;

use App\Services\Chatbot\ChatbotManager;
use App\Services\Llm\LlmManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LlmManager::class, function ($app) {
            return new LlmManager($app);
        });

        $this->app->singleton(ChatbotManager::class, function ($app) {
            return new ChatbotManager($app);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
