<?php

namespace NineteenNinetyFour\Captcha\Tests;

use InvalidArgumentException;
use NineteenNinetyFour\Captcha\Captchas;
use NineteenNinetyFour\Captcha\HCaptcha;
use NineteenNinetyFour\Captcha\RecaptchaV3;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CaptchaTest extends TestCase
{
    /**
     * @return array<int, array{string, string, string, string}>
     */
    public static function drivers(): array
    {
        return [
            ['turnstile', 'cf-turnstile-response', 'https://challenges.cloudflare.com/turnstile/v0/siteverify', 'class="cf-turnstile"'],
            ['hcaptcha', 'h-captcha-response', 'https://api.hcaptcha.com/siteverify', 'class="h-captcha"'],
            ['recaptcha', 'g-recaptcha-response', 'https://www.google.com/recaptcha/api/siteverify', 'class="g-recaptcha"'],
        ];
    }

    #[Test]
    #[DataProvider('drivers')]
    public function each_captcha_draws_its_widget_and_checks_the_token(string $driver, string $field, string $endpoint, string $widget): void
    {
        $http = new FakeClient([['success' => true], ['success' => false], null, ['success' => 'true']]);
        $captcha = Captchas::make($driver, 'site"key', 'secret', $http);

        $this->assertNotNull($captcha);
        $this->assertSame($field, $captcha->field());
        $this->assertStringContainsString($widget, $captcha->widget());
        $this->assertStringContainsString('data-sitekey="site&quot;key"', $captcha->widget());
        $this->assertStringContainsString('data-captcha', $captcha->widget());

        $this->assertTrue($captcha->verify('good', '203.0.113.9'));
        $this->assertFalse($captcha->verify('refused'));
        $this->assertFalse($captcha->verify('provider down'));
        $this->assertFalse($captcha->verify('not strictly true'));
        $this->assertCount(4, $http->requests);

        $this->assertSame($endpoint, $http->requests[0]['url']);
        $this->assertSame('secret', $http->requests[0]['data']['secret']);
        $this->assertSame('good', $http->requests[0]['data']['response']);
        $this->assertSame('203.0.113.9', $http->requests[0]['data']['remoteip']);
    }

    #[Test]
    public function no_token_is_refused_without_asking_the_provider(): void
    {
        $http = new FakeClient([['success' => true]]);
        $captcha = Captchas::make('turnstile', 'site', 'secret', $http);

        $this->assertNotNull($captcha);
        $this->assertFalse($captcha->verify(null));
        $this->assertFalse($captcha->verify(''));
        $this->assertFalse($captcha->verify(str_repeat('a', 8193)));
        $this->assertCount(0, $http->requests);
    }

    #[Test]
    public function hcaptcha_also_sends_the_site_key(): void
    {
        $http = new FakeClient([['success' => true]]);
        (new HCaptcha('site', 'secret', $http))->verify('token');

        $this->assertSame('site', $http->requests[0]['data']['sitekey']);
    }

    #[Test]
    public function recaptcha_v3_needs_the_score_and_this_form_s_action(): void
    {
        $http = new FakeClient([
            ['success' => true, 'score' => 0.9, 'action' => 'newsletter'],
            ['success' => true, 'score' => 0.6, 'action' => 'newsletter'],
            ['success' => true, 'score' => 0.9, 'action' => 'login'],
            ['success' => true, 'action' => 'newsletter'],
        ]);
        $captcha = new RecaptchaV3('site', 'secret', $http, ['score' => 0.7, 'action' => 'newsletter']);

        $this->assertStringContainsString('api.js?render=site', $captcha->widget());
        $this->assertStringContainsString('grecaptcha.execute("site",{action:"newsletter"})', $captcha->widget());
        $this->assertTrue($captcha->verify('a'));
        $this->assertFalse($captcha->verify('b'));
        $this->assertFalse($captcha->verify('c'));
        $this->assertFalse($captcha->verify('d'));
    }

    #[Test]
    public function recaptcha_v3_falls_back_to_submit_for_an_action_google_would_refuse(): void
    {
        $this->assertSame('submit', (new RecaptchaV3('s', 'k', new FakeClient))->action());
        $this->assertSame('submit', (new RecaptchaV3('s', 'k', new FakeClient, ['action' => '"});alert(1)//']))->action());
        $this->assertStringNotContainsString('alert', (new RecaptchaV3('s', 'k', new FakeClient, ['action' => '"});alert(1)//']))->widget());
    }

    #[Test]
    public function a_site_key_cannot_break_out_of_the_script(): void
    {
        $widget = (new RecaptchaV3('</script><script>alert(1)</script>', 'k', new FakeClient))->widget();

        $this->assertStringNotContainsString('</script><script>alert', $widget);
    }

    #[Test]
    public function without_both_keys_there_is_no_captcha_and_unknown_drivers_are_refused(): void
    {
        $this->assertNull(Captchas::make('turnstile', 'site', '', new FakeClient));
        $this->assertNull(Captchas::make('turnstile', '  ', 'secret', new FakeClient));
        $this->assertNull(Captchas::make('turnstile', null, null, new FakeClient));
        $this->assertTrue(Captchas::builtIn('hcaptcha'));
        $this->assertFalse(Captchas::builtIn('App\\MyCaptcha'));
        $this->assertSame(array_keys(Captchas::DRIVERS), array_keys(Captchas::LABELS));

        $this->expectException(InvalidArgumentException::class);
        Captchas::make('nope', 'a', 'b', new FakeClient);
    }
}
