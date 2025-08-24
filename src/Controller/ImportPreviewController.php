<?php

namespace App\Controller;

use App\Repository\PageContentRepository;
use App\Service\AssetUrlResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ImportPreviewController extends AbstractController
{
  #[Route('/import/preview/html/{id}', name: 'import_preview_html')]
  public function __invoke(string $id, PageContentRepository $repo): Response
  {
    $page = $repo->find($id);
    if (!$page) throw $this->createNotFoundException();
    
    $html = $page->getContent();
    $html = $this->injectBaseHref($html, $page->getRoute());
    $html = $this->stripScripts($html);

    $resp = $this->render('import_preview/html_wrapper.html.twig', [
      'html' => $html,
      'src'  => $page->getRoute(),
    ]);

    $resp->headers->set('Content-Security-Policy', "default-src 'self' data: blob: *; script-src 'none'; style-src 'unsafe-inline' *; img-src * data: blob:; frame-ancestors 'none'");
    return $resp;
  }
  
  private function injectBaseHref(string $html, string $url): string
  {
    if (preg_match('~<base\s+href=~i', $html)) return $html;
    
    $doc = new \DOMDocument('1.0', 'UTF-8');
    @$doc->loadHTML($html, LIBXML_NONET | LIBXML_COMPACT | LIBXML_BIGLINES);
    $head = $doc->getElementsByTagName('head')->item(0);
    if (!$head) return $html;
    
    $base = $doc->createElement('base');
    $base->setAttribute('href', rtrim($url, '/').'/');
    $head->insertBefore($base, $head->firstChild);
    
    return (string)$doc->saveHTML();
  }

  private function stripScripts(string $html): string
  {
    $doc = new \DOMDocument('1.0', 'UTF-8');
    @$doc->loadHTML($html, LIBXML_NONET | LIBXML_COMPACT | LIBXML_BIGLINES);
    $xp = new \DOMXPath($doc);
    foreach ($xp->query('//script') as $n) $n->parentNode?->removeChild($n);
    foreach ($xp->query('//iframe|//form') as $n) $n->parentNode?->removeChild($n);
      
    return (string)$doc->saveHTML();
  }
}
