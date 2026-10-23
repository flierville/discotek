<?php

declare(strict_types=1);

namespace App\Form\Front;

use App\Form\Model\ContactData;
use App\Form\Type\HoneypotType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Votre Nom', 'attr' => ['maxlength' => 100, 'autocomplete' => 'name']])
            ->add('email', EmailType::class, ['label' => 'Votre Mail', 'attr' => ['maxlength' => 180, 'autocomplete' => 'email']])
            ->add('newsletter', CheckboxType::class, ['label' => 'Inscription à la Newsletter', 'required' => false])
            ->add('phone', TelType::class, ['label' => 'Téléphone', 'required' => false, 'attr' => ['maxlength' => 20, 'autocomplete' => 'tel']])
            ->add('message', TextareaType::class, [
                'label' => 'Votre Message',
                'help' => 'Merci de nous donner le maximum de détails',
                'attr' => ['rows' => 8],
            ])
            ->add('website', HoneypotType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ContactData::class]);
    }
}
