<?php

namespace App\Controller\Front;

use App\Entity\Place;
use App\Entity\Review;
use App\Form\ReviewType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReviewController extends AbstractController
{
    #[Route('/places/{id}/review', name: 'front_place_review')]
    public function review(Place $place, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $review = new Review();
        $review->setPlace($place);
        $review->setUser($this->getUser());
        $review->setCreatedAt(new \DateTimeImmutable());

        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($review);
            $em->flush();

            $this->addFlash('places_success', 'Votre avis a bien été enregistré.');
            return $this->redirectToRoute('front_place_show', ['id' => $place->getId()]);
        }

        return $this->render('front/review_form.html.twig', [
            'form' => $form->createView(),
            'place' => $place,
        ]);
    }

    #[Route('/reviews/{id}/edit', name: 'front_review_edit')]
    public function edit(Review $review, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($review->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que vos propres avis.');
        }

        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('places_success', 'Avis mis à jour.');
            return $this->redirectToRoute('front_place_show', ['id' => $review->getPlace()->getId()]);
        }

        return $this->render('front/review_form.html.twig', [
            'form' => $form->createView(),
            'place' => $review->getPlace(),
        ]);
    }

    #[Route('/reviews/{id}/delete', name: 'front_review_delete', methods: ['POST'])]
    public function delete(Review $review, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($review->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez supprimer que vos propres avis.');
        }

        $submittedToken = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete-review-' . $review->getId(), $submittedToken)) {
            $em->remove($review);
            $em->flush();

            $this->addFlash('places_success', 'Avis supprimé.');
        } else {
            $this->addFlash('places_success', 'Échec de la suppression de l\'avis (CSRF token invalide).');
        }

        return $this->redirectToRoute('front_place_show', ['id' => $review->getPlace()->getId()]);
    }
}
