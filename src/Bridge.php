<?php

namespace Atwinta\Bridges;

use Exception;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

abstract class Bridge implements Contracts\Bridge
{
    protected PendingRequest $request;

    protected string $scheme;

    protected string $host;

    protected ?string $base_path;

    protected string $accept;

    private Factory $factory;

    private bool $debug = false;

    private string $logChannel = 'stack';

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

    private function getBaseUrl(): string
    {
        return rtrim(
            trim("{$this->scheme}://{$this->host}", '/')
            .'/'
            .trim("{$this->base_path}", '/'),
            '/');
    }

    /**
     * @phpstan-param array<int|string,mixed> $context
     */
    private function writeLogs(string $message, array $context = [], bool $isError = false): void
    {
        if ($isError) {
            Log::channel($this->logChannel)->error($message, $context);
        } else {
            Log::channel($this->logChannel)->info($message, $context);
        }
    }

    protected function resetRequest(): void
    {
        $request = $this->authorize(
            /** @phpstan-ignore-next-line */
            $this->factory
                ->baseUrl($this->getBaseUrl())
                ->accept($this->accept),
        );

        /**
         * Обработчик ошибок для laravel 9
         *
         * @phpstan-ignore-next-line
         */
        if (method_exists($request, 'throw')) {
            $request->throw();
        }

        $this->request = $request;
    }

    /**
     * @phpstan-param PendingRequest $request
     */
    protected function authorize(PendingRequest $request): PendingRequest
    {
        return $request;
    }

    /**
     * {@inheritDoc}
     */
    public function setDebug(bool $debug)
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setLogChannel(string $logChannel)
    {
        $this->logChannel = $logChannel;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function sendRequest(string $method, string $path, array $data = []): ?Response
    {
        try {
            if ($this->debug) {
                ob_start();
                $this->request = $this->request->withOptions(['debug' => 'php://output']);
            }

            $response = $this->request->send($method, $path, $data);

            if ($this->debug) {
                $tracing = ob_get_clean();
                $this->writeLogs(
                    'Успешная отправка запроса',
                    [
                        'payload' => json_encode($data[RequestOptions::JSON] ?? $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                        'tracing' => $tracing,
                        'response' => $response->__toString(),
                    ]
                );
            }

            if (! $response->successful()) {
                $message = 'При отправке запроса '
                    .$this->getBaseUrl()
                    .'/'
                    .trim("{$path}", '/')
                    .' получен ответ со статусом '
                    .$response->status();
                $this->writeLogs(
                    $message,
                    [
                        'request' => json_encode($data[RequestOptions::JSON] ?? $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                        'response' => $response->__toString(),
                    ],
                    true
                );
            }

            $this->resetRequest();

            return $response;
        } catch (Exception $e) {
            $this->writeLogs(
                $e->getMessage(),
                [
                    'url' => $this->getBaseUrl().'/'.trim("{$path}", '/'),
                    'request' => json_encode($data[RequestOptions::JSON] ?? $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                    'stacktrace' => $e->getTraceAsString(),
                ],
                true
            );
        }

        $this->resetRequest();

        return null;
    }
}
