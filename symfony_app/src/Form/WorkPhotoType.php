<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Work;
use App\Entity\WorkPhoto;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkPhotoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('work', EntityType::class, [
                'label' => 'Работа',
                'class' => Work::class,
                'choice_label' => 'title',
                'placeholder' => 'Выберите работу',
            ])
            ->add('imagePath', TextType::class, [
                'label' => 'Путь к изображению',
                'help' => 'Например: uploads/legacy/40k/Captain1.jpg',
            ])
            ->add('caption', TextType::class, [
                'label' => 'Подпись к фото',
                'required' => false,
            ])
            ->add('sortOrder', IntegerType::class, [
                'label' => 'Позиция',
            ])
            ->add('isPublished', CheckboxType::class, [
                'label' => 'Опубликовано',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WorkPhoto::class,
        ]);
    }
}
