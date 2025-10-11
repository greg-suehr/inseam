<?php

namespace App\Controller;

use App\Controller\Admin\SiteCrudController;
use App\Entity\Site;
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
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[AdminDashboard(routePath: '/ishere', routeName: 'ishere_admin')]
class IsHereDashboardController extends AbstractDashboardController
{
    public function __construct(
        private AdminUrlGenerator $adminUrlGenerator,
        private SiteContext      $siteContext,
        private RequestStack     $requestStack,
        private CategoryRepository $categoryRepo,
    ) {}
  
    #[Route("/ishere", name: "ishere_dashboard")]
    public function index(): Response
    {
        $user = $this->getUser();
        return $this->redirectToRoute('ishere_admin_site_index');
    }

  #[Route('/ishere/sites/{id}/view', name: 'ishere_site_view', methods: ['POST','GET'])]
  public function viewSite(Site $site): Response
  {
    $user = $this->getUser();
    # TODO: reinforce access control
    #if (!$user || !$user->getSites()->contains($site)) {
    #  throw new AccessDeniedException('You do not have access to this site.');
    #}
    
    $session = $this->requestStack->getCurrentRequest()->getSession();
    $session->set('current_site_id', $site->getId());
    
    return $this->redirectToRoute('site_dashboard', );
  }
  
  public function configureMenuItems(): iterable
  {
    yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
    yield MenuItem::linkToCrud('Sites', 'fa fa-database', Site::class);
    yield MenuItem::linkToCrud('DNS', 'fa fa-globe', Site::class);        
    yield MenuItem::linkToCrud('Analytics', 'fa fa-chart-simple', Site::class);
    yield MenuItem::linkToCrud('Settings', 'fa fa-sliders', Site::class);
  }
}
