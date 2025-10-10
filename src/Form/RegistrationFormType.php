<?php

namespace App\Form;

use App\Entity\Admin;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class RegistrationFormType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options): void
  {
    $builder
      ->add('username', TextType::class, [
        'constraints' => [new Assert\NotBlank(), new Assert\Length(min: 3, max: 180)],
      ])
      ->add('email', EmailType::class, [
        'required' => false,
        'constraints' => [new Assert\Email()],
      ])
      ->add('password', PasswordType::class, [
        'label' => 'Password',
        'mapped' => false,
        'attr' => ['autocomplete' => 'new-password'],
        'constraints' => [
          new Assert\NotBlank(),
          new Assert\Length(min: 8, max: 4096),
        ],
      ])
      ->add('submit', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
      $resolver->setDefaults(['data_class' => Admin::class]);
    }
}
