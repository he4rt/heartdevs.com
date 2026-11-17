<?php

declare(strict_types=1);

namespace He4rt\Identity\Users\Http\Requests;

use He4rt\Identity\Users\Impersonation\Impersonation;
use He4rt\Identity\Users\User;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteImpersonationRequest extends FormRequest
{
    public function authorize(Impersonation $impersonation): bool
    {
        return $impersonation->impersonator() instanceof User;
    }
}
