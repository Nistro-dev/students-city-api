<?php

namespace App\Controller\Admin;

use App\Entity\Review;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;

class ReviewController extends AbstractController
{
    #[Route('/admin/reviews', name: 'admin_review_list')]
    public function list(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator): Response
    {
        $qb = $em->getRepository(Review::class)
            ->createQueryBuilder('r')
            ->leftJoin('r.user', 'u')
            ->leftJoin('r.place', 'p')
            ->addSelect('u', 'p')
            ->orderBy('r.createdAt', 'DESC');

        if ($username = $request->query->get('username')) {
            $qb->andWhere('u.username LIKE :username')
                ->setParameter('username', "%$username%");
        }

        if ($place = $request->query->get('place')) {
            $qb->andWhere('p.name LIKE :place')
                ->setParameter('place', "%$place%");
        }

        $pagination = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/review_list.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/admin/reviews/{id}/delete', name: 'admin_review_delete', methods: ['POST'])]
    public function delete(Review $review, Request $request, EntityManagerInterface $em, CsrfTokenManagerInterface $csrfManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $submittedToken = $request->request->get('_token');
        if ($csrfManager->isTokenValid(new CsrfToken('delete-review-admin-' . $review->getId(), $submittedToken))) {
            $em->remove($review);
            $em->flush();
            $this->addFlash('success', 'Avis supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_review_list');
    }
}
