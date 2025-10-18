<?php
  
namespace App\Controller\Tenant;

use App\Entity\Page;
use App\Entity\Site;
use App\Service\SiteContext;
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
    private SiteContext $siteContext;
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    public function __construct(SiteContext $siteContext, EntityManagerInterface $em,
                                LoggerInterface $logger
    )
    {
        $this->siteContext = $siteContext;
        $this->em          = $em;
        $this->logger      = $logger;
    }

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

        return $this->render('tenant/page.html.twig', [
            'page' => $page,
            'site' => $site,
        ]);
    }
}

?>
