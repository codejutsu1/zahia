<?php

use App\Models\User;
use Prism\Prism\Prism;
use App\Prism\Tools\Order\RepeatOrderTool;
use Prism\Prism\Enums\Provider;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\ValueObjects\Usage;
use Prism\Prism\Testing\TextStepFake;
use Prism\Prism\Text\ResponseBuilder;
use Prism\Prism\ValueObjects\ToolCall;
use Prism\Prism\ValueObjects\ToolResult;
use App\Prism\Tools\Order\ListOrdersTool;

beforeEach(function () {
    $this->user = User::factory()->create(['email' => 'customer@example.com']);
});

it('lists user orders and asks about repeating', function () {
    $ordersResult = "💳 *Order ID:* ORD12345\n"
        ."💳 *Vendor Name:* Tasty Foods\n"
        ."💳 *Total Items:* 3\n"
        ."💳 *Total Amount:* ₦3,500.00\n"
        ."💳 *Status:* Pending\n"
        ."💳 *Created At:* 23rd November 2024, 14:30\n\n\n"
        ."💳 *Order ID:* ORD67890\n"
        ."💳 *Vendor Name:* Quick Bites\n"
        ."💳 *Total Items:* 2\n"
        ."💳 *Total Amount:* ₦2,000.00\n"
        ."💳 *Status:* Completed\n"
        ."💳 *Created At:* 22nd November 2024, 10:15\n\n\n";

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_list_orders',
                            name: 'list_orders',
                            arguments: ['orders' => ['order_id' => '']]
                        ),
                    ])
                    ->withFinishReason(FinishReason::ToolCalls)
                    ->withUsage(new Usage(20, 30))
                    ->withMeta(new Meta('fake-1', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withText('Here are your orders. Would you like to repeat any of these orders?')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_list_orders',
                            toolName: 'list_orders',
                            args: ['orders' => ['order_id' => '']],
                            result: $ordersResult
                        ),
                    ])
                    ->withFinishReason(FinishReason::Stop)
                    ->withUsage(new Usage(45, 55))
                    ->withMeta(new Meta('fake-2', 'fake-model')),
            )
            ->toResponse(),
    ];

    Prism::fake($responses);

    $listOrdersTool = ListOrdersTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Show me my orders')
        ->withTools([$listOrdersTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->steps)->toHaveCount(2);
    expect($response->toolResults[0]->result)->toContain('Order ID:');
    expect($response->toolResults[0]->result)->toContain('Vendor Name:');
    expect($response->text)->toContain('repeat');
});

it('lists orders then proceeds to repeat when user confirms', function () {
    $ordersResult = "💳 *Order ID:* ORD12345\n"
        ."💳 *Vendor Name:* Tasty Foods\n"
        ."💳 *Total Items:* 2\n"
        ."💳 *Total Amount:* ₦2,500.00\n"
        ."💳 *Status:* Completed\n"
        ."💳 *Created At:* 20th November 2024, 12:00\n\n\n";

    $repeatResult = "🍴 *Tasty Foods*\n"
        ."📦 Jollof Rice (₦1,500.00 x1) = 💰 ₦1,500.00\n\n"
        ."💳 *Total Amount:* ₦1,500.00\n\n"
        .'Do you want to checkout or add more items to your cart?';

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(id: 'call_list', name: 'list_orders', arguments: ['orders' => ['order_id' => '']])
                    ])
                    ->withFinishReason(FinishReason::ToolCalls)
                    ->withUsage(new Usage(20, 30))
                    ->withMeta(new Meta('fake-1', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withText('Your orders are shown above. Would you like to repeat any?')
                    ->withToolResults([
                        new ToolResult(toolCallId: 'call_list', toolName: 'list_orders', args: ['orders' => ['order_id' => '']], result: $ordersResult)
                    ])
                    ->withFinishReason(FinishReason::Stop)
                    ->withUsage(new Usage(40, 50))
                    ->withMeta(new Meta('fake-2', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(id: 'call_repeat', name: 'repeat_order', arguments: ['order' => ['id' => 'ORD12345']])
                    ])
                    ->withFinishReason(FinishReason::ToolCalls)
                    ->withUsage(new Usage(45, 55))
                    ->withMeta(new Meta('fake-3', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withText('Your order has been repeated!')
                    ->withToolResults([
                        new ToolResult(toolCallId: 'call_repeat', toolName: 'repeat_order', args: ['order' => ['id' => 'ORD12345']], result: $repeatResult)
                    ])
                    ->withFinishReason(FinishReason::Stop)
                    ->withUsage(new Usage(55, 65))
                    ->withMeta(new Meta('fake-4', 'fake-model')),
            )
            ->toResponse(),
    ];

    Prism::fake($responses);

    $listOrdersTool = ListOrdersTool::make($this->user);
    $repeatOrderTool = RepeatOrderTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Show my orders and repeat ORD12345')
        ->withTools([$listOrdersTool, $repeatOrderTool])
        ->withMaxSteps(4)
        ->asText();

    expect($response->steps)->toHaveCount(4);
    expect($response->steps[0]->toolCalls[0]->name)->toBe('list_orders');
    expect($response->steps[2]->toolCalls[0]->name)->toBe('repeat_order');
});

it('handles empty orders list', function () {
    $ordersResult = '';

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(id: 'call_empty', name: 'list_orders', arguments: ['orders' => ['order_id' => '']])
                    ])
                    ->withFinishReason(FinishReason::ToolCalls)
                    ->withUsage(new Usage(18, 28))
                    ->withMeta(new Meta('fake-1', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withText('You have no orders yet.')
                    ->withToolResults([
                        new ToolResult(toolCallId: 'call_empty', toolName: 'list_orders', args: ['orders' => ['order_id' => '']], result: $ordersResult)
                    ])
                    ->withFinishReason(FinishReason::Stop)
                    ->withUsage(new Usage(30, 40))
                    ->withMeta(new Meta('fake-2', 'fake-model')),
            )
            ->toResponse(),
    ];

    Prism::fake($responses);

    $listOrdersTool = ListOrdersTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Show my orders')
        ->withTools([$listOrdersTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->toolResults[0]->result)->toBe('');
});

it('handles list orders error', function () {
    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(id: 'call_error', name: 'list_orders', arguments: ['orders' => ['order_id' => '']])
                    ])
                    ->withFinishReason(FinishReason::ToolCalls)
                    ->withUsage(new Usage(18, 28))
                    ->withMeta(new Meta('fake-1', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withText('I encountered an error retrieving your orders.')
                    ->withToolResults([
                        new ToolResult(toolCallId: 'call_error', toolName: 'list_orders', args: ['orders' => ['order_id' => '']], result: 'Error listing orders')
                    ])
                    ->withFinishReason(FinishReason::Stop)
                    ->withUsage(new Usage(30, 40))
                    ->withMeta(new Meta('fake-2', 'fake-model')),
            )
            ->toResponse(),
    ];

    Prism::fake($responses);

    $listOrdersTool = ListOrdersTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Show orders')
        ->withTools([$listOrdersTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->toolResults[0]->result)->toBe('Error listing orders');
});