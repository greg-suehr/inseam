<?php

namespace App\Controller;

use App\Entity\Admin;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('login/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/register', name: 'app_register', methods: ['GET','POST'])]
    public function register(
      Request $request,
      UserPasswordHasherInterface $passwordHasher,
      EntityManagerInterface $em,
    ): Response {
        $user = new Admin();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
          $user->setRoles([]);
          
          $hashed = $passwordHasher->hashPassword(
            $user,
            $form->get('password')->getData()
            );
          $user->setPassword($hashed);
          
          $em->persist($user);
          $em->flush();
          
          return $this->redirectToRoute('app_login');
        }
        else if ($form->isSubmitted() && !$form->isValid()) {
          # TODO: passback validation messages (e.g. password must be >=8 char)
          dd($form->getErrors(true));
        }

        return $this->render('login/register.html.twig', [
          'registrationForm' => $form->createView(),
        ]);
    }
}
