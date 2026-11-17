<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Mails\Http\Requests;

use App\Models\Mail\Mail;
use Illuminate\Auth\Access\Response;
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Foundation\Http\FormRequest;

final class ShowMailPreviewRequest extends FormRequest
{
    public function authorize(#[RouteParameter(parameter: 'mail')] Mail $mail): Response
    {
        return blank($mail->html) ? Response::denyAsNotFound() : Response::allow();
    }
}
