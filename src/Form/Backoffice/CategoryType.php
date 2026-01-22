<?php

declare(strict_types=1);

namespace App\Form\Backoffice;

use App\Domain\Participation\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
                'attr' => [
                    'placeholder' => 'z.B. Technologie, Marketing, Business',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Bitte geben Sie einen Namen ein.']),
                    new Assert\Length([
                        'max' => 100,
                        'maxMessage' => 'Der Name darf nicht länger als {{ limit }} Zeichen sein.'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Beschreibung',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Kurze Beschreibung der Kategorie (optional)',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 500,
                        'maxMessage' => 'Die Beschreibung darf nicht länger als {{ limit }} Zeichen sein.'
                    ])
                ]
            ])
            ->add('color', ColorType::class, [
                'label' => 'Farbe',
                'attr' => [
                    'class' => 'form-control form-control-color'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Bitte wählen Sie eine Farbe.']),
                    new Assert\Regex([
                        'pattern' => '/^#[a-fA-F0-9]{6}$/',
                        'message' => 'Die Farbe muss im Format #rrggbb eingegeben werden.'
                    ])
                ]
            ])
            ->add('sortOrder', IntegerType::class, [
                'label' => 'Reihenfolge',
                'attr' => [
                    'min' => 0,
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Bitte geben Sie eine Reihenfolge an.']),
                    new Assert\Range([
                        'min' => 0,
                        'max' => 999,
                        'notInRangeMessage' => 'Die Reihenfolge muss zwischen {{ min }} und {{ max }} liegen.'
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}