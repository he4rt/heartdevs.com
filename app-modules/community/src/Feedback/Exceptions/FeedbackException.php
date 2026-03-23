<?php

declare(strict_types=1);

namespace He4rt\Community\Feedback\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class FeedbackException extends Exception
{
    public static function idNotFound(int $id): self
    {
        return new self(sprintf(trans('feedbacks.exceptions.id_not_found'), $id), Response::HTTP_NOT_FOUND);
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json($this->getMessage(), $this->code);
    }
}
