<?php

namespace App\Service\Export;

use App\Entity\Site;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Example service showing how to use SiteRenderer to export a complete
 * static site to disk for deployment to Netlify, Vercel, etc.
 */
final class SiteExporter
{
  public function __construct(
    private SiteRenderer $siteRenderer,
    private string $exportDir = '/tmp/site-exports',
  ) {}
  
  /**
   * Export a site to a deployment-ready directory structure.
   */
  public function export(Site $site): string
  {
    $artifact = $this->siteRenderer->render($site);
        
    $buildDir = $this->exportDir . '/' . $artifact->manifest['siteId'] . '/' . $artifact->manifest['buildId'];
    $fs = new Filesystem();
    
    // Create directory structure
    $fs->mkdir([$buildDir, "$buildDir/css", "$buildDir/js", "$buildDir/images"]);
    
    foreach ($artifact->pages as $page) {
      $path = $buildDir . $page['path'];
      
      if (str_ends_with($path, '/')) {
        $path .= 'index.html';
      } else if (!str_ends_with($path, '.html')) {
        $path .= '.html';
      }
      
      $fs->mkdir(dirname($path));
      file_put_contents($path, $page['html']);
    }
    
    foreach ($artifact->css as $css) {
      file_put_contents("$buildDir/css/{$css['hash']}.css", $css['content']);
    }
    
    foreach ($artifact->js as $js) {
      file_put_contents("$buildDir/js/{$js['hash']}.js", $js['content']);
    }
    
    if ($artifact->sitemap) {
      file_put_contents("$buildDir/sitemap.xml", $artifact->sitemap);
    }
    
    if ($artifact->robots) {
      file_put_contents("$buildDir/robots.txt", $artifact->robots);
    }
    
    if ($artifact->redirects) {
      file_put_contents("$buildDir/_redirects", $artifact->redirects);
    }
    
    if ($artifact->headers) {
      file_put_contents("$buildDir/_headers", $artifact->headers);
    }
    
    file_put_contents(
      "$buildDir/manifest.json",
      json_encode($artifact->manifest, JSON_PRETTY_PRINT)
    );
    
    return $buildDir;
  }
    
  /**
   * Create a deployable tarball.
   */
  public function exportTarball(Site $site): string
  {
    $buildDir = $this->export($site);
    $tarball = $buildDir . '.tar.gz';
    
    $phar = new \PharData($tarball);
    $phar->buildFromDirectory($buildDir);
    $phar->compress(\Phar::GZ);
    
    return $tarball;
  }
}
