<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Review;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('companyName', TextType::class, [
                'label' => 'Cég neve',
                'attr' => [
                    'autocomplete' => 'organization',
                    'maxlength' => 255,
                ],
            ])
            ->add('rating', ChoiceType::class, [
                'label' => 'Értékelés',
                'placeholder' => 'Válassz értékelést',
                'choices' => [
                    '5 – Kiváló' => 5,
                    '4 – Jó' => 4,
                    '3 – Átlagos' => 3,
                    '2 – Gyenge' => 2,
                    '1 – Nagyon gyenge' => 1,
                ],
            ])
            ->add('reviewText', TextareaType::class, [
                'label' => 'Vélemény',
                'attr' => [
                    'rows' => 7,
                ],
            ])
            ->add('authorEmail', EmailType::class, [
                'label' => 'E-mail-cím',
                'attr' => [
                    'autocomplete' => 'email',
                    'maxlength' => 255,
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Vélemény elküldése',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Review::class,
        ]);
    }
}
