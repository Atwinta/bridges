<?php

namespace Atwinta\Bridges;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;

abstract class Bridge implements Contracts\Bridge
{
    protected PendingRequest $request;

    protected string $scheme;

    protected string $host;

    protected ?string $base_path;

    protected string $accept;

    private Factory $factory;

    public function __construct(
        Factory $factory,
        string $host,
        string $scheme = 'https',
        ?string $base_path = null,
        string $accept = 'application/json'
    ) {
        $this->factory = $factory;

        $this->scheme = $scheme;
        $this->host = trim($host, '/');
        $this->base_path = $base_path !== null ? trim($base_path, '/') : null;

        $this->accept = $accept;

        $this->resetRequest();
    }

    protected function resetRequest(): void
    {
        $request = $this->authorize(
            $this->factory
                ->baseUrl("$this->scheme://$this->host".($this->base_path !== null ? "/$this->base_path" : ''))
                ->accept($this->accept),
        );

        // Обработчик ошибок для laravel 9
        if (method_exists($request, "throw")) {
            $request->throw();
        }

        $this->request = $request;
    }

    protected function authorize(PendingRequest $request): PendingRequest
    {
        return $request;
    }
}
