<?php
namespace App\Import\Storage;

use App\Import\DTO\Planning\AssetPlanItem;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class FilesystemAssetStorage implements AssetStorage
{
    public function __construct(
        private string $mediaDir,           // e.g. %kernel.project_dir%/public/media
        private string $mediaBaseUrl,       // e.g. /media
        private HttpClientInterface $http   // symfony/http-client
    ) {}

    public function fetchAndStore(AssetPlanItem $asset): string
    {
        // shard by hash for filesystem sanity: /aa/bb/<hash>.<ext>
        [$dir, $filename] = $this->targetPathParts($asset->expectedHash, $this->guessExt($asset));
        $absDir = rtrim($this->mediaDir, '/').'/'.$dir;
        $absPath = $absDir.'/'.$filename;

        if (!is_file($absPath)) {
            $bytes = $this->download($asset->sourceUrl);
            $got = hash('sha256', $bytes);
            if (strtolower($got) !== strtolower($asset->expectedHash)) {
                throw new \RuntimeException(sprintf('Hash mismatch for %s (expected %s, got %s)', $asset->sourceUrl, $asset->expectedHash, $got));
            }
            @mkdir($absDir, 0775, true);
            file_put_contents($absPath, $bytes);
        }

        return rtrim($this->mediaBaseUrl, '/').'/'.$dir.'/'.$filename;
    }

    private function targetPathParts(string $sha, ?string $ext): array
    {
        $d1 = substr($sha, 0, 2);
        $d2 = substr($sha, 2, 2);
        $file = $sha.($ext ? '.'.$ext : '');
        return ["$d1/$d2", $file];
    }

    private function download(string $url): string
    {
        // Support file:// or absolute paths for local sources
        if (str_starts_with($url, 'file://')) {
            return file_get_contents(substr($url, 7));
        }
        if (str_starts_with($url, '/')) {
            return file_get_contents($url);
        }

        $resp = $this->http->request('GET', $url, ['max_redirects' => 5, 'timeout' => 30]);
        if (200 !== $resp->getStatusCode()) {
            throw new \RuntimeException("Failed to download asset: $url");
        }
        return $resp->getContent();
    }

    private function guessExt(AssetPlanItem $asset): ?string
    {
        // Try from URL
        $path = parse_url($asset->sourceUrl, PHP_URL_PATH) ?: '';
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext && preg_match('/^[a-z0-9]{1,5}$/', $ext)) return $ext;

        // Try from MIME hint in targetPath or meta (if you add it)
        // Fallback: null (no extension)
        return null;
    }
}
