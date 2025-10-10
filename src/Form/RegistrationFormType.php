<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            // CHAMP : CIVILITE

            ->add('title', ChoiceType::class, [
                'choices' => [
                    'Sélectionner une civilité' => null,
                    'Madame' => 'Mme',
                    'Monsieur' => 'M'
                ],
                'attr' => ['class' => 'register form__input'],
                'label' => 'Civilité :',
                'label_attr' => ['class' => 'register form__label'],
                'constraints' => [
                    new NotBlank([
                        'message' => "Veuillez choisir une civilité",
                    ])
                ],
            ])
            // CHAMP : NOM

            ->add('name', null, [
                'attr' => ['class' => 'register form__input'],
                'label' => 'Nom :',
                'label_attr' => ['class' => 'register form__label'],
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


            // CHAMP : PRÉNOM

            ->add('firstname', null, [
                'attr' => ['class' => 'register form__input'],
                'label' => 'Prenom :',
                'label_attr' => ['class' => 'register form__label'],
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


            // CHAMP : ADRESSE MAIL

            ->add('email', EmailType::class, [
                'attr' => ['class' => 'register form__input'],
                'label' => 'Email :',
                'label_attr' => ['class' => 'register form__label'],
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



            // CHAMP : MOT DE PASSE

            ->add('plainPassword', PasswordType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => "Veuillez saisir un mot de passe",
                    ]),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*()\-_=+{};:,<.>]).*$/',
                        'message' => 'Votre mot de passe doit contenir au moins une lettre majuscule, une lettre minuscule et un caractère spécial.',
                    ]),
                ],
                'attr' => ['class' => 'register form__input'],
                'label' => 'Mot de passe :',
                'label_attr' => ['class' => 'register form__label']
            ])

            // CHAMP : ACCEPTER LES CONDITIONS D'UTILISATION

            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter les conditions pour vous inscrire',
                    ]),
                ],
                'label' => "J'accepte les conditions d'utilisation et la politique de confidentialité : ",
                'label_attr' => ['class' => 'register form__label'],
                'attr' => ['class' => 'register form__checkbox'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'required' => false
        ]);
    }
}
