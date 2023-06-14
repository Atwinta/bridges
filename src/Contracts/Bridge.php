<?php

namespace Atwinta\Bridges\Contracts;

use Illuminate\Http\Client\Response;

interface Bridge
{
    /**
     * TODO: для пхп 8+ добавить сигнатуру методу
     *
     * @return static
     */
    public function setDebug(bool $debug);

    /**
     * TODO: для пхп 8+ добавить сигнатуру методу
     *
     * @return static
     */
    public function setLogChannel(string $logChannel);

    /**
     * @phpstan-param array<int|string,mixed> $data
     */
    public function sendRequest(string $method, string $path, array $data = []): ?Response;
}
