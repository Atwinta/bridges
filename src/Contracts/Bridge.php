<?php

namespace Atwinta\Bridges\Contracts;

use Illuminate\Http\Client\Response;

interface Bridge
{
    public function setDebug(bool $debug): static;

    public function setLogChannel(string $logChannel): static;

    /**
     * @phpstan-param array<int|string,mixed> $data
     */
    public function sendRequest(string $method, string $path, array $data = []): ?Response;
}
