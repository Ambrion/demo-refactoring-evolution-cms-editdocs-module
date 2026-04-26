<?php

declare(strict_types=1);

namespace EditDocs\Tests\Stubs;

use EditDocs\Http\ResponseSenderInterface;

class TestResponseSender implements ResponseSenderInterface
{
    public string $lastContent = '';
    public int $lastStatusCode = 200;
    public array $lastHeaders = [];

    public function send(string $content, int $statusCode, array $headers = []): void
    {
        $this->lastContent = $content;
        $this->lastStatusCode = $statusCode;
        $this->lastHeaders = $headers;
    }
}
