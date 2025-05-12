<?php
namespace App\Controller\Api;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController
{
    #[Route('/api/users', name: 'api_user_list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function list(UserRepository $userRepo): JsonResponse
    {
        $users = $userRepo->findAll();
        $data = [];

        foreach ($users as $user) {
            $data[] = [
                'id'        => $user->getId(),
                'username'  => $user->getUsername(),
                'email'     => $user->getEmail(),
                'roles'     => $user->getRoles(),
                'status'    => $user->getStatus(),
                'createdAt' => $user->getCreatedAt()->format(\DateTime::ATOM),
            ];
        }

        return new JsonResponse($data, 200);
    }
}