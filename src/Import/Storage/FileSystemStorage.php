<?php
namespace App\Import\Storage;

use App\Import\DTO\Planning\AssetPlanItem;
use App\Import\Exception\AssetDownloadException;
use App\Import\Exception\AssetValidationException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

final class FilesystemAssetStorage implements AssetStorage
{
  private const MAX_RETRIES = 3;
  private const RETRY_DELAY_MS = [100, 500, 1000];
  
  public function __construct(
    private string $mediaDir,           // e.g. %kernel.project_dir%/public/media
    private string $mediaBaseUrl,       // e.g. /media
    private HttpClientInterface $http,   // symfony/http-client
    private LoggerInterface $logger
  ) {}
  
  public function fetchAndStore(AssetPlanItem $asset): string
  {
      $this->validateAsset($asset);
      
      // shard by hash for filesystem sanity: /aa/bb/<hash>.<ext>
      [$dir, $filename] = $this->targetPathParts($asset->expectedHash, $this->guessExt($asset));
      $absDir = rtrim($this->mediaDir, '/').'/'.$dir;
      $absPath = $absDir.'/'.$filename;
      
      if (!is_file($absPath)) {
        $bytes = $this->downloadWithRetry($asset->sourceUrl);
        $this->validateDownloadedContent($bytes, $asset);
        
        @mkdir($absDir, 0775, true);
        if (false === file_put_contents($absPath, $bytes)) {
          throw new AssetDownloadException("Failed to write asset to disk: $absPath");
        }
        
        $this->logger->info('Asset stored successfully', [
          'sourceUrl' => $asset->sourceUrl,
          'targetPath' => $absPath,
          'size' => strlen($bytes)
            ]);
      } else {
        $this->logger->debug('Asset already exists', ['path' => $absPath]);
      }      
        
      return rtrim($this->mediaBaseUrl, '/').'/'.$dir.'/'.$filename;
    }

  private function validateAsset(AssetPlanItem $asset): void
    {
        if (!$this->isValidUrl($asset->sourceUrl)) {
          throw new AssetValidationException("Invalid source URL: {$asset->sourceUrl}");
        }
        
        if (!preg_match('/^[a-f0-9]{64}$/i', $asset->expectedHash)) {
          throw new AssetValidationException("Invalid hash format: {$asset->expectedHash}");
        }
        
        if (empty($asset->targetPath)) {
          throw new AssetValidationException("Target path cannot be empty");
        }
    }

  private function isValidUrl(string $url): bool
    {
        // Allow file:// URLs for local development
        if (str_starts_with($url, 'file://')) {
          return true;
        }
        
        // Allow absolute local paths for testing
        if (str_starts_with($url, '/')) {
          return is_readable($url);
        }
        
        // Validate HTTP/HTTPS URLs
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
          return false;
        }
        
        $parsed = parse_url($url);
        if (!$parsed || !in_array($parsed['scheme'] ?? '', ['http', 'https'])) {
          return false;
        }
        
        return true;
    }

  private function downloadWithRetry(string $url): string
  {
      $lastException = null;
        
      for ($attempt = 0; $attempt < self::MAX_RETRIES; $attempt++) {
        try {
          return $this->download($url);
        } catch (\Exception $e) {
          $lastException = $e;
          
          $this->logger->warning('Asset download attempt failed', [
            'url' => $url,
            'attempt' => $attempt + 1,
            'error' => $e->getMessage()
                ]);
          
          if ($attempt < self::MAX_RETRIES - 1) {
            usleep(self::RETRY_DELAY_MS[$attempt] * 1000);
          }
        }
      }
      
      throw new AssetDownloadException(
        "Failed to download asset after " . self::MAX_RETRIES . " attempts: $url", 
        0, 
        $lastException
      );
    }

  private function download(string $url): string
  {
      // Support file:// or absolute paths for local sources
      if (str_starts_with($url, 'file://')) {
        $content = file_get_contents(substr($url, 7));
        if (false === $content) {
          throw new AssetDownloadException("Failed to read local file: $url");
        }
        return $content;
      }
        
        if (str_starts_with($url, '/')) {
          $content = file_get_contents($url);
          if (false === $content) {
            throw new AssetDownloadException("Failed to read local file: $url");
          }
          return $content;
        }
        
        $resp = $this->http->request('GET', $url, [
          'max_redirects' => 5, 
          'timeout' => 30,
          'headers' => [
            'User-Agent' => 'ImportBot/1.0'
          ]
        ]);
        
        $statusCode = $resp->getStatusCode();
        if ($statusCode !== 200) {
          throw new AssetDownloadException("HTTP $statusCode error downloading asset: $url");
        }
        
        return $resp->getContent();
    }

  private function validateDownloadedContent(string $content, AssetPlanItem $asset): void
  {
      $actualHash = hash('sha256', $content);
      if (strtolower($actualHash) !== strtolower($asset->expectedHash)) {
        throw new AssetValidationException(sprintf(
          'Hash mismatch for %s (expected %s, got %s)', 
          $asset->sourceUrl, 
          $asset->expectedHash, 
          $actualHash
        ));
      }
      
      // Validate file size if provided
      if ($asset->expectedSize && strlen($content) !== $asset->expectedSize) {
        $this->logger->warning('File size mismatch', [
          'sourceUrl' => $asset->sourceUrl,
          'expectedSize' => $asset->expectedSize,
          'actualSize' => strlen($content)
            ]);
      }
    }
  
  private function targetPathParts(string $sha, ?string $ext): array
  {
      $d1 = substr($sha, 0, 2);
      $d2 = substr($sha, 2, 2);
      $file = $sha.($ext ? '.'.$ext : '');
      return ["$d1/$d2", $file];
    }
  
  private function guessExt(AssetPlanItem $asset): ?string
  {
      $path = parse_url($asset->sourceUrl, PHP_URL_PATH) ?: '';
      $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
      if ($ext && preg_match('/^[a-z0-9]{1,5}$/', $ext)) {
        return $ext;
      }
      
      $ext = strtolower(pathinfo($asset->targetPath, PATHINFO_EXTENSION));
      if ($ext && preg_match('/^[a-z0-9]{1,5}$/', $ext)) {
        return $ext;
      }
      
      // TODO: Try from MIME hint in targetPath or meta
      // Fallback: null (no extension)
      return null;
    }
}
