<?php

namespace App\Form;

use App\Entity\Place;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\User;

class PlaceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'attr' => ['class' => 'block w-full px-4 py-2 border rounded']
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'Restaurant' => 'restaurant',
                    'Bar'        => 'bar',
                    'Salle de sport' => 'gym',
                    'Autre'      => 'other'
                ],
                'placeholder' => 'Sélectionnez un type',
                'attr' => ['class' => 'block w-full px-4 py-2 border rounded']
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'attr' => ['class' => 'block w-full px-4 py-2 border rounded']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'block w-full px-4 py-2 border rounded', 'rows' => 4]
            ])
            ->add('latitude', TextType::class, [
                'label' => false,
                'attr' => ['type' => 'hidden', 'id' => 'latitude-field']
            ])
            ->add('longitude', TextType::class, [
                'label' => false,
                'attr' => ['type' => 'hidden', 'id' => 'longitude-field']
            ])
        ;

        if ($options['is_admin']) {
            $builder
                ->add('status', ChoiceType::class, [
                    'label' => 'Statut',
                    'choices' => [
                        'En attente' => 'en_attente',
                        'Actif' => 'active',
                        'Désactivé' => 'desactive'
                    ],
                    'placeholder' => 'Sélectionnez un statut',
                    'attr' => ['class' => 'block w-full px-4 py-2 border rounded']
                ])
                ->add('createdBy', EntityType::class, [
                    'class' => User::class,
                    'choice_label' => 'username',
                    'label' => 'Créé par',
                    'placeholder' => 'Sélectionner un utilisateur',
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Place::class,
            'is_admin' => false,
        ]);
    }
}
