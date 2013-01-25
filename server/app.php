<?php

require_once __DIR__ . '/bootstrap.php';

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Playground\PlaygroundMapper;
use Playground\Playground;

$app = new Application();

if (!defined('ENV')) {
    define('ENV', getenv('env') ?: 'prod');
}

if (ENV == 'dev') {
    $app['debug'] = (ENV == 'dev');
    ini_set('html_errors', 0);
    xdebug_disable();
}

$config = require __DIR__ . '/config/local.config.php';

$app->before(function (Request $request) {
    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());
    }
});

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
$playground_mapper = new PlaygroundMapper($app['db']);

// -----------------------------------
// Routes and controllers
// -----------------------------------

// Route: GET: /
$app->get('/', function(Application $app) {
    return new Response('Hello.');
});

// Route: GET: /playgrounds
$app->get('/playgrounds', function(Application $app, Request $request) use ($playground_mapper, $config) {
    $playgrounds = $playground_mapper->getPlaygrounds($request->query->all());
    return $app->json($playgrounds->toArray($config['image_base_url']));
});

// Route: GET: /playground/:id
$app->get('/playground/{id}', function(Application $app, $id) use ($playground_mapper, $config) {
    $playground = $playground_mapper->getPlayground($id);
    return $app->json($playground->toArray($config['image_base_url']));
});

// Route: POST: /playgrounds
$app->post('/playgrounds', function(Application $app, Request $request) use ($playground_mapper, $config) {
    $data = json_decode($request->getContent(), true);
    if (!$data) {
        return $app->json(array('error' => 'Invalid JSON.'), 400);
    }

    $playground = Playground::createFromArray($data);
    if ($playground_mapper->insert($playground)) {
        return $app->json($playground->toArray($config['image_base_url']), 201); // Should redirect to /playground/id instead?
    } else {
        return $app->json(array('error' => $playground_mapper->getLastError()), 400);
    }
});
/*
curl -H 'Content-Type: application/json' \
    -d '{
        "name": "Test Playground",
        "address": "123 4th St.",
        "lat": 123,
        "lng": 456
    }' \
    http://rpg-bb.dev:8080/playgrounds
 */

// Route: PUT: /playground/:id
// TODO

return $app;
