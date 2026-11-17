<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Logs\Http\Requests;

use App\Support\Logging\EventLog;
use App\Support\Logging\EventLogLine;
use Illuminate\Auth\Access\Response;
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Foundation\Http\FormRequest;

final class ShowLogRequest extends FormRequest
{
    private EventLogLine $line;

    public function authorize(EventLog $log, #[RouteParameter(parameter: 'day')] string $day, #[RouteParameter(parameter: 'offset')] string $offset): Response
    {
        $line = $log->lineAt($day, (int) $offset);

        if (!$line instanceof EventLogLine) {
            return Response::denyAsNotFound();
        }

        $this->line = $line;

        return Response::allow();
    }

    public function line(): EventLogLine
    {
        return $this->line;
    }
}
