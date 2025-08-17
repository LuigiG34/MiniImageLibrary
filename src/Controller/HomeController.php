<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController
{
    #[Route('/', name: 'home')]
    public function __invoke(): Response
    {
        return new Response('<h1>It works 🎉</h1>');
    }
}