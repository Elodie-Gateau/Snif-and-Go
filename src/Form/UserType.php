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

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class)
            ->add('name', TextType::class)
            ->add('firstname', TextType::class)
            ->add('title', ChoiceType::class, [
                'choices' => [
                    'Monsieur' => 'M',
                    'Madame'   => 'Mme',
                ],
                'placeholder' => 'Choisir…',
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
            ->add('password', PasswordType::class, [
                'mapped'   => false,
                'required' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'label' => 'Nouveau mot de passe',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
