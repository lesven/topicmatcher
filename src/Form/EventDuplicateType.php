<?php

namespace App\Form;

use App\Domain\EventManagement\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Formular zum Duplizieren eines Events oder Erstellen eines Templates aus einem Quell-Event.
 */
class EventDuplicateType extends AbstractType
{
    /**
     * Baut das Duplikationsformular mit vorausgefüllten Werten aus dem Quell-Event.
     *
     * @param FormBuilderInterface $builder Formular-Builder
     * @param array $options Formularoptionen (erfordert 'source_event')
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $sourceEvent = $options['source_event'];
        
        $builder
            ->add('name', TextType::class, [
                'label' => 'Event-Name',
                'data' => $sourceEvent->getName() . ' (Kopie)',
                'constraints' => [
                    new Assert\NotBlank(message: 'Bitte geben Sie einen Event-Namen ein.'),
                    new Assert\Length(
                        max: 255,
                        maxMessage: 'Der Event-Name darf maximal {{ limit }} Zeichen lang sein.'
                    ),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Name des neuen Events',
                ],
            ])
            ->add('slug', TextType::class, [
                'label' => 'URL-Slug',
                'data' => $sourceEvent->getSlug() . '-kopie',
                'constraints' => [
                    new Assert\NotBlank(message: 'Bitte geben Sie einen URL-Slug ein.'),
                    new Assert\Length(
                        max: 255,
                        maxMessage: 'Der URL-Slug darf maximal {{ limit }} Zeichen lang sein.'
                    ),
                    new Assert\Regex(
                        pattern: '/^[a-z0-9\-]+$/',
                        message: 'Der URL-Slug darf nur Kleinbuchstaben, Zahlen und Bindestriche enthalten.'
                    ),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'pattern' => '[a-z0-9\-]+',
                    'placeholder' => 'url-slug-fuer-das-event',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Beschreibung',
                'data' => $sourceEvent->getDescription(),
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Kurze Beschreibung des Events (optional)',
                ],
            ])
            ->add('location', TextType::class, [
                'label' => 'Veranstaltungsort',
                'data' => $sourceEvent->getLocation(),
                'required' => false,
                'constraints' => [
                    new Assert\Length(
                        max: 255,
                        maxMessage: 'Der Veranstaltungsort darf maximal {{ limit }} Zeichen lang sein.'
                    ),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ort der Veranstaltung (optional)',
                ],
            ])
            ->add('website', UrlType::class, [
                'label' => 'Website',
                'data' => $sourceEvent->getWebsite(),
                'required' => false,
                'constraints' => [
                    new Assert\Url(message: 'Bitte geben Sie eine gültige URL ein.'),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://example.com (optional)',
                ],
            ])
            ->add('startDate', DateType::class, [
                'label' => 'Startdatum',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('endDate', DateType::class, [
                'label' => 'Enddatum',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('copyCategories', CheckboxType::class, [
                'label' => 'Kategorien kopieren',
                'data' => true,
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ]);
            
        // Template-Option nur anzeigen, wenn das Quell-Event ein Template ist
        if ($sourceEvent->isTemplate()) {
            $builder->add('markAsTemplate', CheckboxType::class, [
                'label' => 'Als Template markieren',
                'data' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ]);
        }
    }

    /**
     * Konfiguriert die Standardoptionen für das Duplikationsformular.
     *
     * @param OptionsResolver $resolver Der Options-Resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'source_event' => null,
        ]);

        $resolver->setAllowedTypes('source_event', Event::class);
        $resolver->setRequired('source_event');
    }
}