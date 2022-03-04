<?php

declare(strict_types=1);

namespace AdyenApi\Model;

final class ApiResponse
{
    private int $statusCode;
    private bool $success;
    private string $message;
}
