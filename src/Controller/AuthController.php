<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
#[Route('/auth')]
class AuthController extends AbstractController
{
    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher, UserRepository $userRepo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if(!isset($data['email']) || !isset($data['password'])) {
            return $this->json([
                'message' => 'Email and password are required'
            ], 400);
        }
        $userExist = $userRepo->findOneBy(['email' => $data['email']]);
        if($userExist) {
            return $this->json([
                'message' => 'User already exist'
            ], 400);
        }
        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword($hasher->hashPassword($user, $data['password']));
        $user->setRoles(['ROLE_USER']);

        $em->persist($user);
        $em->flush();

        return $this->json([
            'message' => 'User registered successfully',
        ]);
    }


    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher, UserRepository $userRepo, JWTTokenManagerInterface $jwt): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if(!isset($data['email']) || !isset($data['password'])) {
            return $this->json([
                'message' => 'Email and password are required'
            ], 400);
        }


        $user = $userRepo->findOneBy(['email' => $data['email']]);
        if(!$user || !$hasher->isPasswordValid($user, $data['password'])) {
            return $this->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $jwtToken = $jwt->create($user);

        $refreshToken = bin2hex(random_bytes(64));
        $refreshTokenExpireAt = new \DateTime('+7 days');
        $user->setRefreshToken($refreshToken);
        $user->setRefreshTokenExpiresAt($refreshTokenExpireAt);
        $em->persist($user);
        $em->flush();

       $res = new JsonResponse([
            'message' => 'You are logged in',
            'token' => $jwtToken
        ], 200);

        $res->headers->setCookie(
            Cookie::create('refresh_token')
                ->withValue($refreshToken)
                ->withExpires($refreshTokenExpireAt)
                ->withSecure(true)
                ->withHttpOnly(true)
        );
        return $res;
    }
}
