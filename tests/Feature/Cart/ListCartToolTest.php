<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Prism\Tools\Cart\ListCartTool;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;
use Prism\Prism\Testing\TextStepFake;
use Prism\Prism\Text\ResponseBuilder;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\ToolCall;
use Prism\Prism\ValueObjects\ToolResult;
use Prism\Prism\ValueObjects\Usage;

it('can use list_carts tool to display cart items', function () {
    $user = User::factory()->create();

    $vendor1 = Vendor::factory()->create(['name' => 'Pizza Palace']);
    $vendor2 = Vendor::factory()->create(['name' => 'Burger Joint']);

    $product1 = Product::factory()->create([
        'vendor_id' => $vendor1->id,
        'name' => 'Margherita Pizza',
        'price' => 2500,
    ]);
    $product2 = Product::factory()->create([
        'vendor_id' => $vendor1->id,
        'name' => 'Pepperoni Pizza',
        'price' => 3000,
    ]);
    $product3 = Product::factory()->create([
        'vendor_id' => $vendor2->id,
        'name' => 'Classic Burger',
        'price' => 1500,
    ]);

    $cart1 = Cart::factory()->create(['user_id' => $user->id]);
    $cart2 = Cart::factory()->create(['user_id' => $user->id]);

    CartItem::factory()->create([
        'cart_id' => $cart1->id,
        'product_id' => $product1->id,
        'quantity' => 2,
    ]);

    CartItem::factory()->create([
        'cart_id' => $cart1->id,
        'product_id' => $product2->id,
        'quantity' => 1,
    ]);

    CartItem::factory()->create([
        'cart_id' => $cart2->id,
        'product_id' => $product3->id,
        'quantity' => 3,
    ]);

    $expectedResult = "🍴 *Pizza Palace*\n".
        "📦 Margherita Pizza (₦2,500.00 x2) - 💰 ₦5,000.00\n".
        "📦 Pepperoni Pizza (₦3,000.00 x1) - 💰 ₦3,000.00\n".
        "🧾 *Subtotal:* ₦8,000.00\n\n".
        "🍴 *Burger Joint*\n".
        "📦 Classic Burger (₦1,500.00 x3) - 💰 ₦4,500.00\n".
        "🧾 *Subtotal:* ₦4,500.00\n\n".
        "💳 *Cart Total:* ₦12,500.00\n\n\n".
        'Do you want to checkout or add more items to your cart?';

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_123',
                            name: 'list_carts',
                            arguments: ['carts' => []]
                        ),
                    ])
                    ->withFinishReason(FinishReason::ToolCalls)
                    ->withUsage(new Usage(15, 25))
                    ->withMeta(new Meta('fake-1', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withText('You have items from 2 vendors in your cart. Your total is ₦12,500.00. Would you like to proceed with checkout?')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_123',
                            toolName: 'list_carts',
                            args: ['carts' => []],
                            result: $expectedResult
                        ),
                    ])
                    ->withFinishReason(FinishReason::Stop)
                    ->withUsage(new Usage(20, 30))
                    ->withMeta(new Meta('fake-2', 'fake-model')),
            )
            ->toResponse(),
    ];

    Prism::fake($responses);

    $listCartTool = ListCartTool::make($user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Show me my cart')
        ->withTools([$listCartTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->steps)->toHaveCount(2);

    expect($response->steps[0]->toolCalls)->toHaveCount(1);
    expect($response->steps[0]->toolCalls[0]->name)->toBe('list_carts');
    expect($response->steps[0]->toolCalls[0]->arguments())->toBe(['carts' => []]);

    expect($response->toolResults)->toHaveCount(1);
    expect($response->toolResults[0]->result)->toBe($expectedResult);

    expect($response->text)
        ->toBe('You have items from 2 vendors in your cart. Your total is ₦12,500.00. Would you like to proceed with checkout?');
});

it('can use list_carts tool filtered by vendor name', function () {
    $user = User::factory()->create();

    $vendor1 = Vendor::factory()->create(['name' => 'Pizza Palace']);
    $vendor2 = Vendor::factory()->create(['name' => 'Burger Joint']);

    $product1 = Product::factory()->create([
        'vendor_id' => $vendor1->id,
        'name' => 'Margherita Pizza',
        'price' => 2500.00,
    ]);
    $product2 = Product::factory()->create([
        'vendor_id' => $vendor2->id,
        'name' => 'Classic Burger',
        'price' => 1500.00,
    ]);

    $cart1 = Cart::factory()->create(['user_id' => $user->id]);
    $cart2 = Cart::factory()->create(['user_id' => $user->id]);

    CartItem::factory()->create([
        'cart_id' => $cart1->id,
        'product_id' => $product1->id,
        'quantity' => 2,
    ]);
    CartItem::factory()->create([
        'cart_id' => $cart2->id,
        'product_id' => $product2->id,
        'quantity' => 1,
    ]);

    $expectedResult = "🍴 *Pizza Palace*\n".
        "📦 Margherita Pizza (₦2,500.00 x2) - 💰 ₦5,000.00\n".
        "🧾 *Subtotal:* ₦5,000.00\n\n".
        "💳 *Cart Total:* ₦5,000.00\n\n\n".
        'Do you want to checkout or add more items to your cart?';

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_456',
                            name: 'list_carts',
                            arguments: ['carts' => ['vendor_name' => 'Pizza Palace']]
                        ),
                    ])
                    ->withFinishReason(FinishReason::ToolCalls)
                    ->withUsage(new Usage(15, 25))
                    ->withMeta(new Meta('fake-1', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withText('You have 2 Margherita Pizzas from Pizza Palace in your cart for ₦5,000.00.')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_456',
                            toolName: 'list_carts',
                            args: ['carts' => ['vendor_name' => 'Pizza Palace']],
                            result: $expectedResult
                        ),
                    ])
                    ->withFinishReason(FinishReason::Stop)
                    ->withUsage(new Usage(20, 30))
                    ->withMeta(new Meta('fake-2', 'fake-model')),
            )
            ->toResponse(),
    ];

    Prism::fake($responses);

    $listCartsTool = ListCartTool::make($user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Show me my cart items from Pizza Palace')
        ->withTools([$listCartsTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->steps)->toHaveCount(2);
    expect($response->steps[0]->toolCalls[0]->name)->toBe('list_carts');
    expect($response->steps[0]->toolCalls[0]->arguments())->toBe(['carts' => ['vendor_name' => 'Pizza Palace']]);
    expect($response->toolResults[0]->result)->toBe($expectedResult);
});

it('returns empty cart message when cart is empty', function () {
    $user = User::factory()->create();

    $expectedResult = 'Your cart is empty.';

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_789',
                            name: 'list_carts',
                            arguments: ['carts' => []]
                        ),
                    ])
                    ->withFinishReason(FinishReason::ToolCalls)
                    ->withUsage(new Usage(15, 25))
                    ->withMeta(new Meta('fake-1', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withText('Your cart is currently empty. Would you like to browse products?')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_789',
                            toolName: 'list_carts',
                            args: ['carts' => []],
                            result: $expectedResult
                        ),
                    ])
                    ->withFinishReason(FinishReason::Stop)
                    ->withUsage(new Usage(20, 30))
                    ->withMeta(new Meta('fake-2', 'fake-model')),
            )
            ->toResponse(),
    ];

    Prism::fake($responses);

    $listCartsTool = ListCartTool::make($user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Show me my cart')
        ->withTools([$listCartsTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->toolResults[0]->result)->toBe('Your cart is empty.');
});
