<?php

namespace App\Form;

use App\Entity\Disponibilite;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DisponibiliteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date',
                'attr' => [
                    'class' => 'form-control',
                ],
                'mapped' => false
            ])
            ->add('heureDebut', TimeType::class, [
                'widget' => 'single_text',
                'label' => 'Heure de début',
                'attr' => ['class' => 'form-control'],
                'mapped' => false,
                'html5' => true
            ])
            ->add('heureFin', TimeType::class, [
                'widget' => 'single_text',
                'label' => 'Heure de fin',
                'attr' => ['class' => 'form-control'],
                'mapped' => false,
                'html5' => true
            ])
            ->add('submit', SubmitType::class, [
                'label' => '<i class="fas fa-plus"></i> Créer créneaux (40min)',
                'label_html' => true,
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Disponibilite::class,
        ]);
    }
}
