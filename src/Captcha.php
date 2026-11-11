<?php

namespace NineteenNinetyFour\Captcha;

/**
 * A captcha on a form.
 */
interface Captcha
{
    /**
     * The HTML that goes in the form: the widget and its script. It has
     * data-captcha on an element, so a template can tell it's there.
     */
    public function widget(): string;

    /**
     * Whether the visitor passed, from the token the widget put in the form.
     * A failed check, or a provider that can't be reached, is a refusal.
     */
    public function verify(?string $token, ?string $ip = null): bool;

    /**
     * The form field the widget fills in.
     */
    public function field(): string;
}
