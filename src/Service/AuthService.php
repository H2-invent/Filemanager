<?php

namespace App\Service;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthService
{
    private string $bearerToken;

    public function __construct(string $bearerToken)
    {
        $this->bearerToken = $bearerToken;
    }

    public function validateToken(?string $token): void
    {
        $bearerTokenHash = hash('sha256',$this->bearerToken);
        if (!$token || $token !== $bearerTokenHash) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid or missing authorization token.');
        }
    }
}
