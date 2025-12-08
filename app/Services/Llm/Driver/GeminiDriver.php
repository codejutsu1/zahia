<?php

namespace App\Services\Llm\Driver;

use App\Contracts\InteractWithLlm;
use App\Models\User;
use App\Prism\Tools\Cart\CreateCartTool;
use App\Prism\Tools\Cart\ListCartTool;
use App\Prism\Tools\DeliveryAddress\CreateDeliveryAddressTool;
use App\Prism\Tools\Order\CheckoutOrderTool;
use App\Prism\Tools\Order\CreateOrderTool;
use App\Prism\Tools\Order\ListOrdersTool;
use App\Prism\Tools\Order\RepeatOrderTool;
use App\Prism\Tools\Product\ListProductsTool;
use App\Prism\Tools\Profile\UpdateEmailTool;
use App\Prism\Tools\Vendor\Product\CreateProductTool;
use App\Prism\Tools\Vendor\Product\DeleteProductTool;
use App\Prism\Tools\Vendor\Product\ListProductsTool as VendorListProductsTool;
use App\Prism\Tools\Vendor\Product\UpdateProductTool;
use App\Prism\Tools\Vendor\Profile\ToggleVisibilityTool;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;

class GeminiDriver implements InteractWithLlm
{
    /**
     * Create a new class instance.
     */
    public function __construct() {}

    public function prompt(array $prismMessages, User $user): string
    {
        return Prism::text()
            ->using(Provider::Gemini, 'gemini-2.5-flash')
            ->withTools([
                ListOrdersTool::make($user),
                ListProductsTool::make($user),
                CreateCartTool::make($user),
                ListCartTool::make($user),
                CheckoutOrderTool::make($user),
                CreateOrderTool::make($user),
                RepeatOrderTool::make($user),
                UpdateEmailTool::make($user),
                CreateDeliveryAddressTool::make($user),
            ])
            ->withMaxSteps(2)
            ->withMessages($prismMessages)
            ->asText()->text;
    }

    public function vendorPrompt(array $prismMessages, User $user): string
    {
        return Prism::text()
            ->using(Provider::Gemini, 'gemini-2.5-flash')
            ->withTools([
                VendorListProductsTool::make($user),
                CreateProductTool::make($user),
                UpdateProductTool::make($user),
                DeleteProductTool::make($user),
                ToggleVisibilityTool::make($user),
            ])
            ->withMaxSteps(2)
            ->withMessages($prismMessages)
            ->asText()->text;
    }
}
