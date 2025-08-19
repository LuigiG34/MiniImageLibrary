<?php
namespace App\Controller;

use App\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class AuthController extends AbstractController
{
    #[Route('/login', name: 'login', methods: ['GET','POST'])]
    public function login(AuthenticationUtils $auth): Response
    {
        return $this->render('security/login.html.twig', [
            'last_username' => $auth->getLastUsername(),
            'error'         => $auth->getLastAuthenticationError(),
        ]);
    }

    #[Route('/logout', name: 'logout')]
    public function logout(): void {}

    #[Route('/register', name: 'register', methods: ['GET','POST'])]
    public function register(
        Request $req,
        DocumentManager $dm,
        UserPasswordHasherInterface $hasher
    ): Response {
        if ($req->isMethod('POST')) {
            $email = (string) $req->request->get('email');
            $pass  = (string) $req->request->get('password');

            $exists = $dm->getRepository(User::class)->findOneBy(['email' => strtolower($email)]);
            if ($exists) {
                $this->addFlash('error', 'Email already registered.');
            } else {
                $user = new User();
                $user->setEmail($email);
                $user->setPassword($hasher->hashPassword($user, $pass));
                $dm->persist($user);
                $dm->flush();

                $this->addFlash('success', 'Account created. Please log in.');
                return $this->redirectToRoute('login');
            }
        }
        return $this->render('security/register.html.twig');
    }
}