<?php

namespace App\Form;

use App\Entity\Trail;
use App\Entity\Walk;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Validator\Constraints\NotBlank;


class WalkType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateTimeType::class, [
                'label' => 'Date et heure de la balade :',
                'attr' => [
                    'class' => 'add-walk form__input',
                    'placeholder' => 'Choisissez une date'
                ],
                'label_attr' => ['class' => 'add-walk form__label'],
                'widget' => 'single_text',
                'html5' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => "Veuillez renseigner une date valide",
                    ]),

                ],
            ])
            ->add('max_dogs', ChoiceType::class, [
                'choices' => array_combine(range(2, 10), range(2, 10)),
                'data' => 5,
                'attr' => [
                    'class' => 'add-walk form__input',
                ],
                'label' => 'Nombre maximum de chiens autorisés pendant la balade :',
                'label_attr' => ['class' => 'add-walk form__label'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez renseigner un nombre de chiens',
                    ]),
                    new Range([
                        'min' => 2,
                        'max' => 10,
                        'notInRangeMessage' => 'Le nombre de chiens doit être compris entre {{ min }} et {{ max }}.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Walk::class,
            'attr' => ['class' => 'form']
        ]);
    }
}
