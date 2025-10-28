<?php

namespace App\Prism\Tools\Profile;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Tool;

class UpdateEmailTool
{
    public static function make(User $user): Tool
    {
        return (new Tool)
            ->as('update_email')
            ->for('This tool updates the email of the user, after the user confirms he or she wants to update the email, call the update email tool with the email address. Sometimes the user might come from the create order tool, you should call create order tool after calling this tool.')
            ->withObjectParameter(
                'profile',
                'The profile parameters',
                [
                    new StringSchema('email', 'The email address of the user, must be a valid email address'),
                ],
                requiredFields: [
                    'email',
                ]
            )
            ->using(function (array $profile) use ($user) {
                try {
                    $user->update([
                        'email' => $profile['email'],
                    ]);

                    return 'Email updated successfully. do you want to continue?';
                } catch (\Throwable $th) {
                    Log::error('Error updating email: '.$th->getMessage());

                    return 'Error updating email, please try again.';
                }
            });
    }
}
