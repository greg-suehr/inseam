<?php

namespace App\Controller;

use App\Controller\Admin\SiteCrudController;
use App\Entity\Asset;
use App\Entity\Category;
use App\Entity\Content;
use App\Entity\Page;
use App\Entity\ProfileUser;
use App\Repository\CategoryRepository;
use App\Service\SiteContext;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[AdminDashboard(routePath: '/site', routeName: 'site_admin')]
class InstanceDashboardController extends AbstractDashboardController
{
    public function __construct(
      private AdminUrlGenerator $adminUrlGenerator,
      private CategoryRepository $categoryRepo,
      private SiteContext      $siteContext,
    ) {}
  
  #[Route("/site", name: "site_dashboard")]
  public function index(): Response
  {
    $site = $this->siteContext->getCurrentSite();
      
    return $this->render('site/instance.html.twig', [
      'site' =>  $site,
    ]
    );
  }

  public function configureDashboard(): Dashboard
  {
    $site = $this->siteContext->getCurrentSite();
    return Dashboard::new()
        ->setTitle($site->getName());
  }

  public function configureMenuItems(): iterable
  {
      yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
      yield MenuItem::linkToCrud('Pages', 'fa fa-sitemap', Page::class);      
      yield MenuItem::linkToCrud('Assets', 'fa fa-image', Asset::class);      
      yield MenuItem::linkToCrud('Categories', 'fa fa-list', Category::class);
      yield MenuItem::linkToCrud('Content', 'fa fa-file', Content::class);

      $categories = $this->categoryRepo->findAll(); // pulls all entries :contentReference[oaicite:0]{index=0}
      foreach ($categories as $category) {
        
        $url = $this->generateUrl('content_new', [
          'category' => $category->getId(),
        ]);
        
        yield MenuItem::linkToURL(
          sprintf('New %s!', $category->getName()),
          'fa fa-plus-circle',
          $url
        );
      }
    }
}
