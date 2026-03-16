<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Work;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
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
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Work::class,
        ]);
    }
}
