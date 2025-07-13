<?php
declare(strict_types=1);

namespace App\Controller;

use App\Security\SecurityUser;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $currentUser = $this->getUser();

        if ($currentUser instanceof SecurityUser) {
            return $this->redirectToRoute('user_show', ['id' => $currentUser->getUser()->getId()]);
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('user/login_form.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    public function logout(): void
    {
        throw new LogicException('The method is implemented by Symfony');
    }
}
