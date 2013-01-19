<?php

require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$config = require __DIR__ . '/../local.config.php';

$app = new Silex\Application();
$app['debug'] = true;

// Register service providers
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'   => 'pdo_mysql',
        'dbname' => $config['db_master']['dbname'],
        'host' => $config['db_master']['host'],
        'user' => $config['db_master']['user'],
        'password' => $config['db_master']['pass'],
    ),
));

// Routes and controllers
$app->get('/', function() use ($app) {
    $data = $app['db']->fetchAll('SHOW DATABASES');

    return new Response(print_r($data, true));
});

$app->run();
