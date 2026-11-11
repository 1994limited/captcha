# Captcha

Cloudflare Turnstile, hCaptcha and Google reCAPTCHA (v2 and v3) behind one small interface, for 1994's Craft CMS plugins and Statamic addons. No framework and no dependencies: the plugin hands it its CMS's HTTP client.

```bash
composer require 1994/captcha
```

## Use

```php
use NineteenNinetyFour\Captcha\Captchas;

// null when either key is empty: no captcha.
$captcha = Captchas::make('turnstile', $siteKey, $secretKey, $http);

// In the form: the widget and its script.
echo $captcha->widget();

// When the form comes back.
$passed = $captcha->verify($request->input($captcha->field()), $request->ip());
```

Drivers: `turnstile`, `hcaptcha`, `recaptcha` (the v2 checkbox) and `recaptcha_v3` (scored, nothing to tick). `Captchas::LABELS` names them for a settings page.

reCAPTCHA v3 takes two options: `action`, which this form's tokens are made for and checked against (default `submit`; letters, digits, `/` and `_`), and `score`, the lowest score taken as a person (default `0.5`).

```php
Captchas::make('recaptcha_v3', $siteKey, $secretKey, $http, ['action' => 'newsletter', 'score' => 0.5]);
```

A token that's missing, too long, refused, or can't be checked because the provider is down, fails. Every widget has a `data-captcha` attribute, so a template can tell one is there.

## The HTTP client

A captcha posts its token to the provider. Give it your CMS's client by implementing `NineteenNinetyFour\Captcha\Http\Client`:

```php
// Laravel and Statamic
final class LaravelClient implements Client
{
    public function postForm(string $url, array $form): ?array
    {
        try {
            $response = Http::asForm()->timeout(10)->post($url, $form);
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        return $response->successful() ? (array) $response->json() : null;
    }
}
```

On Craft, the same with `Craft::createGuzzleClient()`.

## Your own

Implement `NineteenNinetyFour\Captcha\Captcha`: `widget()`, `verify($token, $ip)` and `field()`.

## Licence

MIT. See [LICENSE.md](LICENSE.md).
