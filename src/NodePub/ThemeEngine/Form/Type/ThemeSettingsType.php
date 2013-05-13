<?php

namespace NodePub\ThemeEngine\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use NodePub\ThemeEngine\Theme;

class ThemeSettingsType extends AbstractType
{
    protected $theme,
              $fontStacks;

    public function __construct(Theme $theme, array $fontStacks)
    {
        $this->theme = $theme;
        $this->fontStacks = $fontStacks;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($this->theme->getSettingsKeys() as $key) {

            $type = end(explode('_', $key));

            switch ($type) {
                case 'color':
                    $builder->add($key, 'text', array(
                        'attr' => array('class' => '-colorPicker')
                    ));
                    break;

                case 'font':
                    $builder->add($key, 'choice', array(
                        'choices' => $this->fontStacks,
                        'expanded' => false,
                        'attr' => array('class' => '-fontStack')
                    ));
                    break;
                
                default:
                    $builder->add($key);
                    break;
            }
        }
    }

    public function getName()
    {
        return 'theme_settings';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }
}