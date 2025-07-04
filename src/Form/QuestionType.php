<?php

namespace App\Form;

use App\Entity\Question;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class QuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Question Title',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Question Type',
                'choices' => [
                    'Single-line String' => 'string',
                    'Multi-line Text' => 'text',
                    'Non-negative Integer' => 'int',
                    'Checkbox' => 'checkbox',
                ],
            ])
            ->add('showInTable', CheckboxType::class, [
                'label' => 'Display in results table',
                'required' => false,
            ])
            ->add('position', IntegerType::class, [
                'label' => 'Order',
                'constraints' => [
                    new GreaterThanOrEqual(['value' => 0]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Question::class,
            'csrf_protection' => false, // No CSRF on sub-forms if dynamically added by JS
        ]);
    }
}