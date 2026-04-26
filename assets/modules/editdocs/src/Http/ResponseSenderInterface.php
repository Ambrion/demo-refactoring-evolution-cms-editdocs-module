<?php

declare(strict_types=1);


namespace EditDocs\Http;

interface ResponseSenderInterface
{
    public function send(string $content, int $statusCode, array $headers = []): void;
}
