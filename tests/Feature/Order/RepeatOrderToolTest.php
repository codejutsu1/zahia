<?php

use App\Models\User;
use App\Prism\Tools\Order\RepeatOrderTool;
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
    $this->user = User::factory()->create([
        'email' => 'customer@example.com',
    ]);
});

it('can repeat an order with confirmation', function () {
    $expectedResult = "🍴 *Tasty Foods*\n"
        ."📦 Jollof Rice (₦1,500.00 x1) = 💰 ₦1,500.00\n\n"
        ."📦 Fried Rice (₦1,200.00 x1) = 💰 ₦1,200.00\n\n"
        ."💳 *Total Amount:* ₦2,700.00\n\n"
        .'Do you want to checkout or add more items to your cart?';

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withText('I can repeat your order ORD12345. Would you like me to proceed?')
                    ->withFinishReason(FinishReason::Stop)
                    ->withUsage(new Usage(20, 30))
                    ->withMeta(new Meta('fake-1', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_repeat_order_1',
                            name: 'repeat_order',
                            arguments: [
                                'order' => [
                                    'id' => 'ORD12345',
                                ],
                            ]
                        ),
                    ])
                    ->withFinishReason(FinishReason::ToolCalls)
                    ->withUsage(new Usage(25, 35))
                    ->withMeta(new Meta('fake-2', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withText('Perfect! I\'ve added your previous order to your cart. The items are Jollof Rice and Fried Rice from Tasty Foods (Total: ₦2,700.00). Would you like to checkout or add more items?')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_repeat_order_1',
                            toolName: 'repeat_order',
                            args: [
                                'order' => [
                                    'id' => 'ORD12345',
                                ],
                            ],
                            result: $expectedResult
                        ),
                    ])
                    ->withFinishReason(FinishReason::Stop)
                    ->withUsage(new Usage(50, 60))
                    ->withMeta(new Meta('fake-3', 'fake-model')),
            )
            ->toResponse(),
    ];

    Prism::fake($responses);

    $repeatOrderTool = RepeatOrderTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('I want to repeat my order ORD12345')
        ->withTools([$repeatOrderTool])
        ->withMaxSteps(3)
        ->asText();

    expect($response->steps)->toHaveCount(3);
    expect($response->steps[0]->text)->toContain('Would you like me to proceed');
    expect($response->steps[1]->toolCalls[0]->name)->toBe('repeat_order');
    expect($response->steps[1]->toolCalls[0]->arguments())->toBe([
        'order' => [
            'id' => 'ORD12345',
        ],
    ]);
    expect($response->toolResults[0]->result)->toBe($expectedResult);
    expect($response->text)->toContain('checkout');
});

it('repeats order directly without confirmation when user is explicit', function () {
    $expectedResult = "🍴 *Quick Bites*\n"
        ."📦 Burger (₦2,500.00 x2) = 💰 ₦5,000.00\n\n"
        ."💳 *Total Amount:* ₦5,000.00\n\n"
        .'Do you want to checkout or add more items to your cart?';

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_repeat_direct',
                            name: 'repeat_order',
                            arguments: [
                                'order' => [
                                    'id' => 'ORD67890',
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
                    ->withText('Done! Your order has been added to your cart. Total: ₦5,000.00. Do you want to checkout?')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_repeat_direct',
                            toolName: 'repeat_order',
                            args: [
                                'order' => [
                                    'id' => 'ORD67890',
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

    $repeatOrderTool = RepeatOrderTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Repeat order ORD67890')
        ->withTools([$repeatOrderTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->steps)->toHaveCount(2);
    expect($response->steps[0]->toolCalls[0]->name)->toBe('repeat_order');
    expect($response->toolResults[0]->result)->toContain('Quick Bites');
});

it('handles incorrect order ID gracefully', function () {
    $expectedResult = 'The order ID is incorrect, please try again.';

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_wrong_id',
                            name: 'repeat_order',
                            arguments: [
                                'order' => [
                                    'id' => 'INVALID123',
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
                    ->withText('I couldn\'t find an order with ID INVALID123. Could you please check the order ID and try again?')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_wrong_id',
                            toolName: 'repeat_order',
                            args: [
                                'order' => [
                                    'id' => 'INVALID123',
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

    $repeatOrderTool = RepeatOrderTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Repeat order INVALID123')
        ->withTools([$repeatOrderTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->steps)->toHaveCount(2);
    expect($response->toolResults[0]->result)->toBe($expectedResult);
    expect($response->text)->toContain('couldn\'t find');
});

it('converts order ID to uppercase', function () {
    $expectedResult = "🍴 *Food Palace*\n"
        ."📦 Pizza (₦3,000.00 x1) = 💰 ₦3,000.00\n\n"
        ."💳 *Total Amount:* ₦3,000.00\n\n"
        .'Do you want to checkout or add more items to your cart?';

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_lowercase_id',
                            name: 'repeat_order',
                            arguments: [
                                'order' => [
                                    'id' => 'ord99999', // lowercase
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
                    ->withText('Your order has been added to your cart successfully!')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_lowercase_id',
                            toolName: 'repeat_order',
                            args: [
                                'order' => [
                                    'id' => 'ord99999',
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

    $repeatOrderTool = RepeatOrderTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Repeat order ord99999')
        ->withTools([$repeatOrderTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->steps)->toHaveCount(2);
    expect($response->steps[0]->toolCalls[0]->arguments()['order']['id'])->toBe('ord99999');
    expect($response->toolResults[0]->result)->toContain('Food Palace');
});

it('displays multiple items with quantities and prices', function () {
    $expectedResult = "🍴 *Premium Meals*\n"
        ."📦 Jollof Rice (₦1,500.00 x3) = 💰 ₦4,500.00\n\n"
        ."📦 Fried Rice (₦1,200.00 x2) = 💰 ₦2,400.00\n\n"
        ."📦 Chicken (₦2,000.00 x1) = 💰 ₦2,000.00\n\n"
        ."💳 *Total Amount:* ₦8,900.00\n\n"
        .'Do you want to checkout or add more items to your cart?';

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_multiple_items',
                            name: 'repeat_order',
                            arguments: [
                                'order' => [
                                    'id' => 'ORD88888',
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
                    ->withText('Great! I\'ve added 3 Jollof Rice, 2 Fried Rice, and 1 Chicken to your cart. Total: ₦8,900.00.')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_multiple_items',
                            toolName: 'repeat_order',
                            args: [
                                'order' => [
                                    'id' => 'ORD88888',
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

    $repeatOrderTool = RepeatOrderTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Repeat my order ORD88888')
        ->withTools([$repeatOrderTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->steps)->toHaveCount(2);
    expect($response->toolResults[0]->result)->toContain('x3');
    expect($response->toolResults[0]->result)->toContain('x2');
    expect($response->toolResults[0]->result)->toContain('x1');
    expect($response->toolResults[0]->result)->toContain('₦8,900.00');
});

it('asks if user wants to checkout or add more items', function () {
    $expectedResult = "🍴 *Tasty Foods*\n"
        ."📦 Jollof Rice (₦1,500.00 x1) = 💰 ₦1,500.00\n\n"
        ."💳 *Total Amount:* ₦1,500.00\n\n"
        .'Do you want to checkout or add more items to your cart?';

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_checkout_prompt',
                            name: 'repeat_order',
                            arguments: [
                                'order' => [
                                    'id' => 'ORD11111',
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
                    ->withText('Your order has been added to your cart. Do you want to checkout or add more items?')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_checkout_prompt',
                            toolName: 'repeat_order',
                            args: [
                                'order' => [
                                    'id' => 'ORD11111',
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

    $repeatOrderTool = RepeatOrderTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Repeat order ORD11111')
        ->withTools([$repeatOrderTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->steps)->toHaveCount(2);
    expect($response->toolResults[0]->result)->toContain('Do you want to checkout or add more items');
});

it('handles repeat order error gracefully', function () {
    $errorResult = 'Error repeating order';

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_error',
                            name: 'repeat_order',
                            arguments: [
                                'order' => [
                                    'id' => 'ERROR123',
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
                    ->withText('I encountered an error while repeating your order. Please try again or contact support.')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_error',
                            toolName: 'repeat_order',
                            args: [
                                'order' => [
                                    'id' => 'ERROR123',
                                ],
                            ],
                            result: $errorResult
                        ),
                    ])
                    ->withFinishReason(FinishReason::Stop)
                    ->withUsage(new Usage(30, 40))
                    ->withMeta(new Meta('fake-2', 'fake-model')),
            )
            ->toResponse(),
    ];

    Prism::fake($responses);

    $repeatOrderTool = RepeatOrderTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Repeat order ERROR123')
        ->withTools([$repeatOrderTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->steps)->toHaveCount(2);
    expect($response->toolResults[0]->result)->toBe($errorResult);
    expect($response->text)->toContain('error');
});

it('displays vendor name with emoji', function () {
    $expectedResult = "🍴 *Delicious Delights*\n"
        ."📦 Special Meal (₦2,800.00 x1) = 💰 ₦2,800.00\n\n"
        ."💳 *Total Amount:* ₦2,800.00\n\n"
        .'Do you want to checkout or add more items to your cart?';

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_vendor_emoji',
                            name: 'repeat_order',
                            arguments: [
                                'order' => [
                                    'id' => 'ORD22222',
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
                    ->withText('Your order from Delicious Delights has been added to your cart.')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_vendor_emoji',
                            toolName: 'repeat_order',
                            args: [
                                'order' => [
                                    'id' => 'ORD22222',
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

    $repeatOrderTool = RepeatOrderTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Repeat order ORD22222')
        ->withTools([$repeatOrderTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->steps)->toHaveCount(2);
    expect($response->toolResults[0]->result)->toContain('🍴');
    expect($response->toolResults[0]->result)->toContain('📦');
    expect($response->toolResults[0]->result)->toContain('💰');
    expect($response->toolResults[0]->result)->toContain('💳');
});

it('formats prices with two decimal places', function () {
    $expectedResult = "🍴 *Budget Meals*\n"
        ."📦 Rice (₦1,234.56 x1) = 💰 ₦1,234.56\n\n"
        ."💳 *Total Amount:* ₦1,234.56\n\n"
        .'Do you want to checkout or add more items to your cart?';

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_decimal',
                            name: 'repeat_order',
                            arguments: [
                                'order' => [
                                    'id' => 'ORD33333',
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
                    ->withText('Order added to cart. Total: ₦1,234.56')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_decimal',
                            toolName: 'repeat_order',
                            args: [
                                'order' => [
                                    'id' => 'ORD33333',
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

    $repeatOrderTool = RepeatOrderTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Repeat order ORD33333')
        ->withTools([$repeatOrderTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->steps)->toHaveCount(2);
    expect($response->toolResults[0]->result)->toContain('1,234.56');
});

it('has correct tool configuration', function () {
    $tool = RepeatOrderTool::make($this->user);

    expect($tool->name())->toBe('repeat_order');
    expect($tool->description())->toContain('Repeat an order');
    expect($tool->description())->toContain('after the user confirms');
    expect($tool->description())->toContain('order id');
    expect($tool->parameters())->toHaveKey('order');
});

it('calculates total correctly for multiple items', function () {
    $expectedResult = "🍴 *Multi Item Store*\n"
        ."📦 Item A (₦1,000.00 x2) = 💰 ₦2,000.00\n\n"
        ."📦 Item B (₦1,500.00 x3) = 💰 ₦4,500.00\n\n"
        ."📦 Item C (₦500.00 x1) = 💰 ₦500.00\n\n"
        ."💳 *Total Amount:* ₦7,000.00\n\n"
        .'Do you want to checkout or add more items to your cart?';

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_total_calc',
                            name: 'repeat_order',
                            arguments: [
                                'order' => [
                                    'id' => 'ORD44444',
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
                    ->withText('Your order has been repeated successfully. Total: ₦7,000.00')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_total_calc',
                            toolName: 'repeat_order',
                            args: [
                                'order' => [
                                    'id' => 'ORD44444',
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

    $repeatOrderTool = RepeatOrderTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Repeat order ORD44444')
        ->withTools([$repeatOrderTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->steps)->toHaveCount(2);
    expect($response->toolResults[0]->result)->toContain('₦7,000.00');
    expect($response->toolResults[0]->result)->toContain('₦2,000.00');
    expect($response->toolResults[0]->result)->toContain('₦4,500.00');
    expect($response->toolResults[0]->result)->toContain('₦500.00');
});
