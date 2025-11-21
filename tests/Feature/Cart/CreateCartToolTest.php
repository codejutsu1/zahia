<?php

use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Prism\Tools\Cart\CreateCartTool;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;
use Prism\Prism\Testing\TextStepFake;
use Prism\Prism\Text\ResponseBuilder;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\ToolCall;
use Prism\Prism\ValueObjects\ToolResult;
use Prism\Prism\ValueObjects\Usage;

it('can use create_cart tool to add products to cart', function () {
    $user = User::factory()->create();

    $vendor = Vendor::factory()->create(['name' => 'Pizza Palace']);

    $product1 = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'name' => 'Margherita Pizza',
        'price' => 2500,
        'is_addon' => false,
    ]);

    $product2 = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'name' => 'Pepperoni Pizza',
        'price' => 3000,
        'is_addon' => false,
    ]);

    $toolArguments = [
        'cart' => [
            'cart_items' => [
                [
                    'vendor_name' => 'Pizza Palace',
                    'product' => [
                        'name' => 'margherita pizza',
                        'quantity' => '2',
                    ],
                ],
                [
                    'vendor_name' => 'Pizza Palace',
                    'product' => [
                        'name' => 'pepperoni pizza',
                        'quantity' => '1',
                    ],
                ],
            ],
        ],
    ];

    $expectedResult = 'Cart created successfully, do you want to add more products to your cart or checkout?';

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_123',
                            name: 'create_cart',
                            arguments: $toolArguments
                        ),
                    ])
                    ->withFinishReason(FinishReason::ToolCalls)
                    ->withUsage(new Usage(15, 25))
                    ->withMeta(new Meta('fake-1', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withText('Perfect! I\'ve added 2 Margherita Pizzas and 1 Pepperoni Pizza to your cart. Would you like to checkout now or add more items?')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_123',
                            toolName: 'create_cart',
                            args: $toolArguments,
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

    $createCartTool = CreateCartTool::make($user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Add 2 Margherita pizzas and 1 Pepperoni pizza from Pizza Palace to my cart')
        ->withTools([$createCartTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->steps)->toHaveCount(2);

    expect($response->steps[0]->toolCalls)->toHaveCount(1);
    expect($response->steps[0]->toolCalls[0]->name)->toBe('create_cart');
    expect($response->steps[0]->toolCalls[0]->arguments())->toBe($toolArguments);

    expect($response->toolResults)->toHaveCount(1);
    expect($response->toolResults[0]->result)->toBe($expectedResult);

    expect($response->text)
        ->toBe('Perfect! I\'ve added 2 Margherita Pizzas and 1 Pepperoni Pizza to your cart. Would you like to checkout now or add more items?');
});

it('handles vendor not found error when creating cart', function () {
    $user = User::factory()->create();

    Vendor::factory()->create(['name' => 'Pizza Palace']);

    $toolArguments = [
        'cart' => [
            'cart_items' => [
                [
                    'vendor_name' => 'Non Existent Restaurant',
                    'product' => [
                        'name' => 'burger',
                        'quantity' => '1',
                    ],
                ],
            ],
        ],
    ];

    $expectedResult = "Non Existent Restaurant doesn't exist in our system!, try another vendor?";

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_456',
                            name: 'create_cart',
                            arguments: $toolArguments
                        ),
                    ])
                    ->withFinishReason(FinishReason::ToolCalls)
                    ->withUsage(new Usage(15, 25))
                    ->withMeta(new Meta('fake-1', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withText('Sorry, Non Existent Restaurant is not available in our system. Would you like to try Pizza Palace instead?')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_456',
                            toolName: 'create_cart',
                            args: $toolArguments,
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

    $createCartTool = CreateCartTool::make($user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Add a burger from Non Existent Restaurant to my cart')
        ->withTools([$createCartTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->toolResults[0]->result)->toBe($expectedResult);
});

it('handles product not found error when creating cart', function () {
    $user = User::factory()->create();

    $vendor = Vendor::factory()->create(['name' => 'Pizza Palace']);
    Product::factory()->create([
        'vendor_id' => $vendor->id,
        'name' => 'Margherita Pizza',
        'price' => 2500.00,
    ]);

    $toolArguments = [
        'cart' => [
            'cart_items' => [
                [
                    'vendor_name' => 'Pizza Palace',
                    'product' => [
                        'name' => 'non existent pizza',
                        'quantity' => '1',
                    ],
                ],
            ],
        ],
    ];

    $expectedResult = 'Pizza Palace doesnt have this Non Existent Pizza, try another product?';

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_789',
                            name: 'create_cart',
                            arguments: $toolArguments
                        ),
                    ])
                    ->withFinishReason(FinishReason::ToolCalls)
                    ->withUsage(new Usage(15, 25))
                    ->withMeta(new Meta('fake-1', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withText('Sorry, Pizza Palace doesn\'t have Non Existent Pizza on their menu. They do have Margherita Pizza available. Would you like to order that instead?')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_789',
                            toolName: 'create_cart',
                            args: $toolArguments,
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

    $createCartTool = CreateCartTool::make($user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Add Non Existent Pizza from Pizza Palace to my cart')
        ->withTools([$createCartTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->toolResults[0]->result)->toBe($expectedResult);
});

it('can create cart with products from multiple vendors', function () {
    $user = User::factory()->create();

    $vendor1 = Vendor::factory()->create(['name' => 'Pizza Palace']);
    $vendor2 = Vendor::factory()->create(['name' => 'Burger Joint']);

    $product1 = Product::factory()->create([
        'vendor_id' => $vendor1->id,
        'name' => 'Margherita Pizza',
        'price' => 2500.00,
        'is_addon' => false,
    ]);
    $product2 = Product::factory()->create([
        'vendor_id' => $vendor2->id,
        'name' => 'Classic Burger',
        'price' => 1500.00,
        'is_addon' => false,
    ]);

    $toolArguments = [
        'cart' => [
            'cart_items' => [
                [
                    'vendor_name' => 'Pizza Palace',
                    'product' => [
                        'name' => 'margherita pizza',
                        'quantity' => '1',
                    ],
                ],
                [
                    'vendor_name' => 'Burger Joint',
                    'product' => [
                        'name' => 'classic burger',
                        'quantity' => '2',
                    ],
                ],
            ],
        ],
    ];

    $expectedResult = 'Cart created successfully, do you want to add more products to your cart or checkout?';

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_multi',
                            name: 'create_cart',
                            arguments: $toolArguments
                        ),
                    ])
                    ->withFinishReason(FinishReason::ToolCalls)
                    ->withUsage(new Usage(15, 25))
                    ->withMeta(new Meta('fake-1', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withText('Great! I\'ve added items from both Pizza Palace and Burger Joint to your cart. Ready to checkout?')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_multi',
                            toolName: 'create_cart',
                            args: $toolArguments,
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

    $createCartTool = CreateCartTool::make($user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Add 1 Margherita pizza from Pizza Palace and 2 Classic burgers from Burger Joint')
        ->withTools([$createCartTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->toolResults[0]->result)->toBe($expectedResult);
});

it('handles addon products correctly', function () {
    $user = User::factory()->create();

    $vendor = Vendor::factory()->create(['name' => 'Pizza Palace']);

    $mainProduct = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'name' => 'Large Pizza',
        'price' => 3500.00,
        'is_addon' => false,
    ]);
    $addonProduct = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'name' => 'Extra Cheese',
        'price' => 500.00,
        'is_addon' => true,
    ]);

    $toolArguments = [
        'cart' => [
            'cart_items' => [
                [
                    'vendor_name' => 'Pizza Palace',
                    'product' => [
                        'name' => 'large pizza',
                        'quantity' => '1',
                    ],
                ],
                [
                    'vendor_name' => 'Pizza Palace',
                    'product' => [
                        'name' => 'extra cheese',
                        'quantity' => '1',
                    ],
                ],
            ],
        ],
    ];

    $expectedResult = 'Cart created successfully, do you want to add more products to your cart or checkout?';

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_addon',
                            name: 'create_cart',
                            arguments: $toolArguments
                        ),
                    ])
                    ->withFinishReason(FinishReason::ToolCalls)
                    ->withUsage(new Usage(15, 25))
                    ->withMeta(new Meta('fake-1', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withText('I\'ve added a Large Pizza with Extra Cheese to your cart.')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_addon',
                            toolName: 'create_cart',
                            args: $toolArguments,
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

    $createCartTool = CreateCartTool::make($user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Add a Large Pizza with Extra Cheese from Pizza Palace')
        ->withTools([$createCartTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->toolResults[0]->result)->toBe($expectedResult);
});
