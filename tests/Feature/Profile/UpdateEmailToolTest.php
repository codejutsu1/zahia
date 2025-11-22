<?php

use App\Models\User;
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
        'email' => 'old@example.com',
    ]);
});

it('can update user email successfully', function () {
    $expectedResult = 'Email updated successfully. do you want to continue?';

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_update_email_1',
                            name: 'update_email',
                            arguments: [
                                'profile' => [
                                    'email' => 'new@example.com',
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
                    ->withText('Your email has been updated successfully to new@example.com. Would you like to continue with anything else?')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_update_email_1',
                            toolName: 'update_email',
                            args: [
                                'profile' => [
                                    'email' => 'new@example.com',
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

    $updateEmailTool = UpdateEmailTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Please update my email to new@example.com')
        ->withTools([$updateEmailTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->steps)->toHaveCount(2);

    expect($response->steps[0]->toolCalls)->toHaveCount(1);
    expect($response->steps[0]->toolCalls[0]->name)->toBe('update_email');
    expect($response->steps[0]->toolCalls[0]->arguments())->toBe([
        'profile' => [
            'email' => 'new@example.com',
        ],
    ]);

    expect($response->toolResults)->toHaveCount(1);
    expect($response->toolResults[0]->result)->toBe($expectedResult);

    expect($response->text)->toContain('updated successfully');
});

it('updates email with different valid formats', function () {
    $expectedResult = 'Email updated successfully. do you want to continue?';

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_update_email_format',
                            name: 'update_email',
                            arguments: [
                                'profile' => [
                                    'email' => 'user.name+tag@example.co.uk',
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
                    ->withText('Your email has been updated to user.name+tag@example.co.uk successfully.')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_update_email_format',
                            toolName: 'update_email',
                            args: [
                                'profile' => [
                                    'email' => 'user.name+tag@example.co.uk',
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

    $updateEmailTool = UpdateEmailTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Change my email to user.name+tag@example.co.uk')
        ->withTools([$updateEmailTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->steps)->toHaveCount(2);
    expect($response->toolResults[0]->result)->toBe($expectedResult);
});

it('confirms before updating email', function () {
    $expectedResult = 'Email updated successfully. do you want to continue?';

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withText('I can update your email to updated@example.com. Would you like me to proceed?')
                    ->withFinishReason(FinishReason::Stop)
                    ->withUsage(new Usage(18, 28))
                    ->withMeta(new Meta('fake-1', 'fake-model'))
            )
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_confirmed_update',
                            name: 'update_email',
                            arguments: [
                                'profile' => [
                                    'email' => 'updated@example.com',
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
                    ->withText('Perfect! Your email has been updated to updated@example.com. Do you want to continue?')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_confirmed_update',
                            toolName: 'update_email',
                            args: [
                                'profile' => [
                                    'email' => 'updated@example.com',
                                ],
                            ],
                            result: $expectedResult
                        ),
                    ])
                    ->withFinishReason(FinishReason::Stop)
                    ->withUsage(new Usage(40, 50))
                    ->withMeta(new Meta('fake-3', 'fake-model')),
            )
            ->toResponse(),
    ];

    Prism::fake($responses);

    $updateEmailTool = UpdateEmailTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('I want to update my email to updated@example.com')
        ->withTools([$updateEmailTool])
        ->withMaxSteps(3)
        ->asText();

    expect($response->steps)->toHaveCount(3);
    expect($response->steps[0]->text)->toContain('Would you like me to proceed');
    expect($response->steps[1]->toolCalls[0]->name)->toBe('update_email');
    expect($response->toolResults[0]->result)->toBe($expectedResult);
});

it('asks if user wants to continue after email update', function () {
    $expectedResult = 'Email updated successfully. do you want to continue?';

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_continue_prompt',
                            name: 'update_email',
                            arguments: [
                                'profile' => [
                                    'email' => 'continue@example.com',
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
                    ->withText('Your email has been updated successfully to continue@example.com. Do you want to continue?')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_continue_prompt',
                            toolName: 'update_email',
                            args: [
                                'profile' => [
                                    'email' => 'continue@example.com',
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

    $updateEmailTool = UpdateEmailTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Update my email to continue@example.com')
        ->withTools([$updateEmailTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->steps)->toHaveCount(2);
    expect($response->toolResults[0]->result)->toContain('do you want to continue?');
    expect($response->text)->toContain('continue');
});

it('handles case-sensitive email addresses', function () {
    $expectedResult = 'Email updated successfully. do you want to continue?';

    $responses = [
        (new ResponseBuilder)
            ->addStep(
                TextStepFake::make()
                    ->withToolCalls([
                        new ToolCall(
                            id: 'call_case_sensitive',
                            name: 'update_email',
                            arguments: [
                                'profile' => [
                                    'email' => 'User.Name@Example.COM',
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
                    ->withText('Your email has been updated to User.Name@Example.COM.')
                    ->withToolResults([
                        new ToolResult(
                            toolCallId: 'call_case_sensitive',
                            toolName: 'update_email',
                            args: [
                                'profile' => [
                                    'email' => 'User.Name@Example.COM',
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

    $updateEmailTool = UpdateEmailTool::make($this->user);

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Change my email to User.Name@Example.COM')
        ->withTools([$updateEmailTool])
        ->withMaxSteps(2)
        ->asText();

    expect($response->steps)->toHaveCount(2);
    expect($response->toolResults[0]->result)->toBe($expectedResult);
});

it('has correct tool configuration', function () {
    $tool = UpdateEmailTool::make($this->user);

    expect($tool->name())->toBe('update_email');
    expect($tool->description())->toContain('updates the email of the user');
    expect($tool->description())->toContain('after the user confirms');
    expect($tool->description())->toContain('create order tool');
    expect($tool->parameters())->toHaveKey('profile');
});
