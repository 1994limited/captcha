<?php

namespace NineteenNinetyFour\Captcha\Http;

/**
 * The one request a captcha check needs, made by the CMS's own HTTP client.
 */
interface Client
{
    /**
     * Post a form; the decoded JSON reply, or null when the request fails.
     *
     * @param  array<string, scalar|null>  $form
     * @return array<mixed>|null
     */
    public function postForm(string $url, array $form): ?array;
}
