<?php

use Silex\Application;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\TranslationServiceProvider;

$app = new Application();
$app->register(new UrlGeneratorServiceProvider());
$app->register(new ValidatorServiceProvider());
$app->register(new ServiceControllerServiceProvider());
$app->register(new FormServiceProvider());
$app->register(new TwigServiceProvider(), array(
    'twig.path' => array(
        __DIR__ . '/../templates',
        __DIR__ . '/../vendor/braincrafted/bootstrap-bundle/Bc/Bundle/BootstrapBundle/Resources/views/Form'
    )));

$app['twig'] = $app->share($app->extend('twig', function ($twig, $app) {
    //$twig->addExtension(new \Bc\Bundle\BootstrapBundle\Twig\BootstrapIconExtension);
    //$twig->addExtension(new \Bc\Bundle\BootstrapBundle\Twig\BootstrapLabelExtension);
    //$twig->addExtension(new \Bc\Bundle\BootstrapBundle\Twig\BootstrapBadgeExtension);
    //$twig->addExtension(new \Bc\Bundle\BootstrapBundle\Twig\BootstrapFormExtension);
    return $twig;
}));

$app->register(new TranslationServiceProvider(), array(
    'translator.messages' => array(),
));

$app['upload_folder'] = __DIR__ . '/../web/uploads';

return $app;
