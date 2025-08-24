<?php

namespace App\Import\Render;

use Facebook\WebDriver\WebDriverBy;
use Symfony\Component\Panther\Client;

final class PantherHeadlessRenderer implements HeadlessRenderer
{
  public function __construct(
    private readonly string $chromePath = 'chrome',
    private readonly int $connectTimeoutMs = 30000
  ) {}
  
  public function renderToHtml(string $url, ?string $waitSelector = null, int $waitTimeoutMs = 8000): string
  {
  
    $client = Client::createChromeClient(null, [
      '--headless=new',
      '--disable-gpu',
      '--no-sandbox',
      '--disable-dev-shm-usage',
    ], [], (int)ceil($this->connectTimeoutMs / 1000));
    
    try {
      $client->request('GET', $url);
      
      if ($waitSelector !== null && $waitSelector !== '') {
        $client->waitFor($waitSelector, $waitTimeoutMs);
      } else {
        // Fallback: wait for "network idle" approximation via a micro wait and DOM stable check
        $this->waitDomStabilized($client, $waitTimeoutMs);
      }
      
      // Outer HTML grab
      $html = $client->executeScript('return document.documentElement.outerHTML;') ?? '';
      return is_string($html) ? $html : '';
    } finally {
      // close to avoid zombie chromes in long imports
      $client->quit();
    }
    }

    private function waitDomStabilized(Client $client, int $timeoutMs): void
    {
        $deadline = microtime(true) + $timeoutMs / 1000;
        $lastLen = -1;
        $stableTicks = 0;

        while (microtime(true) < $deadline) {
            $len = (int)$client->executeScript('return document.documentElement.outerHTML.length;');
            if ($len === $lastLen) {
                $stableTicks++;
                if ($stableTicks >= 3) {
                    return;
                }
            } else {
                $stableTicks = 0;
                $lastLen = $len;
            }
            usleep(150_000); // 150ms
        }
    }
}
