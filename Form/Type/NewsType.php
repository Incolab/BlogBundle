<?php

namespace Incolab\BlogBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\CallbackTransformer;

class NewsType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            //->add('author')
            ->add('title', TextType::class)
            //->add('slug')
            ->add('content', TextareaType::class)
            //->add('createdAt', 'datetime')
            //->add('updatedAt', 'datetime')
            ->add('isPublished', CheckboxType::class, array('label'    => 'Published?',
                                                            'required' => false,)
                 );
        
        // liste des balises autofermantes (non échapée par strip_tags)
        // area, base, br, col, embed, hr, img, input, keygen, link, meta, param, source, track, wbr
        
        $builder->get('content')
            ->addModelTransformer(new CallbackTransformer(
                                                          // Transforme <br/> en \n
                                                          function ($originalContent) {
                                                            
                                                            return $originalContent;
                                                          },
                                                          function ($submittedContent) {
                                                            // supprime tous les tags html sauf les autofermante et (br,p, a)
                                                            //$cleaned = strip_tags($submittedContent, '<br><p><a>');

                                                            // transform any \n to real <br/>
                                                            return $submittedContent;
                                                            //return str_replace("\n", '<br/>', $cleaned);
                                                          }
                                                          ));
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Incolab\BlogBundle\Entity\News'
        ));
    }
}
