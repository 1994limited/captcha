<?php

namespace NineteenNinetyFour\Captcha;

use InvalidArgumentException;
use NineteenNinetyFour\Captcha\Http\Client;

/**
 * The captchas that come with the package, by name.
 */
final class Captchas
{
    /** @var array<string, class-string<SiteverifyCaptcha>> */
    public const DRIVERS = [
        'turnstile' => Turnstile::class,
        'hcaptcha' => HCaptcha::class,
        'recaptcha' => Recaptcha::class,
        'recaptcha_v3' => RecaptchaV3::class,
    ];

    /** What each is called, for a settings page. */
    public const LABELS = [
        'turnstile' => 'Cloudflare Turnstile',
        'hcaptcha' => 'hCaptcha',
        'recaptcha' => 'Google reCAPTCHA v2 (checkbox)',
        'recaptcha_v3' => 'Google reCAPTCHA v3 (scored, nothing to tick)',
    ];

    /**
     * One of the built-in captchas, or null without both keys (no captcha).
     *
     * @param  array<string, mixed>  $options  recaptcha_v3: "action" (default "submit") and "score" (default 0.5)
     *
     * @throws InvalidArgumentException for a driver that isn't one of these
     */
    public static function make(string $driver, ?string $siteKey, ?string $secretKey, Client $http, array $options = []): ?Captcha
    {
        $class = self::DRIVERS[$driver] ?? null;

        if ($class === null) {
            throw new InvalidArgumentException("Unknown captcha [{$driver}]. Use turnstile, hcaptcha, recaptcha or recaptcha_v3.");
        }

        $siteKey = trim((string) $siteKey);
        $secretKey = trim((string) $secretKey);

        if ($siteKey === '' || $secretKey === '') {
            return null;
        }

        return new $class($siteKey, $secretKey, $http, $options);
    }

    public static function builtIn(string $driver): bool
    {
        return isset(self::DRIVERS[$driver]);
    }
}
