<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Features\Account\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Laravel\Fortify\InteractsWithTwoFactorState;

final class EditSecurityRequest extends FormRequest
{
    use InteractsWithTwoFactorState;

    public function authorize(): bool
    {
        return true;
    }
}
