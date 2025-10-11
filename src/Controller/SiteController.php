<?php

namespace App\Controller;

use App\Service\SiteBuilder;
use App\Service\SiteTemplateLoader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SiteController extends AbstractController
{
  #[Route('/site/create/{template}', name: 'site_create_from_template')]
  public function createFromTemplate(
    string $template,
    SiteTemplateLoader $loader,
    SiteBuilder $builder
  ): Response {
        $templateData = $loader->load($template);
        
        $user = $this->getUser();
        $site = $builder->createFromTemplate($templateData, $templateData['name'] ?? 'New Site', $user->getUserIdentifier());
        
        return $this->redirectToRoute('site_dashboard', [
          'id' => $site->getId(),
        ]);
    }
}
