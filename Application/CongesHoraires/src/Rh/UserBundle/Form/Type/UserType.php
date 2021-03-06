<?php

namespace Rh\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class UserType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('nom')
                ->add('prenom')
                ->add('username')
                ->add('email')
                ->add('plainPassword', 'repeated', array('type' => 'password'))
                ->add('entreeEntreprise')
                ->add('cadre')
                ->add('roles', 'choice', array(
                        'choices' => array(
                                'ROLE_UTILISATEUR' => 'Utilisateur',
                                'ROLE_CHEF' => 'Chef d\'équipe',
                                'ROLE_RH' => 'Responsable RH',
                                'ROLE_ADMINISTRATEUR' => 'Administrateur'),
                        'multiple' => true));
    }
    
    /**
     * Fonction permettant d'identifier ce formType en retournant son nom.
     */
    public function getName()
    {
        return 'rh_user_usertype';
    }
    
    public function getDefaultOptions(array $options)
    {
        return array(
                'data_class' => 'Rh\UserBundle\Entity\User', );
    }
}