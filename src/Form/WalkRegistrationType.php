<?php

namespace App\Form;

use App\Entity\Dog;
use App\Entity\Walk;
use App\Entity\WalkRegistration;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class WalkRegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('dog', EntityType::class, [
                'label' => 'Je souhaite inscrire',
                'label_attr' => ['class' => 'walk-register__desc-title'],
                'attr' => ['class' => 'walk-register__desc-select'],
                'class' => Dog::class,
                'choices' => $options['dogs'],
                'choice_label' => 'name',
                'placeholder' => 'Choisissez votre chien',
                'invalid_message' => 'Sélection invalide.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WalkRegistration::class,
            'dogs' => [],
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'walk_registration_form',
        ]);
    }
}
