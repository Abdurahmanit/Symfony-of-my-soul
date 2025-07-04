<?php

namespace App\Form;

use App\Entity\Form as FilledFormEntity;
use App\Entity\Template;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class FormAnswerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Template $template */
        $template = $options['template'];

        // Add dynamic fields based on the template's questions
        foreach ($template->getQuestions() as $index => $question) {
            $questionFieldName = null;
            $questionOptions = [
                'label' => $question->getTitle(),
                'help' => $question->getDescription(),
                'required' => false, // Adjust based on if questions can be required
            ];

            // Map question type to the corresponding field in the Form entity
            switch ($question->getType()) {
                case 'string':
                    $questionFieldName = 'stringAnswer' . ($index + 1);
                    $builder->add($questionFieldName, TextType::class, $questionOptions);
                    break;
                case 'text':
                    $questionFieldName = 'textAnswer' . ($index + 1);
                    $builder->add($questionFieldName, TextareaType::class, $questionOptions);
                    break;
                case 'int':
                    $questionFieldName = 'intAnswer' . ($index + 1);
                    $builder->add($questionFieldName, IntegerType::class, array_merge($questionOptions, [
                        'constraints' => [
                            new GreaterThanOrEqual(['value' => 0, 'message' => 'Value must be non-negative.']),
                        ],
                    ]));
                    break;
                case 'checkbox':
                    $questionFieldName = 'checkboxAnswer' . ($index + 1);
                    $builder->add($questionFieldName, CheckboxType::class, array_merge($questionOptions, [
                        'required' => false,
                    ]));
                    break;
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FilledFormEntity::class,
            'template' => null, // This option must be passed when creating the form
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_answer_item',
        ]);

        $resolver->setRequired('template'); // Ensure template is always provided
        $resolver->setAllowedTypes('template', Template::class);
    }
}