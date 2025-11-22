<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Prism\Tools\Product\ListProductsTool;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;
use Prism\Prism\Testing\TextStepFake;
use Prism\Prism\Text\ResponseBuilder;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\ToolCall;
use Prism\Prism\ValueObjects\ToolResult;
use Prism\Prism\ValueObjects\Usage;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('can list products by name', function () {
    $vendor = Vendor::factory()->create(['name' => 'Tasty Foods']);

    Product::factory()->create([
        'name' => 'Jollof Rice',
        'price' => 1500.00,
        'vendor_id' => $vendor->id,
        'status' => ProductStatus::ACTIVE,
    ]);

    Product::factory()->create([
        'name' => 'Fried Rice',
        'price' => 1200.00,
        'vendor_id' => $vendor->id,
        'status' => ProductStatus::ACTIVE,
    ]);

    $expectedResult = "🍴 *Tasty Foods*\n📦 Jollof Rice - 💰 ₦1,500.00\n📦 Fried Rice - 💰 ₦1,200.00\n\n";

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_list_products_1',
                            name: 'list_products',
                            arguments: [
                                'product' => [
                                    'name' => 'Rice',
                                ],
                            ]
                        ),
                    ])
                    ->withFinishReason(FinishReason::ToolCalls)
                    ->withUsage(new Usage(20, 30))
                    ->withMeta(new Meta('fake-1', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withText('I found 2 rice dishes from Tasty Foods: Jollof Rice for ₦1,500.00 and Fried Rice for ₦1,200.00.')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_list_products_1',
                            toolName: 'list_products',
                            args: [
                                'product' => [
                                    'name' => 'Rice',
                                ],
                            ],
                            result: $expectedResult
                        ),
                    ])
                    ->withFinishReason(FinishReason::Stop)
                    ->withUsage(new Usage(35, 45))
                    ->withMeta(new Meta('fake-2', 'fake-model')),
            )
            ->toResponse(),
    ];

    Prism::fake($responses);

    $listProductsTool = ListProductsTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Show me rice dishes')
        ->withTools([$listProductsTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->steps)->toHaveCount(2);

    expect($response->steps[0]->toolCalls)->toHaveCount(1);
    expect($response->steps[0]->toolCalls[0]->name)->toBe('list_products');
    expect($response->steps[0]->toolCalls[0]->arguments())->toBe([
        'product' => [
            'name' => 'Rice',
        ],
    ]);

    expect($response->toolResults)->toHaveCount(1);
    expect($response->toolResults[0]->result)->toBe($expectedResult);

    expect($response->text)->toContain('Jollof Rice');
    expect($response->text)->toContain('Fried Rice');
});

it('can filter products by vendor name', function () {
    $vendor1 = Vendor::factory()->create(['name' => 'Tasty Foods']);
    $vendor2 = Vendor::factory()->create(['name' => 'Quick Bites']);

    Product::factory()->create([
        'name' => 'Jollof Rice',
        'price' => 1500.00,
        'vendor_id' => $vendor1->id,
        'status' => ProductStatus::ACTIVE,
    ]);

    Product::factory()->create([
        'name' => 'Fried Rice',
        'price' => 1200.00,
        'vendor_id' => $vendor2->id,
        'status' => ProductStatus::ACTIVE,
    ]);

    $expectedResult = "🍴 *Tasty Foods*\n📦 Jollof Rice - 💰 ₦1,500.00\n\n";

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_vendor_filter',
                            name: 'list_products',
                            arguments: [
                                'product' => [
                                    'name' => 'Rice',
                                    'vendor_name' => 'Tasty',
                                ],
                            ]
                        ),
                    ])
                    ->withFinishReason(FinishReason::ToolCalls)
                    ->withUsage(new Usage(25, 35))
                    ->withMeta(new Meta('fake-1', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withText('From Tasty Foods, I found Jollof Rice for ₦1,500.00.')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_vendor_filter',
                            toolName: 'list_products',
                            args: [
                                'product' => [
                                    'name' => 'Rice',
                                    'vendor_name' => 'Tasty',
                                ],
                            ],
                            result: $expectedResult
                        ),
                    ])
                    ->withFinishReason(FinishReason::Stop)
                    ->withUsage(new Usage(30, 40))
                    ->withMeta(new Meta('fake-2', 'fake-model')),
            )
            ->toResponse(),
    ];

    Prism::fake($responses);

    $listProductsTool = ListProductsTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Show me rice dishes from Tasty Foods')
        ->withTools([$listProductsTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->steps)->toHaveCount(2);
    expect($response->steps[0]->toolCalls[0]->name)->toBe('list_products');
    expect($response->steps[0]->toolCalls[0]->arguments())->toBe([
        'product' => [
            'name' => 'Rice',
            'vendor_name' => 'Tasty',
        ],
    ]);
    expect($response->toolResults[0]->result)->toBe($expectedResult);
    expect($response->text)->toContain('Tasty Foods');
    expect($response->text)->toContain('Jollof Rice');
});

it('can filter products by price with less than operator', function () {
    $vendor = Vendor::factory()->create(['name' => 'Budget Meals']);

    Product::factory()->create([
        'name' => 'Jollof Rice',
        'price' => 1500.00,
        'vendor_id' => $vendor->id,
        'status' => ProductStatus::ACTIVE,
    ]);

    Product::factory()->create([
        'name' => 'Fried Rice',
        'price' => 1200.00,
        'vendor_id' => $vendor->id,
        'status' => ProductStatus::ACTIVE,
    ]);

    Product::factory()->create([
        'name' => 'White Rice',
        'price' => 800.00,
        'vendor_id' => $vendor->id,
        'status' => ProductStatus::ACTIVE,
    ]);

    $expectedResult = "🍴 *Budget Meals*\n📦 Fried Rice - 💰 ₦1,200.00\n📦 White Rice - 💰 ₦800.00\n\n";

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_price_filter',
                            name: 'list_products',
                            arguments: [
                                'product' => [
                                    'name' => 'Rice',
                                    'price' => '<1300',
                                ],
                            ]
                        ),
                    ])
                    ->withFinishReason(FinishReason::ToolCalls)
                    ->withUsage(new Usage(22, 32))
                    ->withMeta(new Meta('fake-1', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withText('I found 2 rice dishes under ₦1,300: Fried Rice (₦1,200.00) and White Rice (₦800.00).')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_price_filter',
                            toolName: 'list_products',
                            args: [
                                'product' => [
                                    'name' => 'Rice',
                                    'price' => '<1300',
                                ],
                            ],
                            result: $expectedResult
                        ),
                    ])
                    ->withFinishReason(FinishReason::Stop)
                    ->withUsage(new Usage(40, 50))
                    ->withMeta(new Meta('fake-2', 'fake-model')),
            )
            ->toResponse(),
    ];

    Prism::fake($responses);

    $listProductsTool = ListProductsTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Show me rice dishes under ₦1,300')
        ->withTools([$listProductsTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->steps)->toHaveCount(2);
    expect($response->steps[0]->toolCalls[0]->arguments()['product']['price'])->toBe('<1300');
    expect($response->toolResults[0]->result)->toBe($expectedResult);
    expect($response->text)->toContain('Fried Rice');
    expect($response->text)->toContain('White Rice');
});

it('can filter products with greater than or equal operator', function () {
    $vendor = Vendor::factory()->create(['name' => 'Premium Foods']);

    Product::factory()->create([
        'name' => 'Jollof Rice',
        'price' => 1500.00,
        'vendor_id' => $vendor->id,
        'status' => ProductStatus::ACTIVE,
    ]);

    Product::factory()->create([
        'name' => 'Fried Rice',
        'price' => 1200.00,
        'vendor_id' => $vendor->id,
        'status' => ProductStatus::ACTIVE,
    ]);

    Product::factory()->create([
        'name' => 'Special Rice',
        'price' => 2000.00,
        'vendor_id' => $vendor->id,
        'status' => ProductStatus::ACTIVE,
    ]);

    $expectedResult = "🍴 *Premium Foods*\n📦 Jollof Rice - 💰 ₦1,500.00\n📦 Special Rice - 💰 ₦2,000.00\n\n";

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_gte_filter',
                            name: 'list_products',
                            arguments: [
                                'product' => [
                                    'name' => 'Rice',
                                    'price' => '>=1500',
                                ],
                            ]
                        ),
                    ])
                    ->withFinishReason(FinishReason::ToolCalls)
                    ->withUsage(new Usage(23, 33))
                    ->withMeta(new Meta('fake-1', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withText('Premium rice dishes ₦1,500 and above: Jollof Rice (₦1,500.00) and Special Rice (₦2,000.00).')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_gte_filter',
                            toolName: 'list_products',
                            args: [
                                'product' => [
                                    'name' => 'Rice',
                                    'price' => '>=1500',
                                ],
                            ],
                            result: $expectedResult
                        ),
                    ])
                    ->withFinishReason(FinishReason::Stop)
                    ->withUsage(new Usage(38, 48))
                    ->withMeta(new Meta('fake-2', 'fake-model')),
            )
            ->toResponse(),
    ];

    Prism::fake($responses);

    $listProductsTool = ListProductsTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Show me premium rice dishes ₦1,500 or more')
        ->withTools([$listProductsTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->steps)->toHaveCount(2);
    expect($response->steps[0]->toolCalls[0]->arguments()['product']['price'])->toBe('>=1500');
    expect($response->toolResults[0]->result)->toBe($expectedResult);
    expect($response->text)->toContain('Jollof Rice');
    expect($response->text)->toContain('Special Rice');
});

it('can combine name, vendor, and price filters', function () {
    $vendor1 = Vendor::factory()->create(['name' => 'Tasty Foods']);
    $vendor2 = Vendor::factory()->create(['name' => 'Quick Bites']);

    Product::factory()->create([
        'name' => 'Jollof Rice',
        'price' => 1500.00,
        'vendor_id' => $vendor1->id,
        'status' => ProductStatus::ACTIVE,
    ]);

    Product::factory()->create([
        'name' => 'Fried Rice',
        'price' => 1200.00,
        'vendor_id' => $vendor1->id,
        'status' => ProductStatus::ACTIVE,
    ]);

    Product::factory()->create([
        'name' => 'Jollof Rice',
        'price' => 1800.00,
        'vendor_id' => $vendor2->id,
        'status' => ProductStatus::ACTIVE,
    ]);

    $expectedResult = "🍴 *Tasty Foods*\n📦 Jollof Rice - 💰 ₦1,500.00\n\n";

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_combined_filter',
                            name: 'list_products',
                            arguments: [
                                'product' => [
                                    'name' => 'Jollof',
                                    'vendor_name' => 'Tasty',
                                    'price' => '<1600',
                                ],
                            ]
                        ),
                    ])
                    ->withFinishReason(FinishReason::ToolCalls)
                    ->withUsage(new Usage(28, 38))
                    ->withMeta(new Meta('fake-1', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withText('From Tasty Foods, I found Jollof Rice under ₦1,600 for ₦1,500.00.')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_combined_filter',
                            toolName: 'list_products',
                            args: [
                                'product' => [
                                    'name' => 'Jollof',
                                    'vendor_name' => 'Tasty',
                                    'price' => '<1600',
                                ],
                            ],
                            result: $expectedResult
                        ),
                    ])
                    ->withFinishReason(FinishReason::Stop)
                    ->withUsage(new Usage(42, 52))
                    ->withMeta(new Meta('fake-2', 'fake-model')),
            )
            ->toResponse(),
    ];

    Prism::fake($responses);

    $listProductsTool = ListProductsTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Show me Jollof Rice from Tasty Foods under ₦1,600')
        ->withTools([$listProductsTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->steps)->toHaveCount(2);
    expect($response->steps[0]->toolCalls[0]->arguments())->toBe([
        'product' => [
            'name' => 'Jollof',
            'vendor_name' => 'Tasty',
            'price' => '<1600',
        ],
    ]);
    expect($response->toolResults[0]->result)->toBe($expectedResult);
    expect($response->text)->toContain('Tasty Foods');
    expect($response->text)->toContain('Jollof Rice');
});

it('returns empty result when no products match', function () {
    $vendor = Vendor::factory()->create(['name' => 'Empty Vendor']);

    Product::factory()->create([
        'name' => 'Jollof Rice',
        'price' => 1500.00,
        'vendor_id' => $vendor->id,
        'status' => ProductStatus::ACTIVE,
    ]);

    $expectedResult = '';

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_no_match',
                            name: 'list_products',
                            arguments: [
                                'product' => [
                                    'name' => 'Pizza',
                                ],
                            ]
                        ),
                    ])
                    ->withFinishReason(FinishReason::ToolCalls)
                    ->withUsage(new Usage(18, 28))
                    ->withMeta(new Meta('fake-1', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withText('I couldn\'t find any products matching "Pizza" in our system.')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_no_match',
                            toolName: 'list_products',
                            args: [
                                'product' => [
                                    'name' => 'Pizza',
                                ],
                            ],
                            result: $expectedResult
                        ),
                    ])
                    ->withFinishReason(FinishReason::Stop)
                    ->withUsage(new Usage(25, 35))
                    ->withMeta(new Meta('fake-2', 'fake-model')),
            )
            ->toResponse(),
    ];

    Prism::fake($responses);

    $listProductsTool = ListProductsTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Show me Pizza')
        ->withTools([$listProductsTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->steps)->toHaveCount(2);
    expect($response->toolResults[0]->result)->toBe('');
    expect($response->text)->toContain('couldn\'t find');
});

it('only returns active products', function () {
    $vendor = Vendor::factory()->create(['name' => 'Status Test']);

    Product::factory()->create([
        'name' => 'Active Rice',
        'price' => 1000.00,
        'vendor_id' => $vendor->id,
        'status' => ProductStatus::ACTIVE,
    ]);

    Product::factory()->create([
        'name' => 'Inactive Rice',
        'price' => 1000.00,
        'vendor_id' => $vendor->id,
        'status' => ProductStatus::OUT_OF_STOCK,
    ]);

    $expectedResult = "🍴 *Status Test*\n📦 Active Rice - 💰 ₦1,000.00\n\n";

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_active_only',
                            name: 'list_products',
                            arguments: [
                                'product' => [
                                    'name' => 'Rice',
                                ],
                            ]
                        ),
                    ])
                    ->withFinishReason(FinishReason::ToolCalls)
                    ->withUsage(new Usage(19, 29))
                    ->withMeta(new Meta('fake-1', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withText('I found Active Rice for ₦1,000.00.')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_active_only',
                            toolName: 'list_products',
                            args: [
                                'product' => [
                                    'name' => 'Rice',
                                ],
                            ],
                            result: $expectedResult
                        ),
                    ])
                    ->withFinishReason(FinishReason::Stop)
                    ->withUsage(new Usage(28, 38))
                    ->withMeta(new Meta('fake-2', 'fake-model')),
            )
            ->toResponse(),
    ];

    Prism::fake($responses);

    $listProductsTool = ListProductsTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Show me rice products')
        ->withTools([$listProductsTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->steps)->toHaveCount(2);
    expect($response->toolResults[0]->result)->toBe($expectedResult);
    expect($response->toolResults[0]->result)->toContain('Active Rice');
    expect($response->toolResults[0]->result)->not->toContain('Inactive Rice');
});

it('groups products by vendor', function () {
    $vendor1 = Vendor::factory()->create(['name' => 'Vendor A']);
    $vendor2 = Vendor::factory()->create(['name' => 'Vendor B']);

    Product::factory()->create([
        'name' => 'Product 1',
        'price' => 1000.00,
        'vendor_id' => $vendor1->id,
        'status' => ProductStatus::ACTIVE,
    ]);

    Product::factory()->create([
        'name' => 'Product 2',
        'price' => 1200.00,
        'vendor_id' => $vendor1->id,
        'status' => ProductStatus::ACTIVE,
    ]);

    Product::factory()->create([
        'name' => 'Product 3',
        'price' => 1500.00,
        'vendor_id' => $vendor2->id,
        'status' => ProductStatus::ACTIVE,
    ]);

    $expectedResult = "🍴 *Vendor A*\n📦 Product 1 - 💰 ₦1,000.00\n📦 Product 2 - 💰 ₦1,200.00\n\n🍴 *Vendor B*\n📦 Product 3 - 💰 ₦1,500.00\n\n";

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_grouped',
                            name: 'list_products',
                            arguments: [
                                'product' => [
                                    'name' => 'Product',
                                ],
                            ]
                        ),
                    ])
                    ->withFinishReason(FinishReason::ToolCalls)
                    ->withUsage(new Usage(21, 31))
                    ->withMeta(new Meta('fake-1', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withText('I found products from two vendors: Vendor A has Product 1 and Product 2, while Vendor B has Product 3.')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_grouped',
                            toolName: 'list_products',
                            args: [
                                'product' => [
                                    'name' => 'Product',
                                ],
                            ],
                            result: $expectedResult
                        ),
                    ])
                    ->withFinishReason(FinishReason::Stop)
                    ->withUsage(new Usage(45, 55))
                    ->withMeta(new Meta('fake-2', 'fake-model')),
            )
            ->toResponse(),
    ];

    Prism::fake($responses);

    $listProductsTool = ListProductsTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Show me all products')
        ->withTools([$listProductsTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->steps)->toHaveCount(2);
    expect($response->toolResults[0]->result)->toContain('🍴 *Vendor A*');
    expect($response->toolResults[0]->result)->toContain('🍴 *Vendor B*');
    expect($response->toolResults[0]->result)->toContain('Product 1');
    expect($response->toolResults[0]->result)->toContain('Product 2');
    expect($response->toolResults[0]->result)->toContain('Product 3');
});
