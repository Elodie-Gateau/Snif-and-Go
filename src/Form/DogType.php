<?php

namespace App\Form;

use App\Entity\Dog;
use App\Entity\DogBreed;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class DogType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'attr' => ['class' => 'add-dog__form-input'],
                'label' => 'Nom du chien :',
                'label_attr' => ['class' => 'add-dog__form-label'],
                'constraints' => [
                    new NotBlank([
                        'message' => "Veuillez saisir un nom",
                    ]),
                    new Length([
                        'min' => 2,
                        'minMessage' => 'Votre nom doit contenir au moins {{ limit }} caractères',
                        'max' => 50,
                    ]),
                ],
            ])
            ->add('birth_date', DateType::class, [
                'widget' => 'choice',
                'years' => range(date('Y') - 30, date('Y')),
                'attr' => ['class' => 'add-dog__form__input'],
                'label' => 'Date de naissance :',
                'label_attr' => ['class' => 'add-dog__form-label'],
            ])

            ->add('sex', ChoiceType::class, [
                'choices' => [
                    'Choisir le genre' => null,
                    'Femelle' => 'female',
                    'Mâle' => 'male'
                ],
                'attr' => ['class' => 'add-dog__form-input'],
                'label' => 'Genre :',
                'label_attr' => ['class' => 'add-dog__form-label'],
                'constraints' => [
                    new NotBlank([
                        'message' => "Veuillez choisir un genre",
                    ])
                ],
            ])
            // ->add('dogBreed', DogBreedAutocompleteField::class)

            ->add('dogBreed', EntityType::class, [
                'class' => DogBreed::class,
                'choice_label' => 'name_fr',
                'placeholder' => 'Saisir une race',
                'autocomplete' => true,
                'label' => 'Race du chien :',
                'label_attr' => ['class' => 'add-dog__form-label'],
            ])

            ->add(
                'identity_number',
                null,
                [
                    'attr' => ['class' => 'add-dog__form-input'],
                    'label' => "Numéro d'identification du chien :",
                    'label_attr' => ['class' => 'add-dog__form-label'],
                ],
            )

            ->add('photo', FileType::class, [
                'label' => 'Photo de profil',
                'label_attr' => ['class' => 'add-dog__form-label'],
                'attr' => ['class' => 'add-dog__form-input'],
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5000k',
                        'mimeTypes' => [
                            'image/*',
                        ],
                        'mimeTypesMessage' => 'Image trop lourde',
                    ])
                ],
            ]);;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Dog::class,
            'required' => false
        ]);
    }
}
