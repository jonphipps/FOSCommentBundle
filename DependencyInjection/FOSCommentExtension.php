<?php

namespace FOS\CommentBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;

class FOSCommentExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $configuration = new Configuration();

        $config = $processor->process($configuration->getConfigTree(), $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        if (!in_array(strtolower($config['db_driver']), array('mongodb', 'orm'))) {
            throw new \InvalidArgumentException(sprintf('Invalid db driver "%s".', $config['db_driver']));
        }
        $loader->load(sprintf('%s.xml', $config['db_driver']));

        foreach (array('value_transformer', 'blamer', 'form', 'creator', 'spam_detection') as $basename) {
            $loader->load(sprintf('%s.xml', $basename));
        }

        if (array_key_exists('acl', $config)) {
            $loader->load(sprintf('%s.xml', 'acl'));

            $container->setAlias('fos_comment.manager.thread', 'fos_comment.manager.thread.acl');
            $container->setAlias('fos_comment.manager.comment', 'fos_comment.manager.comment.acl');
        }
        else
        {
            $container->setAlias('fos_comment.manager.thread', 'fos_comment.manager.default.thread');
            $container->setAlias('fos_comment.manager.comment', 'fos_comment.manager.default.comment');
        }

        $container->setParameter('fos_comment.model.comment.class', $config['class']['model']['comment']);
        $container->setParameter('fos_comment.form.comment.class', $config['class']['form']['comment']);

        $container->setParameter('fos_comment.akismet.url', $config['akismet']['url']);
        $container->setParameter('fos_comment.akismet.api_key', $config['akismet']['api_key']);

        $container->setAlias('fos_comment.form_factory.comment', $config['service']['form_factory']['comment']);
        $container->setAlias('fos_comment.creator.thread', $config['service']['creator']['thread']);
        $container->setAlias('fos_comment.creator.comment', $config['service']['creator']['comment']);
        $container->setAlias('fos_comment.blamer.comment', $config['service']['blamer']['comment']);
        $container->setAlias('fos_comment.spam_detection.comment', $config['service']['spam_detection']['comment']);
    }
}
