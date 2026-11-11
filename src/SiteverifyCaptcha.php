<?php

namespace NineteenNinetyFour\Captcha;

use NineteenNinetyFour\Captcha\Http\Client;

/**
 * A captcha checked by posting its token to the provider's "siteverify"
 * address, as Turnstile, hCaptcha and reCAPTCHA all work. A failed check,
 * or a provider that can't be reached, is a refusal.
 */
abstract class SiteverifyCaptcha implements Captcha
{
    /**
     * @param  array<string, mixed>  $options
     */
    final public function __construct(
        protected readonly string $siteKey,
        protected readonly string $secretKey,
        protected readonly Client $http,
        protected readonly array $options = [],
    ) {}

    abstract protected function endpoint(): string;

    public function verify(?string $token, ?string $ip = null): bool
    {
        if ($token === null || $token === '' || strlen($token) > 8192) {
            return false;
        }

        $result = $this->http->postForm($this->endpoint(), $this->payload($token, $ip));

        return is_array($result) && $this->passed($result);
    }

    /**
     * @return array<string, scalar|null>
     */
    protected function payload(string $token, ?string $ip): array
    {
        return ['secret' => $this->secretKey, 'response' => $token, 'remoteip' => $ip];
    }

    /**
     * @param  array<mixed>  $result
     */
    protected function passed(array $result): bool
    {
        return ($result['success'] ?? false) === true;
    }

    protected function attr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
