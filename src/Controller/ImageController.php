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

    #[Route('/file/{id}', name: 'file_raw', requirements: ['id' => '[a-f0-9]{24}'], methods: ['GET'])]
    public function fileById(string $id, DocumentManager $dm): Response
    {
        // Get repo
        $repo = $dm->getRepository(Image::class);

        // Find Document by id
        $doc = $repo->find($id);
        if (!$doc) {
            throw $this->createNotFoundException();
        }

        $mime = $doc->getMime() ?: 'application/octet-stream';

        // Return the Image
        return new StreamedResponse(function () use ($repo, $id) {
            $repo->downloadToStream($id, fopen('php://output', 'wb'));
        }, 200, ['Content-Type' => $mime]);
    }

    #[Route('/upload', name: 'upload', methods: ['POST'])]
    public function upload(Request $req, DocumentManager $dm): Response
    {
        // Get the uploaded file
        $file = $req->files->get('file');
        if (!$file) {
            $this->addFlash('error', 'No file provided.');
            return $this->redirectToRoute('home');
        }

        // Get title, tags and mimetype
        $title = (string)$req->request->get('title', $file->getClientOriginalName());
        $tags = array_filter(
            array_map(
                'trim', 
                explode(',', 
                (string)$req->request->get('tags', '')
                )
            )
        );
        $mime = $file->getMimeType() ?? 'application/octet-stream';

        // Get Repo
        $repo = $dm->getRepository(Image::class);
        // Write the content of the file to GridFS
        $doc  = $repo->uploadFromFile($file->getRealPath(), $file->getClientOriginalName());

        $doc->setTitle($title);
        $doc->setTags($tags);
        $doc->setMime($mime);

        $user = $this->getUser();
        if ($user instanceof User) {
            $doc->setOwner($user);
        }

        $dm->flush();

        $this->addFlash('success', 'Image uploaded.');
        return $this->redirectToRoute('home');
    }
}
