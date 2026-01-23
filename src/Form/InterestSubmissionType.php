<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Formular für die Abgabe einer Interessenbekundung zu einem Beitrag.
 *
 * Enthält Name, E-Mail, optionale Nachricht und Datenschutzakzeptanz.
 */
class InterestSubmissionType extends AbstractType
{
    /**
     * Baut das Formular zur Interessenbekundung auf.
     *
     * @param FormBuilderInterface $builder Formular-Builder
     * @param array $options Formularoptionen
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Ihr Name',
                'attr' => [
                    'placeholder' => 'Max Mustermann',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Ihr Name darf nicht leer sein.'),
                    new Assert\Length(
                        min: 2,
                        max: 100,
                        minMessage: 'Der Name muss mindestens {{ limit }} Zeichen lang sein.',
                        maxMessage: 'Der Name darf maximal {{ limit }} Zeichen lang sein.'
                    )
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-Mail-Adresse',
                'attr' => [
                    'placeholder' => 'max@beispiel.de',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Die E-Mail-Adresse darf nicht leer sein.'),
                    new Assert\Email(message: 'Bitte geben Sie eine gültige E-Mail-Adresse ein.')
                ]
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Nachricht (optional)',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Zusätzliche Informationen oder Fragen...',
                    'class' => 'form-control',
                    'maxlength' => 500
                ],
                'constraints' => [
                    new Assert\Length(
                        max: 500,
                        maxMessage: 'Die Nachricht darf maximal {{ limit }} Zeichen lang sein.'
                    )
                ]
            ])
            ->add('privacyAccepted', CheckboxType::class, [
                'label' => 'Ich akzeptiere die Datenschutzerklärung',
                'mapped' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'constraints' => [
                    new Assert\IsTrue(message: 'Sie müssen die Datenschutzerklärung akzeptieren.')
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Interesse bekunden',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }

    /**
     * Konfiguriert die Standardoptionen für das Formular.
     *
     * @param OptionsResolver $resolver Der Options-Resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}