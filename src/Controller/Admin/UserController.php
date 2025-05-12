<?php
namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    #[Route('/admin/users', name: 'admin_user_list')]
    public function list(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator): Response
    {
        $qb = $em->getRepository(User::class)
                  ->createQueryBuilder('u')
                  ->orderBy('u.createdAt', 'DESC');

        if ($search = $request->query->get('search')) {
            $qb->andWhere('u.username LIKE :s OR u.email LIKE :s')
               ->setParameter('s', '%'.$search.'%');
        }
        if (null !== ($status = $request->query->get('status')) && $status !== '') {
            $qb->andWhere('u.status = :st')
               ->setParameter('st', $status);
        }

        $pagination = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/user_list.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/admin/users/new', name: 'admin_user_new')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['is_edit' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plain = $form->get('plainPassword')->getData();
            $user->setPassword($hasher->hashPassword($user, $plain));
            $user->setCreatedAt(new \DateTimeImmutable());
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Utilisateur créé.');
            return $this->redirectToRoute('admin_user_list');
        }

        return $this->render('admin/user_form.html.twig', [
            'form'  => $form->createView(),
            'title' => 'Créer un utilisateur',
        ]);
    }

    #[Route('/admin/users/{id}/edit', name: 'admin_user_edit')]
    public function edit(
        User $user,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        $form = $this->createForm(UserType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($plain = $form->get('plainPassword')->getData()) {
                $user->setPassword($hasher->hashPassword($user, $plain));
            }
            $em->flush();

            $this->addFlash('success', 'Utilisateur mis à jour.');
            return $this->redirectToRoute('admin_user_list');
        }

        return $this->render('admin/user_form.html.twig', [
            'form'  => $form->createView(),
            'title' => 'Modifier l’utilisateur « '.$user->getUsername().' »',
        ]);
    }

    #[Route('/admin/users/{id}/approve', name: 'admin_user_approve', methods: ['POST'])]
    public function approve(User $user, EntityManagerInterface $em): RedirectResponse
    {
        $user->setStatus('active');
        $em->flush();

        $this->addFlash('success', "Utilisateur {$user->getEmail()} approuvé.");

        return $this->redirectToRoute('admin_user_list');
    }

    #[Route('/admin/users/{id}/reject', name: 'admin_user_reject', methods: ['POST'])]
    public function reject(User $user, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($user);
        $em->flush();

        $this->addFlash('success', "Utilisateur {$user->getEmail()} supprimé.");

        return $this->redirectToRoute('admin_user_list');
    }


    #[Route('/admin/users/{id}/revoke', name: 'admin_user_revoke', methods: ['POST'])]
    public function revoke(User $user, EntityManagerInterface $em, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('revoke-user', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $user->setStatus('en_attente');
        $em->flush();

        $this->addFlash('success', "Utilisateur « {$user->getEmail()} » remis en attente.");

        return $this->redirectToRoute('admin_user_list');
    }
}
