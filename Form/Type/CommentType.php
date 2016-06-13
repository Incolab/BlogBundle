<?php

namespace Incolab\BlogBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\CallbackTransformer;

class CommentType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            //->add('author')
            ->add('content', TextareaType::class)
            //->add('news')
            //->add('createdAt', 'datetime')
        ;
        
        // liste des balises autofermantes (non échapée par strip_tags)
        // area, base, br, col, embed, hr, img, input, keygen, link, meta, param, source, track, wbr
        
        $builder->get('content')
            ->addModelTransformer(new CallbackTransformer(
                                                          // Before send form
                                                          function ($originalContent) {
                                                            
                                                            return $originalContent;
                                                          },
                                                          // Before persist
                                                          function ($submittedContent) {
                                                            // supprime tous les tags html sauf les autofermante et (br,p, a)
                                                            $cleaned = strip_tags($submittedContent, '<br><p><a><ul><ol><li><strong><em>');

                                                            return $cleaned;
                                                          })
                                  );
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Incolab\BlogBundle\Entity\Comment'
        ));
    }
}
