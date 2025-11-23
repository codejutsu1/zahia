<?php

use App\Models\User;
use App\Prism\Tools\Order\CheckoutOrderTool;
use App\Prism\Tools\Order\CreateOrderTool;
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
    $this->user = User::factory()->create(['email' => 'customer@example.com']);
});

it('shows checkout details and asks for confirmation', function () {
    $checkoutResult = "🍴 *Tasty Foods*\n"
        ."📦 Jollof Rice (₦1,500.00 x2) = 💰 ₦3,000.00\n\n"
        ."🧾 *Subtotal:* ₦3,000.00\n"
        ."🚚 *Delivery Fee:* ₦800.00\n"
        ."⚙️ *Service Fee:* ₦400.00\n"
        ."💳 *Cart Total:* ₦4,200.00\n";

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_checkout',
                            name: 'checkout_order',
                            arguments: [
                                'cart' => [
                                    'vendor_name' => 'Tasty Foods',
                                    'products' => [['name' => 'Jollof Rice']],
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
                    ->withText('Your order total is ₦4,200.00. Would you like to proceed with the order?')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_checkout',
                            toolName: 'checkout_order',
                            args: ['cart' => ['vendor_name' => 'Tasty Foods', 'products' => [['name' => 'Jollof Rice']]]],
                            result: $checkoutResult
                        ),
                    ])
                    ->withFinishReason(FinishReason::Stop)
                    ->withUsage(new Usage(35, 45))
                    ->withMeta(new Meta('fake-2', 'fake-model')),
            )
            ->toResponse(),
    ];

    Prism::fake($responses);

    $checkoutTool = CheckoutOrderTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('I want to checkout')
        ->withTools([$checkoutTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->steps)->toHaveCount(2);
    expect($response->toolResults[0]->result)->toContain('Subtotal');
    expect($response->toolResults[0]->result)->toContain('Delivery Fee');
    expect($response->text)->toContain('proceed');
});

it('proceeds with create order after user confirms', function () {
    $checkoutResult = "🍴 *Quick Bites*\n"
        ."📦 Burger (₦2,500.00 x1) = 💰 ₦2,500.00\n\n"
        ."🧾 *Subtotal:* ₦2,500.00\n"
        ."🚚 *Delivery Fee:* ₦1,200.00\n"
        ."⚙️ *Service Fee:* ₦800.00\n"
        ."💳 *Cart Total:* ₦4,500.00\n";

    $orderResult = "*✅ Order Created Successfully*\n\n*Total Amount: ₦* 4500.00\n\n*Pay to*\nAccount number: 1234567890\nThank you!";

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(id: 'call_checkout', name: 'checkout_order', arguments: ['cart' => ['vendor_name' => 'Quick Bites', 'products' => [['name' => 'Burger']]]]),
                    ])
                    ->withFinishReason(FinishReason::ToolCalls)
                    ->withUsage(new Usage(20, 30))
                    ->withMeta(new Meta('fake-1', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withText('Total is ₦4,500.00. Do you want to proceed?')
                    ->withToolResults([
                        new ToolResult(toolCallId: 'call_checkout', toolName: 'checkout_order', args: ['cart' => ['vendor_name' => 'Quick Bites', 'products' => [['name' => 'Burger']]]], result: $checkoutResult),
                    ])
                    ->withFinishReason(FinishReason::Stop)
                    ->withUsage(new Usage(35, 45))
                    ->withMeta(new Meta('fake-2', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(id: 'call_create', name: 'create_order', arguments: ['cart' => ['vendor_name' => 'Quick Bites', 'products' => [['name' => 'Burger']]]]),
                    ])
                    ->withFinishReason(FinishReason::ToolCalls)
                    ->withUsage(new Usage(40, 50))
                    ->withMeta(new Meta('fake-3', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withText('Order created successfully!')
                    ->withToolResults([
                        new ToolResult(toolCallId: 'call_create', toolName: 'create_order', args: ['cart' => ['vendor_name' => 'Quick Bites', 'products' => [['name' => 'Burger']]]], result: $orderResult),
                    ])
                    ->withFinishReason(FinishReason::Stop)
                    ->withUsage(new Usage(50, 60))
                    ->withMeta(new Meta('fake-4', 'fake-model')),
            )
            ->toResponse(),
    ];

    Prism::fake($responses);

    $checkoutTool = CheckoutOrderTool::make($this->user);
    $createOrderTool = CreateOrderTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Checkout and confirm order')
        ->withTools([$checkoutTool, $createOrderTool])
        ->withMaxSteps(4)
        ->asText();

    expect($response->steps)->toHaveCount(4);
    expect($response->steps[0]->toolCalls[0]->name)->toBe('checkout_order');
    expect($response->steps[2]->toolCalls[0]->name)->toBe('create_order');
    expect($response->text)->toContain('created successfully');
});

it('handles checkout error gracefully', function () {
    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(id: 'call_error', name: 'checkout_order', arguments: ['cart' => ['vendor_name' => 'Invalid', 'products' => [['name' => 'Test']]]]),
                    ])
                    ->withFinishReason(FinishReason::ToolCalls)
                    ->withUsage(new Usage(18, 28))
                    ->withMeta(new Meta('fake-1', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withText('I encountered an error getting checkout information.')
                    ->withToolResults([
                        new ToolResult(toolCallId: 'call_error', toolName: 'checkout_order', args: ['cart' => ['vendor_name' => 'Invalid', 'products' => [['name' => 'Test']]]], result: 'Error getting checkout information'),
                    ])
                    ->withFinishReason(FinishReason::Stop)
                    ->withUsage(new Usage(30, 40))
                    ->withMeta(new Meta('fake-2', 'fake-model')),
            )
            ->toResponse(),
    ];

    Prism::fake($responses);

    $checkoutTool = CheckoutOrderTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Checkout')
        ->withTools([$checkoutTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->toolResults[0]->result)->toBe('Error getting checkout information');
});

it('displays fees breakdown with emojis', function () {
    $checkoutResult = "🍴 *Food Palace*\n"
        ."📦 Pizza (₦3,000.00 x1) = 💰 ₦3,000.00\n\n"
        ."🧾 *Subtotal:* ₦3,000.00\n"
        ."🚚 *Delivery Fee:* ₦1,600.00\n"
        ."⚙️ *Service Fee:* ₦1,200.00\n"
        ."💳 *Cart Total:* ₦5,800.00\n";

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(id: 'call_fees', name: 'checkout_order', arguments: ['cart' => ['vendor_name' => 'Food Palace', 'products' => [['name' => 'Pizza']]]]),
                    ])
                    ->withFinishReason(FinishReason::ToolCalls)
                    ->withUsage(new Usage(20, 30))
                    ->withMeta(new Meta('fake-1', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withText('Checkout details ready.')
                    ->withToolResults([
                        new ToolResult(toolCallId: 'call_fees', toolName: 'checkout_order', args: ['cart' => ['vendor_name' => 'Food Palace', 'products' => [['name' => 'Pizza']]]], result: $checkoutResult),
                    ])
                    ->withFinishReason(FinishReason::Stop)
                    ->withUsage(new Usage(35, 45))
                    ->withMeta(new Meta('fake-2', 'fake-model')),
            )
            ->toResponse(),
    ];

    Prism::fake($responses);

    $checkoutTool = CheckoutOrderTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Checkout pizza')
        ->withTools([$checkoutTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->toolResults[0]->result)->toContain('🧾');
    expect($response->toolResults[0]->result)->toContain('🚚');
    expect($response->toolResults[0]->result)->toContain('⚙️');
    expect($response->toolResults[0]->result)->toContain('💳');
});
