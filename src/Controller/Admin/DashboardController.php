<?php

namespace App\Controller\Admin;

use App\Entity\Admin;
use App\Entity\Blurb;
use App\Entity\BioLink;
use App\Entity\BioTag;
use App\Entity\Show;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->redirectToRoute('admin_show_index');

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        // return $this->render('some/path/my-dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Inseam');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Admins', 'fas fa-user', Admin::class);
        yield MenuItem::linkToCrud('Blurbs', 'fas fa-scroll', Blurb::class);
        yield MenuItem::linkToCrud('Links', 'fas fa-chain', BioLink::class);        
        yield MenuItem::linkToCrud('Tags', 'fas fa-tag', BioTag::class);
        yield MenuItem::linkToCrud('Shows', 'fas fa-calendar', Show::class);
    }
}
