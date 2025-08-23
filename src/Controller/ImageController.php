<?php

namespace App\Controller;

use App\Document\Image;
use App\Document\ImageMetadata;
use App\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\UploadOptions;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use MongoDB\BSON\Regex;

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
            ->hydrate(true)
            ->getQuery()
            ->execute();

        return $this->render('image/index.html.twig', [
            'images' => $images,
            'q' => "",
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
    public function upload(Request $request, DocumentManager $dm, ValidatorInterface $validator): Response
    {
        $file = $request->files->get('file');
        if (!$file) {
            $this->addFlash('error', 'No file provided.');
            return $this->redirectToRoute('home');
        }

        $title = (string) $request->request->get('title', $file->getClientOriginalName());
        $tags = array_filter(array_map('trim', explode(',', (string) $request->request->get('tags', ''))));
        $mime = $file->getMimeType() ?? 'application/octet-stream';

        $metadata = new ImageMetadata();
        $metadata->setTitle($title);
        $metadata->setTags($tags);
        $metadata->setMime($mime);
        if ($user = $this->getUser()) {
            $metadata->setOwner($user);
        }

        $image = new Image();
        $image->setMetadata($metadata);
        $image->setFilename($file->getClientOriginalName());

        $errors = $validator->validate($image);
        if (count($errors) > 0) {
            $this->addFlash('error', (string) $errors);
            return $this->redirectToRoute('home');
        }

        $repo = $dm->getRepository(Image::class);
        $uploadOptions = new UploadOptions();
        $uploadOptions->metadata = $metadata;

        $image = $repo->uploadFromFile($file->getRealPath(), $file->getClientOriginalName(), $uploadOptions);

        $this->addFlash('success', 'Image uploaded successfully.');
        return $this->redirectToRoute('home');
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $req, DocumentManager $dm): Response
    {
        $q = trim((string) $req->query->get('q', ''));

        // Get repo  + Create query
        $qb = $dm->getRepository(Image::class)
            ->createQueryBuilder()
            ->sort('uploadDate', 'desc')
            ->limit(24);

        if ($q !== '') {
            // case-insensitive match on title stored in metadata
            $qb->field('metadata.title')->equals(new Regex($q, 'i'));

            // also match tags
            $qb->addOr($qb->expr()->field('metadata.tags')->equals(new Regex($q, 'i')));
        }

        $images = $qb->getQuery()->execute();

        return $this->render('image/index.html.twig', [
            'images' => $images,
            'q' => $q,
        ]);
    }


    #[Route('/edit/{id}', name: 'edit_image', methods: ['GET'])]
    public function edit(string $id, Request $request, DocumentManager $dm, ValidatorInterface $validator): Response
    {
        $repo = $dm->getRepository(Image::class);
        $image = $repo->find($id);

        if (!$image) {
            $this->addFlash('error', 'Image not found.');
            return $this->redirectToRoute('home');
        }

        $title = (string) $request->query->get('title');
        if (empty($title)) {
            $this->addFlash('error', 'Title cannot be empty.');
            return $this->redirectToRoute('home');
        }

        // Update metadata.title
        $metadata = $image->getMetadata();
        $metadata->setTitle($title);

        // Validate
        $errors = $validator->validate($image);
        if (count($errors) > 0) {
            $this->addFlash('error', (string) $errors);
            return $this->redirectToRoute('home');
        }

        try {
            $dm->createQueryBuilder(Image::class)
                ->findAndUpdate()
                ->field('id')->equals($id)
                ->field('metadata.title')->set($title)
                ->getQuery()
                ->execute();

            $this->addFlash('success', 'Image title updated successfully.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to update title: ' . $e->getMessage());
        }

        return $this->redirectToRoute('home');
    }

    #[Route('/delete/{id}', name: 'delete_image', methods: ['GET'])]
    public function delete(string $id, DocumentManager $dm, Request $request): Response
    {
        $repo = $dm->getRepository(Image::class);
        $image = $repo->find($id);

        if (!$image) {
            $this->addFlash('error', 'Image not found.');
            return $this->redirectToRoute('home');
        }

        // Check authorization
        if ($image->getOwner() !== $this->getUser()) {
            $this->addFlash('error', 'You are not authorized to delete this image.');
            return $this->redirectToRoute('home');
        }

        try {
            // Remove the image document from images.files
            $dm->remove($image);

            // Remove associated chunks from images.chunks using raw MongoDB client
            $db = $dm->getClient()->selectDatabase($dm->getConfiguration()->getDefaultDB());
            $chunksCollection = $db->selectCollection('images.chunks');
            $chunksCollection->deleteMany(['files_id' => new \MongoDB\BSON\ObjectId($id)]);

            // Flush changes to images.files
            $dm->flush();

            $this->addFlash('success', 'Image deleted successfully.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to delete image: ' . $e->getMessage());
        }

        return $this->redirectToRoute('home');
    }
}
