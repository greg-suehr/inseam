<?php

namespace App\Controller;

use App\Entity\Show;
use App\Repository\ShowRepository;
use App\Repository\AdminRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class aRssFeedController extends AbstractController
{
    #[Route('/wct', name: 'wct_site')]
    public function index(): Response
    {
        return $this->render('wct/index.html.twig', [
          'feedItems' => [],
        ]);
    }
  
  #[Route('/wct/about', name: 'wct_about')]
  public function home(Request $request, ShowRepository $showRepo, AdminRepository $userRepo): Response
    {
        return $this->render('wct/about.html.twig', [
        ]);
    }

  #[Route('/wct/archive', name: 'wct_archive')]
  public function archive(Request $request, AdminRepository $userRepo): Response
    {
        $adminUser = $userRepo->getUser('inseam');
        
        return $this->render('wct/archive.html.twig', [
        ]);
    }    

  #[Route('/wct/bio', name: 'wct_bio')]
  public function bio(Request $request, AdminRepository $userRepo): Response
    {
        $adminUser = $userRepo->getUser('inseam');
        
        return $this->render('wct/bio.html.twig', [
        ]);
    }

  #[Route('/wct/contact', name: 'wct_contact')]
  public function contact(Request $request, AdminRepository $userRepo): Response
    {
        $adminUser = $userRepo->getUser('inseam');
        
        return $this->render('wct/contact.html.twig', [
        ]);
    }

  #[Route('/wct/submit', name: 'wct_submit')]
  public function submit(Request $request, AdminRepository $userRepo): Response
    {
        $adminUser = $userRepo->getUser('inseam');
        
        return $this->render('wct/submit.html.twig', [
        ]);
    }

  #[Route('/wct/newsletter', name: 'wct_newsletter')]
  public function newsletter(Request $request, AdminRepository $userRepo): Response
    {
        $adminUser = $userRepo->getUser('inseam');
        
        return $this->render('wct/newsletter.html.twig', [
        ]);
    }

    #[Route('/wct/privacy', name: 'wct_privacy')]
  public function privacy(Request $request, AdminRepository $userRepo): Response
    {
        $adminUser = $userRepo->getUser('inseam');
        
        return $this->render('wct/privacy.html.twig', [
        ]);
    }

    #[Route('/wct/licensing', name: 'wct_licensing')]
  public function licensing(Request $request, AdminRepository $userRepo): Response
    {
        $adminUser = $userRepo->getUser('inseam');
        
        return $this->render('wct/licensing.html.twig', [
        ]);
    }
}
