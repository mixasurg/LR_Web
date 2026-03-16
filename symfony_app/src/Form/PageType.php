<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Page;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Название',
            ])
            ->add('menuTitle', TextType::class, [
                'label' => 'Название в меню',
                'required' => false,
                'help' => 'Если пусто, будет использовано название страницы.',
            ])
            ->add('slug', TextType::class, [
                'label' => 'Slug',
                'help' => 'Например: services, about-us, menu',
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Контент (HTML разрешен)',
                'attr' => ['rows' => 12],
            ])
            ->add('position', IntegerType::class, [
                'label' => 'Позиция в меню',
            ])
            ->add('showInMenu', CheckboxType::class, [
                'label' => 'Показывать в меню',
                'required' => false,
            ])
            ->add('isPublished', CheckboxType::class, [
                'label' => 'Опубликована',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Page::class,
        ]);
    }
}
