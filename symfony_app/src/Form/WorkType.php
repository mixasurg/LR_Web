<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Work;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Название работы',
            ])
            ->add('slug', TextType::class, [
                'label' => 'Slug',
                'help' => 'Например: captain, inceptors, norn',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Описание (HTML разрешен)',
                'attr' => ['rows' => 8],
            ])
            ->add('sortOrder', IntegerType::class, [
                'label' => 'Позиция',
            ])
            ->add('isPublished', CheckboxType::class, [
                'label' => 'Опубликована',
                'required' => false,
            ])
            ->add('isFeatured', CheckboxType::class, [
                'label' => 'Избранная (для главной)',
                'required' => false,
                'help' => 'На главной выводится до 6 избранных работ.',
            ])
            ->add('storageImagePaths', ChoiceType::class, [
                'label' => 'Фото из R2 для этой работы',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'choices' => $options['storage_image_choices'],
                'disabled' => $options['storage_image_choices'] === [],
                'help' => $options['storage_image_choices'] === []
                    ? 'Список из R2 недоступен. Можно добавить фото позже в разделе "Админ: фото".'
                    : 'Выберите одно или несколько фото (Ctrl/⌘ + клик), они будут добавлены к работе после сохранения.',
                'attr' => [
                    'size' => min(14, max(6, count($options['storage_image_choices']))),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Work::class,
            'storage_image_choices' => [],
        ]);
        $resolver->setAllowedTypes('storage_image_choices', 'array');
    }
}
