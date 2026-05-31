<?php

declare(strict_types=1);

namespace He4rt\Profile\Actions;

use He4rt\Profile\Enums\StartAvailability;
use He4rt\Profile\Models\Profile;
use Illuminate\Validation\ValidationException;

final class ToggleAvailability
{
    public function handle(Profile $profile, bool $available, ?StartAvailability $startAvailability = null): Profile
    {
        if ($available && !$startAvailability instanceof StartAvailability) {
            throw ValidationException::withMessages([
                'start_availability' => [__('validation.required_if', [
                    'attribute' => 'start_availability',
                    'other' => 'available_for_proposals',
                    'value' => 'true',
                ])],
            ]);
        }

        $attributes = ['available_for_proposals' => $available];

        if ($available && $startAvailability instanceof StartAvailability) {
            $attributes['start_availability'] = $startAvailability;
        }

        $profile->update($attributes);

        return $profile->refresh();
    }
}
