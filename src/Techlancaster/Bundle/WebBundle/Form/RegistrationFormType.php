<?php

namespace Techlancaster\Bundle\WebBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class RegistrationFormType extends AbstractType
{
    private $class;

    /**
     * @param string $class The User class name
     */
    public function __construct($class)
    {
        $this->class = $class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', null, array('label' => 'Username'))
            ->add('email', 'email', array('label' => 'Email'))
            ->add('plainPassword', 'repeated', array(
                'type' => 'password',
                'first_options' => array('label' => 'Password'),
                'second_options' => array('label' => 'Confirm Password'),
                'invalid_message' => 'Please make sure passwords match',
            ))
            ->add('captcha', 'captcha', array(
                'label_attr' => array('class'=>'captcha')
            ))
            ->add('register', 'submit', array(
                'attr' => array('class' => 'btn-success')
            ))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => $this->class,
            'intention'  => 'registration',
        ));
    }

    public function getName()
    {
        return 'tl_user_registration';
    }
}