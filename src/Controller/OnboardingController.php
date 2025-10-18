<?php

namespace App\Controller;

use App\Service\SiteBuilder;
use App\Service\SiteTemplateLoader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OnboardingController extends AbstractController
{
  #[Route('/site/create/{templateId}', name: 'site_create_from_template')]
  public function createFromTemplate(
    string $templateId,
    SiteTemplateLoader $loader,
    SiteBuilder $builder
  ): Response {
        $templateData = $loader->load($templateId);
        
        $user = $this->getUser();
        $site = $builder->createFromTemplate($templateData, $templateData['name'] ?? 'New Site', $user);
        
        return $this->redirectToRoute('site_dashboard', [
          'id' => $site->getId(),
        ]);
    }

  #[Route('/site/new', name: 'site_template_select')]
  public function select(SiteTemplateLoader $loader): Response
  {
    return $this->render('onboard/select_template.html.twig', [
      'templates' => $loader->all(),
    ]);
  }

  #[Route('/site/new/template/{id}/preview/{slug}', name: 'site_template_preview', methods: ['GET'])]
  public function preview(
    string $id,
    string $slug,
    SiteTemplateLoader $loader,
    Request $req
  ): Response {
    $template = $loader->find($id);
    if (!$template) {
      throw $this->createNotFoundException('Unknown template');
    }
    
    $page = null;
    foreach ($template['pages'] as $p) {
      if ($p['slug'] === $slug) { $page = $p; break; }
    }
    if (!$page) {
      $page = $template['pages'][0] ?? null;
      if (!$page) {
        throw $this->createNotFoundException('Template has no pages');
      }
    }
    
    return $this->render('onboard/preview_template.html.twig', [
      'template' => $template,
      'page'     => $page,
    ]);
  }
}
