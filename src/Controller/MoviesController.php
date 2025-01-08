<?php

namespace App\Controller;

use App\Entity\Movies;
use App\Repository\CategoriesRepository;
use App\Repository\MoviesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
#[Route('/movies')]
class MoviesController extends AbstractController
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
    #[Route('/', name: 'get_movies', methods: ['GET'])]
    public function getAllMovies(): JsonResponse
    {
        $movies = $this->moviesRepo->findAll();
        $data = $this->serializer->normalize($movies,'json');
        return $this->json(
            $data
        );
    }

    #[Route('/{id}', name: 'get_movie', methods: ['GET'])]
    public function getMovie(int $id): JsonResponse
    {
        $movies = $this->moviesRepo->find($id);
        $data = $this->serializer->normalize($movies,'json');
        return $this->json(
            $data
        );
    }
    #[Route('/add', name: 'add_movie', methods: ['PUT'])]
    public function addMovie(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if(!isset($data['titre']) || !isset($data['dateParution']) || !isset($data['description']) || !isset($data['realisateur'])) {
            return new JsonResponse(['error' => 'Il manque des trucs :('], 400);
        } else {
            $movie = new Movies();
            $movie->setTitre($data['titre']);
            $movie->setDescription($data['description']);
            $movie->setRealisateur($data['realisateur']);
            $movie->setDateParution(new \DateTime($data['dateParution']));

            $this->entityManager->persist($movie);
            $categ = $data['categories'];
            foreach($categ as $category){
                $cat = $this->categRepo->find($category);
                $cat->addMovie($movie);
                $this->entityManager->persist($cat);
            }
            $this->entityManager->flush();     
            return new JsonResponse('Bien joué, j\'aurais pas fait comme c cédille a');      
        }
        
    }
    #[Route('/{id}', name: 'update_movie', methods: ['PUT'])]
    public function updateMovie(Request $request, int $id): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if(!isset($data['titre']) || !isset($data['dateParution']) || !isset($data['description']) || !isset($data['realisateur'])) {
            return new JsonResponse(['error' => 'Il manque des trucs :('], 400);
        } else {
            $movie = $this->moviesRepo->find($id);
            $movie->setTitre($data['titre']);
            $movie->setDescription($data['description']);
            $movie->setRealisateur($data['realisateur']);
            $movie->setDateParution(new \DateTime($data['dateParution']));

            $this->entityManager->persist($movie);
            $categ = $data['categories'];
            $currentCategories = $movie->getCategories();
            foreach($categ as $category){
                $cat = $this->categRepo->find(id: $category);
                if(!$currentCategories->contains($cat)){
                    $movie->addCategory($cat);
                }
            }
            foreach($currentCategories as $currentCategory){
                if(!$categ || !in_array($currentCategory->getId(), $categ)){
                    $movie->removeCategory($currentCategory);
                }
            }
            $this->entityManager->flush();     
            return new JsonResponse('Bien joué, mais ça aurait pu être fait plus vite et mieux');      
        }
    }
    #[Route('/{id}', name: 'delete_movie', methods: ['DELETE'])]
    public function deleteMovie(int $id): JsonResponse
    {
        $movie = $this->moviesRepo->find($id);
        if (!$movie){
            return $this->json(['message'=>'Ce code appartient a Joël Simoes'], 404);
        }
        foreach ($movie->getCategories() as $category){
            $category->removeMovie($movie);
            $this->entityManager->persist($category);
        }
        $this->entityManager->remove($movie);
        $this->entityManager->flush();
        return $this->json(['message'=>'bruit de chasse d\'eau'], 200);
    }
}
