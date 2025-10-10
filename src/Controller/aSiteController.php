<?php

namespace App\Controller;

use App\Entity\Show;
use App\Repository\ShowRepository;
use App\Repository\AdminRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class aSiteController extends AbstractController
{
    #[Route('/', name: 'app_site')]
    public function index(): Response
    {
        return $this->render('index.html.twig', [
            'controller_name' => 'SiteController',
        ]);
    }
  
  #[Route('/home', name: 'app_home')]
  public function home(Request $request, ShowRepository $showRepo, AdminRepository $userRepo): Response
    {
        $adminUser = $userRepo->getUser('inseam');
        
        return $this->render('myspace.html.twig', [
            'controller_name' => 'SiteController',
            'user'            => $adminUser,
            'last_login'      => $adminUser ? $adminUser->getLastLogin() : null,
            'upcoming_shows'  => $showRepo->findUpcoming(),
        ]);
    }

  #[Route('/bio', name: 'app_bio')]
  public function bio(Request $request, AdminRepository $userRepo): Response
    {
        $adminUser = $userRepo->getUser('inseam');
        
        return $this->render('bio.html.twig', [
            'controller_name' => 'SiteController',
            'last_login'      => $adminUser ? $adminUser->getLastLogin() : null,
        ]);
    }
}
