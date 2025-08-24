<?php

namespace App\Import\Render;

interface HeadlessRenderer
{
  /**
   * @param string      $url            The URL to render
   * @param string|null $waitSelector   CSS selector to wait for (eg. 'main,[role="main"]')
   * @param int         $waitTimeoutMs  Milliseconds to wait for selector/network idle
   */
  public function renderToHtml(string $url, ?string $waitSelector = null, int $waitTimeoutMs = 8000): string;
}
