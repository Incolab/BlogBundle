<?php

namespace Incolab\BlogBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Incolab\BlogBundle\DependencyInjection\Configuration;

class IncolabBlogExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        
        $config = $this->processConfiguration($configuration, $configs);
        
        $container->setParameter('incolab_blog.news.nb_char_index', $config['news']['nb_char_index']);
        $container->setParameter('incolab_blog.news.news_by_page', $config['news']['news_by_page']);
    }
}