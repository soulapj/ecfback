<?php

namespace App\Controller;

use App\Entity\Categories;
use App\Repository\CategoriesRepository;
use App\Repository\MoviesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
#[Route('/categories')]
class CategoriesController extends AbstractController
{
    private $moviesRepo;
    private $serializer;
    private $entityManager;
    private $categRepo;
    public function __construct(MoviesRepository $moviesRepo, SerializerInterface $serializerInterface, EntityManagerInterface $entityManager, CategoriesRepository $categoriesRepository){
        $this->categRepo = $categoriesRepository;
        $this->entityManager = $entityManager;
        $this->moviesRepo = $moviesRepo;
        $this->serializer = $serializerInterface;
    }
    #[Route('/', name: 'get_categories', methods: ['GET'])]
    public function getAllCategories(): JsonResponse
    {
        $categories = $this->categRepo->findAll();
        $data = $this->serializer->normalize($categories,'json');
        return $this->json(
            $data
        );
    }

    #[Route('/{id}', name: 'get_category', methods: ['GET'])]
    public function getCategory(int $id): JsonResponse
    {
        $categories = $this->categRepo->find($id);
        $data = $this->serializer->normalize($categories,'json');
        return $this->json(
            $data
        );
    }
    #[Route('/add', name: 'add_category', methods: ['PUT'])]
    public function addCategory(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if(!isset($data['titre'])) {
            return new JsonResponse(['error' => 'Il manque des trucs :('], 400);
        } else {
            $category = new Categories();
            $category->setTitre($data['titre']);

            $this->entityManager->persist($category);
            $this->entityManager->flush();     
            return new JsonResponse('Bien joué, j\'aurais pas fait comme c cédille a');      
        }
        
    }
    #[Route('/{id}', name: 'update_category', methods: ['PUT'])]
    public function updateCategory(Request $request, int $id): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if(!isset($data['titre'])) {
            return new JsonResponse(['error' => 'Il manque des trucs :('], 400);
        } else {
            $category = $this->categRepo->find($id);
            $category->setTitre($data['titre']);
            $this->entityManager->persist($category);
            $this->entityManager->flush();     
            return new JsonResponse('Bien joué, mais ça aurait pu être fait plus vite et mieux');      
        }
    }
    #[Route('/{id}', name: 'delete_category', methods: ['DELETE'])]
    public function deleteCategory(int $id): JsonResponse
    {
        $category = $this->categRepo->find($id);
        if (!$category){
            return $this->json(['message'=>'Ce code appartient a Joël Simoes'], 404);
        }
        $this->entityManager->remove($category);
        $this->entityManager->flush();
        return $this->json(['message'=>'bruit de chasse d\'eau'], 200);
    }
}
