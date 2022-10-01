<?php
declare(strict_types=1);

namespace App\Form\Type;

use App\Service\Manager\AuthorManager;
use App\Service\Manager\CategoryManager;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;

class BookType extends AbstractEntityType
{

    protected AuthorManager   $authorManager;
    protected CategoryManager $categoryManager;

    public function __construct(
        AuthorManager $authorManager,
        CategoryManager $categoryManager
    )
    {
        $this->authorManager   = $authorManager;
        $this->categoryManager = $categoryManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'title',
                TextType::class,
                [
                    'constraints' => new NotBlank()
                ]
            )
            ->add(
                'description',
                TextareaType::class
            )
            ->add(
                'authors',
                ChoiceType::class,
                [
                    'multiple' => true,
                    'choices'  => $this->authorManager->choices(['first_name', 'last_name']),
                ]
            )
            ->add(
                'categories',
                ChoiceType::class,
                [
                    'multiple' => true,
                    'choices'  => $this->categoryManager->choices(['name']),
                ]
            )
            ->add(
                'quantity',
                NumberType::class,
                [
                    'empty_data'  => 1,
                    'scale'       => 0,
                    'constraints' => [
                        new GreaterThanOrEqual(['value' => 0])
                    ]
                ]
            );
    }
}
