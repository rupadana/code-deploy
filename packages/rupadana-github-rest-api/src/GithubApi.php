<?php

namespace Rupadana\GithubApi;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class GithubApi
{
    protected $client = null;

    protected $baseUrl = 'https://api.github.com';

    protected $endpoint = '/';

    public static function make($token): static
    {
        return new static($token);
    }

    public function __construct(public ?string $token)
    {
        $this->client = Http::withHeader('Authorization', 'token '.$token)
            ->baseUrl($this->baseUrl);
    }

    public function endpoint(string $endpoint): static
    {
        $this->endpoint .= str($endpoint)->finish('/');

        return $this;
    }

    public function get(array $query = []): Collection
    {
        // dd(str($this->endpoint)->replaceLast('/', '')->toString());
        return $this->client->get(str($this->endpoint)->replaceLast('/', '')->toString(), $query)->collect();
    }

    public function user(): static
    {
        return $this->endpoint('user');
    }

    public function users(string $username = ''): static
    {
        return $this->endpoint('users/'.$username);
    }

    public function repos(string $repository = ''): static
    {
        return $this->endpoint('repos/'.$repository);
    }

    public function commits(): static
    {
        return $this->endpoint('commits');
    }

    public function hooks(): static
    {
        return $this->endpoint('hooks');
    }

    public function post(array $body = []): Collection
    {
        return $this->client->post(str($this->endpoint)->replaceLast('/', '')->toString(), $body)->collect();
    }
}
