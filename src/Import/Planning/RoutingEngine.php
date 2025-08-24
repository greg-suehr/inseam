<?php
namespace App\Import\Planning;

use App\Import\DTO\Planning\RoutePlanItem;
use Psr\Log\LoggerInterface;

final class RoutingEngine
{
  public function __construct(private LoggerInterface $logger) {}

  public function resolve(RoutePlanItem $rp): string
  {
      try {
        $route = $this->normalizeRoute($rp->route, $rp->slug);
        
        $this->logger->debug('Route resolved', [
          'pageId' => $rp->pageId,
          'oldUrl' => $rp->oldUrl,
          'slug' => $rp->slug,
          'finalRoute' => $route
        ]);
        
        return $route;
        
      } catch (\Exception $e) {
        $this->logger->error('Route resolution failed', [
          'pageId' => $rp->pageId,
          'error' => $e->getMessage()
            ]);
        
            // Fallback to a safe route
        return '/imported/' . $this->sanitizeSlug($rp->slug ?: $rp->pageId);
      }
    }

  private function normalizeRoute(string $route, string $slug): string
  {
        $route = str_replace('{slug}', $this->sanitizeSlug($slug), $route);
        
        if (!str_starts_with($route, '/')) {
          $route = '/' . $route;
        }
        
        // Remove trailing slash unless root
        if ($route !== '/' && str_ends_with($route, '/')) {
          $route = rtrim($route, '/');
        }
        
        return $route;
    }

  private function sanitizeSlug(string $slug): string
  {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return $slug ?: 'untitled';
    }
}
