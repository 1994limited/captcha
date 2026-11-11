<?php

namespace NineteenNinetyFour\Captcha;

/**
 * Google reCAPTCHA v3: nothing to tick. Google scores each visit from 0 (a
 * bot) to 1 (a person); visits scoring under the "score" option (0.5) are
 * refused, and so are tokens made for another action than the "action"
 * option ("submit"), so a token from one form can't be used on another.
 */
class RecaptchaV3 extends Recaptcha
{
    public function widget(): string
    {
        $key = $this->attr($this->siteKey);
        $json = fn (string $value) => json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        // Asks Google for a token as the form is sent, then sends it.
        return '<input type="hidden" name="g-recaptcha-response" value="" data-captcha data-captcha-recaptcha>'
            .'<script src="https://www.google.com/recaptcha/api.js?render='.$key.'" async defer></script>'
            .'<script>(function(){document.querySelectorAll("[data-captcha-recaptcha]").forEach(function(input){var form=input.form;if(!form||form.dataset.captchaRecaptcha)return;form.dataset.captchaRecaptcha=1;form.addEventListener("submit",function(event){if(input.value||!window.grecaptcha)return;event.preventDefault();grecaptcha.ready(function(){grecaptcha.execute('.$json($this->siteKey).',{action:'.$json($this->action()).'}).then(function(token){input.value=token;form.requestSubmit?form.requestSubmit():form.submit()})})})})})();</script>';
    }

    public function action(): string
    {
        $action = $this->options['action'] ?? null;

        // Google allows letters, digits, slashes and underscores.
        return is_string($action) && preg_match('#^[A-Za-z0-9/_]+$#', $action) ? $action : 'submit';
    }

    protected function passed(array $result): bool
    {
        $threshold = is_numeric($this->options['score'] ?? null) ? (float) $this->options['score'] : 0.5;

        return parent::passed($result)
            && ($result['action'] ?? null) === $this->action()
            && is_numeric($result['score'] ?? null)
            && (float) $result['score'] >= $threshold;
    }
}
