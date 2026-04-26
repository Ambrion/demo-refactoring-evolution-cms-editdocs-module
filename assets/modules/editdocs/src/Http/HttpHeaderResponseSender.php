<?php

declare(strict_types=1);


namespace EditDocs\Http;

class HttpHeaderResponseSender implements ResponseSenderInterface
{
    public function send(string $content, int $statusCode, array $headers = []): void
    {
        if (!headers_sent()) {
            http_response_code($statusCode);
            foreach ($headers as $header) {
                header($header);
            }
        }
        echo $content;
        exit;
    }
}
