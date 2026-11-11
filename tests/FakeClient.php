<?php

namespace NineteenNinetyFour\Captcha\Tests;

use NineteenNinetyFour\Captcha\Http\Client;

/**
 * Answers requests from a list, and remembers them.
 */
final class FakeClient implements Client
{
    /** @var array<int, array{url: string, data: array<string, scalar|null>}> */
    public array $requests = [];

    /**
     * @param  array<int, array<mixed>|null>  $replies
     */
    public function __construct(private array $replies = []) {}

    public function postForm(string $url, array $form): ?array
    {
        $this->requests[] = ['url' => $url, 'data' => $form];

        return array_shift($this->replies);
    }
}
