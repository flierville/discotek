<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Entity\Settings;
use App\Form\DataTransformer\EmailListTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SiteSettingsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('siteName', TextType::class, ['label' => 'Nom de la société', 'help' => 'Utilisé dans les emails.'])
            ->add('publicEmail', EmailType::class, ['label' => 'Adresse email affichée'])
            ->add('phone', TextType::class, ['label' => 'Téléphone', 'required' => false])
            ->add('address', TextType::class, ['label' => 'Adresse postale', 'required' => false])
            ->add('contactRecipients', TextareaType::class, [
                'label' => 'Destinataires du formulaire de contact',
                'help' => 'Une adresse email par ligne.',
                'attr' => ['rows' => 3],
            ])
            ->add('quoteRecipients', TextareaType::class, [
                'label' => 'Destinataires des demandes de devis',
                'help' => 'Une adresse email par ligne.',
                'attr' => ['rows' => 3],
            ])
            ->add('siteTitle', TextType::class, [
                'label' => 'Titre du site (balise title)',
                'help' => 'Titre de la page d\'accueil, ajouté à la fin du titre des autres pages.',
            ])
            ->add('metaDescription', TextareaType::class, ['label' => 'Description pour Google', 'required' => false, 'attr' => ['rows' => 3]])
            ->add('metaKeywords', TextareaType::class, ['label' => 'Mots clefs', 'required' => false, 'attr' => ['rows' => 2]])
            ->add('googleAnalyticsId', TextType::class, [
                'label' => 'Identifiant Google Analytics 4',
                'required' => false,
                'attr' => ['placeholder' => 'G-XXXXXXXXXX'],
            ]);

        $builder->get('contactRecipients')->addModelTransformer(new EmailListTransformer());
        $builder->get('quoteRecipients')->addModelTransformer(new EmailListTransformer());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Settings::class]);
    }
}
