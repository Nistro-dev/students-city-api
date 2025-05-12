<?php

namespace App\Controller\Admin;

use App\Entity\Place;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Form\PlaceType;
use Knp\Component\Pager\PaginatorInterface;
use App\Service\GeocodingService;

class PlaceController extends AbstractController
{

    #[Route('/admin/places', name: 'admin_place_list')]
    public function list(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator): Response
    {
        $qb = $em->getRepository(Place::class)->createQueryBuilder('p')->orderBy('p.createdAt', 'DESC');

        if ($search = $request->query->get('search')) {
            $qb->andWhere('p.name LIKE :s OR p.address LIKE :s')
                ->setParameter('s', '%' . $search . '%');
        }

        if ($status = $request->query->get('status')) {
            $qb->andWhere('p.status = :status')->setParameter('status', $status);
        }

        $pagination = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/place_list.html.twig', [
            'pagination' => $pagination,
            'places' => $pagination->getItems(),
        ]);
    }

    #[Route('/admin/places/new', name: 'admin_place_new')]
    public function new(Request $request, EntityManagerInterface $em, GeocodingService $geocodingService): Response
    {
        $place = new Place();
        $form = $this->createForm(PlaceType::class, $place, ['is_admin' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $place->setCreatedAt(new \DateTimeImmutable());

            $coords = $geocodingService->geocode($place->getAddress());
            if ($coords) {
                $place->setLatitude($coords['lat']);
                $place->setLongitude($coords['lon']);
            }

            $em->persist($place);
            $em->flush();

            $this->addFlash('success', 'Établissement ajouté.');
            return $this->redirectToRoute('admin_place_list');
        }

        return $this->render('admin/place_form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Nouvel établissement',
        ]);
    }

    #[Route('/admin/places/{id}/edit', name: 'admin_place_edit')]
    public function edit(Place $place, Request $request, EntityManagerInterface $em, GeocodingService $geocodingService): Response
    {
        $form = $this->createForm(PlaceType::class, $place, ['is_admin' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $coords = $geocodingService->geocode($place->getAddress());
            if ($coords) {
                $place->setLatitude($coords['lat']);
                $place->setLongitude($coords['lon']);
            }

            $em->flush();
            $this->addFlash('success', 'Établissement modifié.');
            return $this->redirectToRoute('admin_place_list');
        }

        return $this->render('admin/place_form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Modifier « ' . $place->getName() . ' »',
        ]);
    }
    #[Route('/admin/places/{id}/approve', name: 'admin_place_approve', methods: ['POST'])]
    public function approve(Place $place, EntityManagerInterface $em): Response
    {
        $place->setStatus('active');
        $em->flush();

        $this->addFlash('success', "Établissement approuvé.");
        return $this->redirectToRoute('admin_place_list');
    }

    #[Route('/admin/places/{id}/reject', name: 'admin_place_reject', methods: ['POST'])]
    public function reject(Place $place, EntityManagerInterface $em): Response
    {
        $em->remove($place);
        $em->flush();

        $this->addFlash('success', "Établissement supprimé.");
        return $this->redirectToRoute('admin_place_list');
    }

    #[Route('/admin/places/{id}/revoke', name: 'admin_place_revoke', methods: ['POST'])]
    public function revoke(Place $place, EntityManagerInterface $em, Request $request): Response
    {
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('revoke-place-' . $place->getId(), $submittedToken)) {
            throw $this->createAccessDeniedException('Token CSRF invalide');
        }

        $place->setStatus('en_attente');
        $em->flush();

        $this->addFlash('success', "Établissement remis en attente.");
        return $this->redirectToRoute('admin_place_list');
    }
}
