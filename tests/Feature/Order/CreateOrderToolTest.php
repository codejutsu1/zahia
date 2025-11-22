<?php

use App\Models\User;
use App\Prism\Tools\Order\CreateOrderTool;
use App\Prism\Tools\Profile\UpdateEmailTool;
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

it('can create an order with confirmation', function () {
    $expectedResult = "*✅ Order Created Successfully*\n\n"
        ."*Total Amount: ₦* 2700.00\n\n"
        ."*Pay to*\n"
        ."Account number: 1234567890\n"
        ."Account Name: Tasty Foods\n"
        ."Bank Name: Example Bank\n\n"
        .'Thank you for your purchase!';

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withText('I can create an order for Jollof Rice and Fried Rice from Tasty Foods. Would you like me to proceed?')
                    ->withFinishReason(FinishReason::Stop)
                    ->withUsage(new Usage(25, 35))
                    ->withMeta(new Meta('fake-1', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_create_order_1',
                            name: 'create_order',
                            arguments: [
                                'cart' => [
                                    'vendor_name' => 'Tasty Foods',
                                    'products' => [
                                        ['name' => 'Jollof Rice'],
                                        ['name' => 'Fried Rice'],
                                    ],
                                ],
                            ]
                        ),
                    ])
                    ->withFinishReason(FinishReason::ToolCalls)
                    ->withUsage(new Usage(30, 40))
                    ->withMeta(new Meta('fake-2', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withText('Perfect! Your order has been created successfully. The total amount is ₦2,700.00. Please make payment to account number 1234567890 (Tasty Foods - Example Bank).')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_create_order_1',
                            toolName: 'create_order',
                            args: [
                                'cart' => [
                                    'vendor_name' => 'Tasty Foods',
                                    'products' => [
                                        ['name' => 'Jollof Rice'],
                                        ['name' => 'Fried Rice'],
                                    ],
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

    $createOrderTool = CreateOrderTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('I want to order Jollof Rice and Fried Rice from Tasty Foods')
        ->withTools([$createOrderTool])
        ->withMaxSteps(3)
        ->asText();

    expect($response->steps)->toHaveCount(3);
    expect($response->steps[0]->text)->toContain('Would you like me to proceed');
    expect($response->steps[1]->toolCalls[0]->name)->toBe('create_order');
    expect($response->steps[1]->toolCalls[0]->arguments())->toBe([
        'cart' => [
            'vendor_name' => 'Tasty Foods',
            'products' => [
                ['name' => 'Jollof Rice'],
                ['name' => 'Fried Rice'],
            ],
        ],
    ]);
    expect($response->toolResults[0]->result)->toBe($expectedResult);
    expect($response->text)->toContain('created successfully');
});

it('creates order with single product', function () {
    $expectedResult = "*✅ Order Created Successfully*\n\n"
        ."*Total Amount: ₦* 1500.00\n\n"
        ."*Pay to*\n"
        ."Account number: 9876543210\n"
        ."Account Name: Quick Bites\n"
        ."Bank Name: Sample Bank\n\n"
        .'Thank you for your purchase!';

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_single_product',
                            name: 'create_order',
                            arguments: [
                                'cart' => [
                                    'vendor_name' => 'Quick Bites',
                                    'products' => [
                                        ['name' => 'Jollof Rice'],
                                    ],
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
                    ->withText('Your order for Jollof Rice has been created! Total: ₦1,500.00. Please pay to account 9876543210.')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_single_product',
                            toolName: 'create_order',
                            args: [
                                'cart' => [
                                    'vendor_name' => 'Quick Bites',
                                    'products' => [
                                        ['name' => 'Jollof Rice'],
                                    ],
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

    $createOrderTool = CreateOrderTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Order Jollof Rice from Quick Bites')
        ->withTools([$createOrderTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->steps)->toHaveCount(2);
    expect($response->steps[0]->toolCalls[0]->arguments()['cart']['products'])->toHaveCount(1);
    expect($response->toolResults[0]->result)->toContain('Order Created Successfully');
});

it('creates order with multiple products', function () {
    $expectedResult = "*✅ Order Created Successfully*\n\n"
        ."*Total Amount: ₦* 5200.00\n\n"
        ."*Pay to*\n"
        ."Account number: 1122334455\n"
        ."Account Name: Food Palace\n"
        ."Bank Name: Trust Bank\n\n"
        .'Thank you for your purchase!';

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_multiple_products',
                            name: 'create_order',
                            arguments: [
                                'cart' => [
                                    'vendor_name' => 'Food Palace',
                                    'products' => [
                                        ['name' => 'Jollof Rice'],
                                        ['name' => 'Fried Rice'],
                                        ['name' => 'Chicken'],
                                        ['name' => 'Plantain'],
                                    ],
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
                    ->withText('Great! Your order for 4 items has been created successfully. Total: ₦5,200.00.')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_multiple_products',
                            toolName: 'create_order',
                            args: [
                                'cart' => [
                                    'vendor_name' => 'Food Palace',
                                    'products' => [
                                        ['name' => 'Jollof Rice'],
                                        ['name' => 'Fried Rice'],
                                        ['name' => 'Chicken'],
                                        ['name' => 'Plantain'],
                                    ],
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

    $createOrderTool = CreateOrderTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Order Jollof Rice, Fried Rice, Chicken, and Plantain from Food Palace')
        ->withTools([$createOrderTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->steps)->toHaveCount(2);
    expect($response->steps[0]->toolCalls[0]->arguments()['cart']['products'])->toHaveCount(4);
    expect($response->toolResults[0]->result)->toContain('*Total Amount: ₦* 5200.00');
});

it('handles invalid email error and calls update email tool', function () {
    $invalidEmailResult = 'You don\'t have an email address, please provide a valid email address and call the update email tool.';
    $emailUpdateResult = 'Email updated successfully. do you want to continue?';
    $orderSuccessResult = "*✅ Order Created Successfully*\n\n"
        ."*Total Amount: ₦* 1500.00\n\n"
        ."*Pay to*\n"
        ."Account number: 1234567890\n"
        ."Account Name: Tasty Foods\n"
        ."Bank Name: Example Bank\n\n"
        .'Thank you for your purchase!';

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_order_no_email',
                            name: 'create_order',
                            arguments: [
                                'cart' => [
                                    'vendor_name' => 'Tasty Foods',
                                    'products' => [
                                        ['name' => 'Jollof Rice'],
                                    ],
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
                    ->withText('I need your email address to create the order. Could you please provide a valid email address?')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_order_no_email',
                            toolName: 'create_order',
                            args: [
                                'cart' => [
                                    'vendor_name' => 'Tasty Foods',
                                    'products' => [
                                        ['name' => 'Jollof Rice'],
                                    ],
                                ],
                            ],
                            result: $invalidEmailResult
                        ),
                    ])
                    ->withFinishReason(FinishReason::Stop)
                    ->withUsage(new Usage(35, 45))
                    ->withMeta(new Meta('fake-2', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_update_email',
                            name: 'update_email',
                            arguments: [
                                'profile' => [
                                    'email' => 'newuser@example.com',
                                ],
                            ]
                        ),
                    ])
                    ->withFinishReason(FinishReason::ToolCalls)
                    ->withUsage(new Usage(28, 38))
                    ->withMeta(new Meta('fake-3', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_order_after_email',
                            name: 'create_order',
                            arguments: [
                                'cart' => [
                                    'vendor_name' => 'Tasty Foods',
                                    'products' => [
                                        ['name' => 'Jollof Rice'],
                                    ],
                                ],
                            ]
                        ),
                    ])
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_update_email',
                            toolName: 'update_email',
                            args: [
                                'profile' => [
                                    'email' => 'newuser@example.com',
                                ],
                            ],
                            result: $emailUpdateResult
                        ),
                    ])
                    ->withFinishReason(FinishReason::ToolCalls)
                    ->withUsage(new Usage(40, 50))
                    ->withMeta(new Meta('fake-4', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withText('Perfect! Your email has been updated and your order has been created successfully.')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_order_after_email',
                            toolName: 'create_order',
                            args: [
                                'cart' => [
                                    'vendor_name' => 'Tasty Foods',
                                    'products' => [
                                        ['name' => 'Jollof Rice'],
                                    ],
                                ],
                            ],
                            result: $orderSuccessResult
                        ),
                    ])
                    ->withFinishReason(FinishReason::Stop)
                    ->withUsage(new Usage(55, 65))
                    ->withMeta(new Meta('fake-5', 'fake-model')),
            )
            ->toResponse(),
    ];

    Prism::fake($responses);

    $createOrderTool = CreateOrderTool::make($this->user);
    $updateEmailTool = UpdateEmailTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Create an order for Jollof Rice from Tasty Foods')
        ->withTools([$createOrderTool, $updateEmailTool])
        ->withMaxSteps(5)
        ->asText();

    expect($response->steps)->toHaveCount(5);

    expect($response->steps[0]->toolCalls[0]->name)->toBe('create_order');
    expect($response->steps[1]->toolResults[0]->result)->toContain('provide a valid email address');

    expect($response->steps[2]->toolCalls[0]->name)->toBe('update_email');
    expect($response->steps[3]->toolResults[0]->result)->toContain('Email updated successfully');

    expect($response->steps[3]->toolCalls[0]->name)->toBe('create_order');
    expect($response->steps[4]->toolResults[0]->result)->toContain('Order Created Successfully');
});

it('asks for email when user has no email', function () {
    $invalidEmailResult = 'You don\'t have an email address, please provide a valid email address and call the update email tool.';

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_no_email',
                            name: 'create_order',
                            arguments: [
                                'cart' => [
                                    'vendor_name' => 'Food Hub',
                                    'products' => [
                                        ['name' => 'Fried Rice'],
                                    ],
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
                    ->withText('To create your order, I need your email address. Please provide a valid email address so I can proceed.')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_no_email',
                            toolName: 'create_order',
                            args: [
                                'cart' => [
                                    'vendor_name' => 'Food Hub',
                                    'products' => [
                                        ['name' => 'Fried Rice'],
                                    ],
                                ],
                            ],
                            result: $invalidEmailResult
                        ),
                    ])
                    ->withFinishReason(FinishReason::Stop)
                    ->withUsage(new Usage(35, 45))
                    ->withMeta(new Meta('fake-2', 'fake-model')),
            )
            ->toResponse(),
    ];

    Prism::fake($responses);

    $createOrderTool = CreateOrderTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Order Fried Rice from Food Hub')
        ->withTools([$createOrderTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->steps)->toHaveCount(2);
    expect($response->toolResults[0]->result)->toBe($invalidEmailResult);
    expect($response->text)->toContain('email address');
});

it('handles order creation error gracefully', function () {
    $errorResult = 'Error creating order';

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_order_error',
                            name: 'create_order',
                            arguments: [
                                'cart' => [
                                    'vendor_name' => 'Error Vendor',
                                    'products' => [
                                        ['name' => 'Test Product'],
                                    ],
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
                    ->withText('I encountered an error while creating your order. Please try again or contact support.')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_order_error',
                            toolName: 'create_order',
                            args: [
                                'cart' => [
                                    'vendor_name' => 'Error Vendor',
                                    'products' => [
                                        ['name' => 'Test Product'],
                                    ],
                                ],
                            ],
                            result: $errorResult
                        ),
                    ])
                    ->withFinishReason(FinishReason::Stop)
                    ->withUsage(new Usage(35, 45))
                    ->withMeta(new Meta('fake-2', 'fake-model')),
            )
            ->toResponse(),
    ];

    Prism::fake($responses);

    $createOrderTool = CreateOrderTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Create an order for Test Product from Error Vendor')
        ->withTools([$createOrderTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->steps)->toHaveCount(2);
    expect($response->toolResults[0]->result)->toBe($errorResult);
    expect($response->text)->toContain('error');
});

it('displays payment information in order confirmation', function () {
    $expectedResult = "*✅ Order Created Successfully*\n\n"
        ."*Total Amount: ₦* 2500.00\n\n"
        ."*Pay to*\n"
        ."Account number: 5555666677\n"
        ."Account Name: Premium Foods\n"
        ."Bank Name: First Bank\n\n"
        .'Thank you for your purchase!';

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_payment_info',
                            name: 'create_order',
                            arguments: [
                                'cart' => [
                                    'vendor_name' => 'Premium Foods',
                                    'products' => [
                                        ['name' => 'Special Rice'],
                                    ],
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
                    ->withText('Your order has been created! Please make payment of ₦2,500.00 to account 5555666677 (Premium Foods - First Bank).')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_payment_info',
                            toolName: 'create_order',
                            args: [
                                'cart' => [
                                    'vendor_name' => 'Premium Foods',
                                    'products' => [
                                        ['name' => 'Special Rice'],
                                    ],
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

    $createOrderTool = CreateOrderTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Order Special Rice from Premium Foods')
        ->withTools([$createOrderTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->steps)->toHaveCount(2);
    expect($response->toolResults[0]->result)->toContain('Account number: 5555666677');
    expect($response->toolResults[0]->result)->toContain('Account Name: Premium Foods');
    expect($response->toolResults[0]->result)->toContain('Bank Name: First Bank');
    expect($response->toolResults[0]->result)->toContain('Thank you for your purchase!');
});

it('has correct tool configuration', function () {
    $tool = CreateOrderTool::make($this->user);

    expect($tool->name())->toBe('create_order');
    expect($tool->description())->toContain('Creating an order');
    expect($tool->description())->toContain('after the user confirms');
    expect($tool->description())->toContain('invalid email');
    expect($tool->description())->toContain('update email tool');
    expect($tool->parameters())->toHaveKey('cart');
});

it('handles product name case variations', function () {
    $expectedResult = "*✅ Order Created Successfully*\n\n"
        ."*Total Amount: ₦* 1800.00\n\n"
        ."*Pay to*\n"
        ."Account number: 9998887776\n"
        ."Account Name: Local Eats\n"
        ."Bank Name: Access Bank\n\n"
        .'Thank you for your purchase!';

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_case_variation',
                            name: 'create_order',
                            arguments: [
                                'cart' => [
                                    'vendor_name' => 'Local Eats',
                                    'products' => [
                                        ['name' => 'jollof rice'],
                                        ['name' => 'FRIED RICE'],
                                    ],
                                ],
                            ]
                        ),
                    ])
                    ->withFinishReason(FinishReason::ToolCalls)
                    ->withUsage(new Usage(24, 34))
                    ->withMeta(new Meta('fake-1', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withText('Your order for Jollof Rice and Fried Rice has been created successfully.')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_case_variation',
                            toolName: 'create_order',
                            args: [
                                'cart' => [
                                    'vendor_name' => 'Local Eats',
                                    'products' => [
                                        ['name' => 'jollof rice'],
                                        ['name' => 'FRIED RICE'],
                                    ],
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

    $createOrderTool = CreateOrderTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Order jollof rice and FRIED RICE from Local Eats')
        ->withTools([$createOrderTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->steps)->toHaveCount(2);
    expect($response->steps[0]->toolCalls[0]->arguments()['cart']['products'])->toHaveCount(2);
    expect($response->toolResults[0]->result)->toContain('Order Created Successfully');
});
