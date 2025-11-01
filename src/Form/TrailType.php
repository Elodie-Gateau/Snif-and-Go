<?php

namespace App\Form;

use App\Entity\Trail;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\File as FileConstraint;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Form\FormInterface;




class TrailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'attr' => [
                    'class' => 'add-trail form__input',
                    'placeholder' => "Ex : Le sentier des Chênes, ..."
                ],
                'label' => "Nom de l'itinéraire :",

                'label_attr' => ['class' => 'add-trail form-label'],
                'constraints' => [
                    new NotBlank([
                        'message' => "Veuillez saisir un nom",
                    ]),
                    new Length([
                        'min' => 2,
                        'minMessage' => 'Votre nom doit contenir au moins {{ limit }} caractères',
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('inputMode', ChoiceType::class, [
                'attr' => ['class' => 'add-trail form-input-select'],
                'label_attr' => ['class' => 'add-trail form-label'],
                'expanded' => true,
                'multiple' => false,
                'choices' => [
                    'Importer un fichier GPX' => 'gpx',
                    "Saisir les adresses de départ et d'arrivée" => 'manual',
                ],
                'data' => 'gpx',
                'label' => "Choisissez une méthode pour renseigner l'itinéraire :",
            ])

            ->add('gpxFile', FileType::class, [
                'attr' => ['class' => 'add-trail form__input'],
                'label_attr' => ['class' => 'add-trail form-label'],
                'mapped' => false,
                'required' => false,
                'label' => 'Importer un fichier GPX :',
                'attr' => ['accept' => '.gpx,application/gpx+xml'],
                'constraints' => [
                    new FileConstraint([
                        'groups' => ['gpx'],
                        'maxSize' => '10M',
                        'mimeTypes' => ['application/gpx+xml', 'application/xml', 'text/xml'],
                        'mimeTypesMessage' => 'Fichier GPX invalide',
                    ]),
                    new NotBlank([
                        'groups' => ['gpx'],
                        'message' => "Veuillez télécharger un fichier",
                    ]),
                ],
                'row_attr' => ['data-section' => 'gpx'],
            ])

            ->add('startAddress', null, [
                'attr' => ['class' => 'add-trail form__input'],
                'label_attr' => ['class' => 'add-trail form-label'],
                'label' => 'Adresse :',
                'constraints' => [
                    new NotBlank([
                        'groups' => ['manual'],
                        'message' => "Veuillez saisir une adresse",
                    ]),
                    new Length([
                        'groups' => ['manual'],
                        'min' => 2,
                        'minMessage' => 'Votre adresse doit contenir au moins {{ limit }} caractères',
                        'max' => 4096,
                    ]),
                ],
            ])

            ->add('startCode', null, [
                'attr' => ['class' => 'add-trail form__input'],
                'label_attr' => ['class' => 'add-trail form-label code'],
                'label' => 'Code Postal :',
                'constraints' => [
                    new NotBlank([
                        'groups' => ['manual'],
                        'message' => "Veuillez saisir un code postal",
                    ]),
                    new Length([
                        'groups' => ['manual'],
                        'min' => 5,
                        'minMessage' => 'Votre code postal doit contenir {{ limit }} chiffres',
                        'maxMessage' => 'Votre code postal doit contenir {{ limit }} chiffres',
                        'max' => 5,
                    ]),
                ],
            ])

            ->add('startCity', null, [
                'attr' => ['class' => 'add-trail form__input'],
                'label_attr' => ['class' => 'add-trail form-label'],
                'label' => 'Ville :',
                'constraints' => [
                    new NotBlank([
                        'groups' => ['manual'],
                        'message' => "Veuillez saisir une ville",
                    ]),
                    new Length([
                        'groups' => ['manual'],
                        'min' => 2,
                        'minMessage' => 'Votre nom doit contenir au moins {{ limit }} caractères',
                        'max' => 4096,
                    ]),
                ],
            ])

            ->add('endAddress', null, [
                'attr' => ['class' => 'add-trail form__input'],
                'label_attr' => ['class' => 'add-trail form-label'],
                'label' => 'Adresse :',
                'constraints' => [
                    new NotBlank([
                        'groups' => ['manual'],
                        'message' => "Veuillez saisir un nom",
                    ]),
                    new Length([
                        'groups' => ['manual'],
                        'min' => 2,
                        'minMessage' => 'Votre nom doit contenir au moins {{ limit }} caractères',
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('endCode', null, [
                'attr' => ['class' => 'add-trail form__input'],
                'label_attr' => ['class' => 'add-trail form-label'],
                'label' => 'Code Postal :',
                'constraints' => [
                    new NotBlank([
                        'groups' => ['manual'],
                        'message' => "Veuillez saisir un nom",
                    ]),
                    new Length([
                        'groups' => ['manual'],
                        'min' => 2,
                        'minMessage' => 'Votre nom doit contenir au moins {{ limit }} caractères',
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('endCity', null, [
                'attr' => ['class' => 'add-trail form__input'],
                'label_attr' => ['class' => 'add-trail form-label'],
                'label' => 'Ville :',
                'constraints' => [
                    new NotBlank([
                        'groups' => ['manual'],
                        'message' => "Veuillez saisir un nom",
                    ]),
                    new Length([
                        'groups' => ['manual'],
                        'min' => 2,
                        'minMessage' => 'Votre nom doit contenir au moins {{ limit }} caractères',
                        'max' => 4096,
                    ]),
                ],
            ])

            ->add('water_point', ChoiceType::class, [
                'attr' => ['class' => 'add-trail form__input'],
                'label_attr' => ['class' => 'add-trail form-label'],
                'multiple' => false,
                'choices' => [
                    'Oui' => 'true',
                    'Non' => 'false',
                    'Je ne sais pas' => null
                ],
                'label' => "Existe-t-il un point d'eau sur le trajet ?"
            ])

            ->add('difficulty', ChoiceType::class, [
                'attr' => ['class' => 'add-trail form__input'],
                'label_attr' => ['class' => 'add-trail form-label'],
                'expanded' => false,
                'multiple' => false,
                'choices' => [
                    'Sélectionner un niveau de difficulté' => null,
                    'Facile' => 'easy',
                    'Moyen' => 'medium',
                    'Difficile' => 'hard'
                ],
                'label' => "Quel niveau de difficulté estimez-vous pour cet itinéraire ?",
            ])

            ->add('photoFiles', FileType::class, [
                'label' => 'Téléchargez des photos (facultatif) :',
                'label_attr' => ['class' => 'add-trail form-label'],
                'mapped' => false,
                'multiple' => true,
                'required' => false,
                'attr' => ['accept' => 'image/*', 'class' => 'add-trail form__input'],
                'constraints' => [
                    new All([
                        new File([
                            'maxSize' => '5M',
                            'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
                            'mimeTypesMessage' => 'Formats acceptés : jpeg, png, webp, gif (≤ 5 Mo).',
                        ])
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Trail::class,
            'required' => false,
            'attr' => ['class' => 'form'],
            'validation_groups' => function (FormInterface $form) {
                $data = $form->getData();
                $mode = $data?->getInputMode();
                if (!$mode) {
                    $mode = $form->has('inputMode') ? ($form->get('inputMode')->getData() ?? 'gpx') : 'gpx';
                }
                return ['Default', $mode];
            },

        ]);
    }
}
