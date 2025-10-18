<?php
  
namespace App\Controller\Tenant;

use App\Entity\Page;
use App\Entity\Site;
use App\Service\SiteContext;
use App\Service\Render\SiteRenderer;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
  The Tenant PageController serves client site content directly from a `page.data`
  blockTree through the Twig templating system.
 */
class PageController extends AbstractController
{  
  public function __construct(    
    private EntityManagerInterface $em,
    private LoggerInterface $logger,
    private SiteContext $siteContext,
    private SiteRenderer $siteRenderer,
  )
  {}

  #[Route("/p/{siteDomain}/{slug}", name: "tenant_page_show", requirements: ["siteDomain" => ".+", "slug"=>".+"])]
  public function show(Request $request, string $siteDomain, string $slug): Response
  {
    $site = $this->em->getRepository(Site::class) ->findOneBy([
      'domain' => $siteDomain
    ]);
    
    if (!$site) {
      $this->logger->info("Site \"$site\" not found");
      throw new NotFoundHttpException();
    }
    
    $page = $this->em->getRepository(Page::class)->findOneBy([
      'site'      => $site->getId(),
      'slug'         => $slug,
      'is_published' => true,
    ]); 
    
    if (!$page) {
      $this->logger->info("Failed search for slug \"$slug\" on site \"$site\"");
      throw new NotFoundHttpException();
    }

    $htmlContent = $page->getHtmlContent();
        
    if (empty($htmlContent)) {
      $this->logger->info("Regenerating HTML for page {$page->getId()} on-demand");
            
      try {
        $rendered = $this->siteRenderer->renderPage($page, $site);
        $page->setHtmlContent($rendered['html']);
        
        $this->em->flush();
        
        $htmlContent = $rendered['html'];
        
        $this->logger->info("Successfully regenerated HTML for page {$page->getId()}");
      } catch (\Throwable $e) {
        $this->logger->error("Failed to render page {$page->getId()}: {$e->getMessage()}");
        
        return $this->render('tenant/page.html.twig', [
          'page' => $page,
          'site' => $site,
        ]);
      }
    }

    return new Response($htmlContent, 200, [
      'Content-Type' => 'text/html; charset=UTF-8',
      'X-Render-Mode' => empty($page->getHtmlContent()) ? 'on-demand' : 'cached',
    ]);
  }
}
