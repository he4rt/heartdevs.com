<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Users\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Without an app to receive the user, impersonation does not exist.
 */
final class CreateUserImpersonationRequest extends FormRequest
{
    private string $redirectTo;

    public function authorize(): Response
    {
        $redirectTo = config('he4rt.impersonation.redirect_to');

        if (!is_string($redirectTo)) {
            return Response::denyAsNotFound();
        }

        $this->redirectTo = $redirectTo;

        return Response::allow();
    }

    public function redirectTo(): string
    {
        return $this->redirectTo;
    }
}
