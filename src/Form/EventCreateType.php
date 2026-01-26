<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class EventCreateType extends AbstractType
{
    /**
     * Baut das Event-Erstellungsformular auf.
     *
     * @param FormBuilderInterface $builder Formular-Builder
     * @param array $options Formularoptionen
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Event-Name',
                'constraints' => [
                    new Assert\NotBlank(message: 'Event-Name ist erforderlich'),
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('slug', TextType::class, [
                'label' => 'URL-Slug',
                'constraints' => [
                    new Assert\NotBlank(message: 'Slug ist erforderlich'),
                    new Assert\Regex(pattern: '/^[a-z0-9\-]+$/', message: 'Nur Kleinbuchstaben, Zahlen und Bindestriche sind erlaubt'),
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Beschreibung',
                'required' => false,
                'constraints' => [new Assert\Length(max: 2000)],
            ])
            ->add('eventDate', DateType::class, [
                'label' => 'Datum (optional)',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('location', TextType::class, [
                'label' => 'Ort (optional)',
                'required' => false,
                'constraints' => [new Assert\Length(max: 255)],
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
