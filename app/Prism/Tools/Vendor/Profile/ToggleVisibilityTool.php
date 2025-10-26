<?php

namespace App\Prism\Tools\Vendor\Profile;

use App\Enums\VendorStatus;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Schema\BooleanSchema;
use Prism\Prism\Tool;

class ToggleVisibilityTool
{
    public static function make(User $user): Tool
    {
        return (new Tool)
            ->as('update_product')
            ->for('Toggles the visibility of the vendor profile, this tool toggles the visibility of the vendor profile, after the user confirms he or she wants to toggle the visibility of the vendor profile.')
            ->withObjectParameter(
                'vendor',
                'The vendor parameters',
                [
                    new BooleanSchema('is_visible', 'Whether the vendor profile is visible'),
                ],
                requiredFields: [
                    'is_visible',
                ]
            )
            ->using(function (array $vendor) use ($user) {
                try {
                    $vendorModel = Vendor::firstwhere('user_id', $user->id);

                    return match (true) {
                        /** @phpstan-ignore-next-line */
                        $vendor['is_visible'] === true && $vendorModel->status == VendorStatus::ACTIVE => 'Vendor profile is already visible.',

                        /** @phpstan-ignore-next-line */
                        $vendor['is_visible'] == false && $vendorModel->status == VendorStatus::INACTIVE => 'Vendor profile is already hidden.',

                        /** @phpstan-ignore-next-line */
                        $vendor['is_visible'] == true && $vendorModel->status == VendorStatus::INACTIVE => (function () use ($vendorModel) {
                            $vendorModel->status = VendorStatus::ACTIVE;
                            $vendorModel->save();

                            return 'Vendor profile is now visible.';
                        })(),

                        /** @phpstan-ignore-next-line */
                        $vendor['is_visible'] == false && $vendorModel->status == VendorStatus::ACTIVE => (function () use ($vendorModel) {
                            $vendorModel->status = VendorStatus::INACTIVE;
                            $vendorModel->save();

                            return 'Vendor profile is now hidden.';
                        })(),

                        default => 'Invalid vendor status.',
                    };
                } catch (\Exception $e) {
                    Log::error('Error deleting product: '.$e->getMessage());

                    return 'Error deleting product, please try again.';
                }
            });
    }
}
