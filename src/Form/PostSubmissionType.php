<?php

declare(strict_types=1);

namespace App\Form;

use App\Domain\Participation\Category;
use App\Domain\EventManagement\Event;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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
 * Formular für die Einreichung eines neuen Beitrags.
 */
class PostSubmissionType extends AbstractType
{
    /**
     * Baut das Formular zur Beitragseinreichung auf.
     *
     * @param FormBuilderInterface $builder Formular-Builder
     * @param array $options Formularoptionen
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Kategorie wählen...',
                'label' => 'Kategorie',
                'query_builder' => function ($repository) use ($options) {
                    $qb = $repository->createQueryBuilder('c');
                    
                    if (isset($options['event']) && $options['event'] instanceof Event) {
                        $qb->andWhere('c.event = :event')
                           ->setParameter('event', $options['event']);
                    }
                    
                    return $qb->orderBy('c.sortOrder', 'ASC')
                             ->addOrderBy('c.name', 'ASC');
                },
                'constraints' => [
                    new Assert\NotBlank(message: 'Bitte wählen Sie eine Kategorie aus.')
                ]
            ])
            ->add('title', TextType::class, [
                'label' => 'Titel',
                'attr' => [
                    'placeholder' => 'Kurzer, aussagekräftiger Titel...',
                    'maxlength' => 255
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Der Titel darf nicht leer sein.'),
                    new Assert\Length(
                        min: 10,
                        max: 255,
                        minMessage: 'Der Titel muss mindestens {{ limit }} Zeichen lang sein.',
                        maxMessage: 'Der Titel darf maximal {{ limit }} Zeichen lang sein.'
                    )
                ]
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Beschreibung (optional)',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Optional: Beschreiben Sie Ihr Anliegen in 1-2 Sätzen...',
                    'maxlength' => 500
                ],
                'constraints' => [
                    new Assert\Length(
                        min: 10,
                        max: 500,
                        minMessage: 'Die Beschreibung muss mindestens {{ limit }} Zeichen lang sein, wenn angegeben.',
                        maxMessage: 'Die Beschreibung darf maximal {{ limit }} Zeichen lang sein.'
                    )
                ]
            ])
            ->add('authorName', TextType::class, [
                'label' => 'Ihr Name',
                'attr' => [
                    'placeholder' => 'Max Mustermann'
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
            ->add('authorEmail', EmailType::class, [
                'label' => 'E-Mail-Adresse',
                'attr' => [
                    'placeholder' => 'max@beispiel.de'
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Die E-Mail-Adresse darf nicht leer sein.'),
                    new Assert\Email(message: 'Bitte geben Sie eine gültige E-Mail-Adresse ein.')
                ]
            ])
            ->add('showAuthorName', CheckboxType::class, [
                'label' => 'Name öffentlich anzeigen',
                'help' => 'Wenn aktiviert, wird Ihr Name bei dem Beitrag angezeigt.',
                'required' => false
            ])
            ->add('privacyAccepted', CheckboxType::class, [
                'label' => 'Ich akzeptiere die Datenschutzerklärung',
                'mapped' => false,
                'constraints' => [
                    new Assert\IsTrue(message: 'Sie müssen die Datenschutzerklärung akzeptieren.')
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Beitrag einreichen',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }

    /**
     * Konfiguriert die Standardoptionen für das Beitragseinreichungsformular.
     *
     * @param OptionsResolver $resolver Der Options-Resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'event' => null,
        ]);
        
        $resolver->setAllowedTypes('event', ['null', Event::class]);
    }
}