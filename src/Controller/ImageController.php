<?php

namespace App\Controller;

use App\Document\Image;
use App\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ImageController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(DocumentManager $dm): Response
    {
        // Get repository
        $repo = $dm->getRepository(Image::class);

        // Get 12 Images by uploadedDate
        $images = $repo->createQueryBuilder()
            ->sort('uploadDate', 'desc')
            ->limit(12)
            ->getQuery()->execute();

        return $this->render('image/index.html.twig', [
            'images' => $images, 
        ]);
    }

    
}
