<?php

$app = require_once __DIR__ . '/../server/app.php';

$app->run();



return;

// --- Deprecated -------------------------------------------------------------------

ini_set('html_errors', 0);
xdebug_disable();

require_once __DIR__.'/../vendor/autoload.php';

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Playground\PlaygroundMapper;
use Playground\Playground;

$config = require __DIR__ . '/../local.config.php';
$config['image_base_url'] = 'http://s3.amazonaws.com/grit-rpg/images'; // TODO: Move this to local config file.

$app = new Application();
$app['debug'] = true;

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
$playground_mapper = new PlaygroundMapper($app['db'], $config['image_base_url']);

// -----------------------------------
// Routes and controllers
// -----------------------------------

// Route: GET: /
$app->get('/', function(Application $app) {
    return new Response('Hello.');
});

// Route: GET: /playgrounds
$app->get('/playgrounds', function(Application $app, Request $request) use ($playground_mapper) {
    $playgrounds = $playground_mapper->getPlaygrounds($request->query->all());
    return $app->json($playgrounds->toArray());
});

// Route: GET: /playground/:id
$app->get('/playground/{id}', function(Application $app, $id) use ($playground_mapper) {
    $playground = $playground_mapper->getPlayground($id);
    return $app->json($playground->toArray());
});

// Route: POST: /playgrounds
$app->post('/playgrounds', function(Application $app, Request $request) use ($playground_mapper, $config) {
    $data = json_decode($request->getContent(), true);
    if (!$data) {
        return $app->json(array('error' => 'Invalid JSON.'), 400);
    }

    $playground = Playground::createFromArray($data, $config['image_base_url']);
    if ($playground_mapper->insert($playground)) {
        return $app->json($playground->toArray(), 201); // Should redirect to /playground/id instead?
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

$app->run();
