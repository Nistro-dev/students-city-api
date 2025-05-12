<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class AuthController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data) || !isset($data['username'], $data['email'], $data['password'])) {
            return new JsonResponse(['message' => 'Données manquantes ou invalides'], 400);
        }

        $existing = $em->getRepository(User::class)
               ->findOneBy(['email' => $data['email']]);
        if ($existing) {
            return new JsonResponse(
                ['message' => 'Un compte avec cette adresse e-mail existe déjà.'],
                409
            );
        }

        $existingUsername = $em->getRepository(User::class)
               ->findOneBy(['username' => $data['username']]);
        if ($existingUsername) {
            return new JsonResponse(
                ['message' => 'Un compte avec ce nom d\'utilisateur existe déjà.'],
                409
            );
        }

        $user = new User();
        $user->setUsername($data['username']);
        $user->setEmail($data['email']);
        $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        $user->setRoles(['ROLE_USER']);
        $user->setStatus('en_attente');
        $user->setCreatedAt(new \DateTimeImmutable());

        $em->persist($user);
        $em->flush();

        return new JsonResponse(['message' => 'Compte créé. En attente de validation.'], 201);
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data) || !isset($data['email'], $data['password'])) {
            return new JsonResponse(['message' => 'Invalid JSON or missing credentials'], 400);
        }

        $user = $em->getRepository(User::class)
                   ->findOneBy(['email' => $data['email']]);

        if (!$user || !$hasher->isPasswordValid($user, $data['password'])) {
            return new JsonResponse(['message' => 'Email ou mot de passe invalide'], 401);
        }

        if ($user->getStatus() !== 'active') {
            return new JsonResponse(['message' => 'Compte non activé'], 403);
        }

        $token = $jwtManager->create($user);

        return new JsonResponse(['token' => $token], 200);
    }
}