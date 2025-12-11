<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Speciality;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Type de compte
            ->add('role', ChoiceType::class, [
                'label' => 'Type de compte',
                'choices' => [
                    'Patient' => 'patient',
                    'Médecin' => 'medecin',
                ],
                'expanded' => true,
                'multiple' => false,
                'attr' => [
                    'class' => 'role-choices'
                ]
            ])

            // Diplôme (conditionnel pour médecin)
            ->add('diplome', FileType::class, [
                'label' => 'Diplôme médical *',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'accept' => '.pdf,.jpg,.jpeg,.png'
                ],
                'help' => 'Carte professionnelle, diplôme ou certificat médical'
            ])

            // Spécialité (conditionnel pour médecin)
            ->add('specialite', EntityType::class, [
                'class' => Speciality::class,
                'choice_label' => 'nom',
                'label' => 'Spécialité médicale *',
                'required' => false,
                'mapped' => false,
                'placeholder' => 'Sélectionnez votre spécialité',
            ])

            // Genre
            ->add('genre', ChoiceType::class, [
                'label' => 'Genre',
                'choices' => [
                    'Homme' => 'homme',
                    'Femme' => 'femme',
                ],
                'expanded' => true,
                'multiple' => false,
            ])

            // Nom et Prénom
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => ['placeholder' => 'Saisir votre nom ici']
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['placeholder' => 'Saisir votre prénom ici']
            ])

            // Téléphone et Date de naissance
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'attr' => ['placeholder' => 'Saisir votre téléphone ici']
            ])
            ->add('date_naissance', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
                'attr' => ['placeholder' => 'jj/mm/aaaa']
            ])

            // Email
            ->add('email', EmailType::class, [
                'label' => 'Adresse Email',
                'attr' => ['placeholder' => 'Saisir votre email ici']
            ])

            // Mot de passe
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe',
                'attr' => ['placeholder' => 'Saisir votre mdp ici'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un mot de passe',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('confirm_password', PasswordType::class, [
                'label' => 'Confirmation',
                'attr' => ['placeholder' => 'Saisir votre mdp ici'],
                'mapped' => false,
            ])
        ;
    }
}