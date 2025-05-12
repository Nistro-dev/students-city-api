<?php

namespace App\Controller\Front;

use App\Entity\Place;
use App\Form\PlaceType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PlaceController extends AbstractController
{
    #[Route('/places', name: 'front_place_list')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $qb = $em->getRepository(Place::class)->createQueryBuilder('p')
            ->where('p.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('p.createdAt', 'DESC');

        if ($q = $request->query->get('q')) {
            $qb->andWhere('p.name LIKE :q OR p.address LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        if ($type = $request->query->get('type')) {
            $qb->andWhere('p.type = :type')
                ->setParameter('type', $type);
        }

        $places = $qb->getQuery()->getResult();

        return $this->render('front/place_list.html.twig', [
            'places' => $places,
        ]);
    }

    #[Route('/places/new', name: 'front_place_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $place = new Place();
        $form = $this->createForm(PlaceType::class, $place);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $place->setStatus('en_attente');
            $place->setCreatedAt(new \DateTimeImmutable());
            $place->setCreatedBy($this->getUser());

            $em->persist($place);
            $em->flush();

            $this->addFlash('places_success', 'L\'établissement a été soumis et est en attente de validation.');
            return $this->redirectToRoute('front_place_list');
        }

        return $this->render('front/place_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/places/{id}', name: 'front_place_show')]
    public function show(Place $place): Response
    {
        if ($place->getStatus() !== 'active') {
            throw $this->createNotFoundException('Lieu introuvable');
        }

        return $this->render('front/place_show.html.twig', [
            'place' => $place,
        ]);
    }

    #[Route('/places/{id}/edit', name: 'front_place_edit')]
    public function edit(Place $place, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted(attribute: 'ROLE_USER');

        if ($place->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que vos propres établissements.');
        }

        $form = $this->createForm(PlaceType::class, $place);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('places_success', 'Établissement mis à jour.');
            return $this->redirectToRoute('profile');
        }

        return $this->render('front/place_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/places/{id}/delete', name: 'front_place_delete', methods: ['POST'])]
    public function delete(Place $place, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($place->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez supprimer que vos propres établissements.');
        }

        $submittedToken = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete-place-' . $place->getId(), $submittedToken)) {
            $em->remove($place);
            $em->flush();

            $this->addFlash('places_success', 'Établissement supprimé.');
        } else {
            $this->addFlash('places_success', 'Échec de la suppression de l\'établissement (CSRF token invalide).');
        }

        return $this->redirectToRoute('profile');
    }

    #[Route('/map', name: 'front_map')]
    public function map(EntityManagerInterface $em): Response
    {
        $places = $em->getRepository(Place::class)->createQueryBuilder('p')
            ->where('p.status = :status')
            ->andWhere('p.latitude IS NOT NULL')
            ->andWhere('p.longitude IS NOT NULL')
            ->setParameter('status', 'active')
            ->getQuery()
            ->getResult();

        return $this->render('front/map.html.twig', [
            'places' => $places,
        ]);
    }
}
