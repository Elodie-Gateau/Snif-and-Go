<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'attr' => ['class' => 'input'],
                'label' => 'Email :',
                'label_attr' => ['class' => 'label'],
                'constraints' => [
                    new NotBlank([
                        'message' => "Veuillez saisir une adresse mail",
                    ]),
                    new Regex([
                        'pattern' => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
                        'message' => "L'adresse mail indiquée est invalide",
                    ]),
                ],
            ])
            ->add('name', null, [
                'attr' => ['class' => 'input'],
                'label' => 'Nom :',
                'label_attr' => ['class' => 'label'],
                'constraints' => [
                    new NotBlank([
                        'message' => "Veuillez saisir un nom",
                    ]),
                    new Length([
                        'min' => 2,
                        'minMessage' => 'Votre nom doit contenir au moins {{ limit }} caractères',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('firstname', null, [
                'attr' => ['class' => 'input'],
                'label' => 'Prenom :',
                'label_attr' => ['class' => 'label'],
                'constraints' => [
                    new NotBlank([
                        'message' => "Veuillez saisir un prénom",
                    ]),
                    new Length([
                        'min' => 2,
                        'minMessage' => 'Votre prénom doit contenir au moins {{ limit }} caractères',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('title', ChoiceType::class, [
                'choices' => [
                    'Sélectionner une civilité' => null,
                    'Madame' => 'Mme',
                    'Monsieur' => 'M'
                ],
                'attr' => ['class' => 'input'],
                'label' => 'Civilité :',
                'label_attr' => ['class' => 'label'],
                'constraints' => [
                    new NotBlank([
                        'message' => "Veuillez choisir une civilité",
                    ])
                ],
            ])

            // ->add('roles', ChoiceType::class, [
            //     'label'    => 'Rôles',
            //     'choices'  => [
            //         'Utilisateur'    => 'ROLE_USER',
            //         'Administrateur' => 'ROLE_ADMIN',
            //     ],
            //     'multiple' => true,
            //     'expanded' => true,
            // ])
        //     ->add('plainPassword', PasswordType::class, [

        //         'mapped' => false,
        //         'attr' => ['autocomplete' => 'new-password'],
        //         'constraints' => [
        //             new NotBlank([
        //                 'message' => "Veuillez saisir un mot de passe",
        //             ]),
        //             new Length([
        //                 'min' => 8,
        //                 'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
        //                 // max length allowed by Symfony for security reasons
        //                 'max' => 4096,
        //             ]),
        //             new Regex([
        //                 'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*()\-_=+{};:,<.>]).*$/',
        //                 'message' => 'Votre mot de passe doit contenir au moins une lettre majuscule, une lettre minuscule et un caractère spécial.',
        //             ]),
        //         ],
        //         'attr' => ['class' => 'input'],
        //         'label' => 'Mot de passe :',
        //         'label_attr' => ['class' => 'label']
        //     ])
         ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
