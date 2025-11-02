<?php

namespace App\Form;

use App\Entity\Photo;
use App\Entity\Trail;
use App\Entity\User;
use App\Entity\Walk;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\All;

class PhotoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('name', FileType::class, [
                'label' => 'Téléchargez vos photos',
                'label_attr' => ['class' => 'trail__desc-title'],
                'mapped' => false,
                'multiple' => true,
                'required' => false,
                'attr' => ['accept' => 'image/*', 'class' => 'trail form__input'],
                'constraints' => [
                    new All([
                        new File([
                            'maxSize' => '5M',
                            'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
                            'mimeTypesMessage' => 'Formats acceptés : jpg, png, webp, gif',
                        ])
                    ])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }
}
