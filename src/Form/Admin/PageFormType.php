<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Entity\Page;
use App\Enum\PageKind;
use App\Form\EventListener\SlugFromTitleListener;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PageFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'help' => 'Affiché dans le menu.',
                'attr' => ['data-slug-target' => 'source', 'data-action' => 'slug#sync'],
            ])
            ->add('slug', TextType::class, [
                'label' => 'Adresse de la page',
                'help' => 'Partie de l\'URL après le nom de domaine (minuscules, chiffres et tirets).',
                'attr' => ['data-slug-target' => 'slug', 'data-action' => 'slug#lock'],
            ])
            ->add('kind', EnumType::class, [
                'label' => 'Type de page',
                'class' => PageKind::class,
                'choice_label' => static fn (PageKind $kind): string => $kind->label(),
                'help' => 'Les pages Devis, Contact et Photos affichent automatiquement leur formulaire sous le contenu.',
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Contenu',
                'required' => false,
                'sanitize_html' => true,
                'sanitizer' => 'app.page_content',
            ])
            ->add('published', CheckboxType::class, ['label' => 'Page en ligne', 'required' => false])
            ->add('inMenu', CheckboxType::class, ['label' => 'Afficher dans le menu', 'required' => false])
            ->add('inFooter', CheckboxType::class, ['label' => 'Afficher dans le pied de page', 'required' => false])
            ->add('position', IntegerType::class, ['label' => 'Ordre dans le menu', 'help' => 'Les plus petits nombres en premier.'])
            ->add('menuColor', TextType::class, [
                'label' => 'Couleur dans le menu',
                'required' => false,
                'help' => 'Optionnel, par exemple #ff0000. Vide = rose du site.',
                'attr' => ['placeholder' => '#ff0000', 'maxlength' => 7],
            ])
            ->add('metaTitle', TextType::class, [
                'label' => 'Titre pour Google (balise title)',
                'required' => false,
                'help' => 'Vide = titre de la page. Le nom du site est ajouté automatiquement.',
            ])
            ->add('metaDescription', TextareaType::class, [
                'label' => 'Description pour Google',
                'required' => false,
                'help' => 'Vide = description générale du site.',
                'attr' => ['rows' => 3],
            ])
            ->add('metaKeywords', TextareaType::class, [
                'label' => 'Mots clefs',
                'required' => false,
                'help' => 'Vide = mots clefs généraux du site.',
                'attr' => ['rows' => 2],
            ])
            ->addEventSubscriber(new SlugFromTitleListener());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Page::class]);
    }
}
