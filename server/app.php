<?php

require_once __DIR__ . '/bootstrap.php';

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Playground\PlaygroundMapper;
use Playground\Playground;

$app = new Application();

// Environment-specific customization
if (!defined('ENV')) define('ENV', getenv('env') ?: 'prod');
if (ENV == 'dev') {
    $app['debug'] = true;
    ini_set('html_errors', 0);
    xdebug_disable();
}

$config = require __DIR__ . '/config/local.config.php';

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
$app['playground_mapper'] = new PlaygroundMapper($app['db']);

// Decode JSON request data
$app->before(function (Request $request) use ($app) {
    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
        $data = json_decode($request->getContent(), true);

        // If JSON is invalid, json_decode returns null.
        if ($data === null && $request->getContent() !== null) {
            return $app->json(array('error' => 'Invalid JSON.'), 400);
        }

        $request->request->replace(is_array($data) ? $data : array());
    }
});

// -----------------------------------
// Routes and controllers
// -----------------------------------

// Route: GET: /hello
$app->get('/hello', function() {
    return new Response('Hello.');
});

// Route: GET /playgrounds
$app->get('/playgrounds', function(Application $app, Request $request) use ($config) {
    $playgrounds = $app['playground_mapper']->getPlaygrounds($request->query->all());
    return $app->json($playgrounds->toArray());
});

// Route: GET /playgrounds/:id
$app->get('/playgrounds/{id}', function(Application $app, $id) use ($config) {
    $playground = $app['playground_mapper']->getPlayground($id);
    return $app->json($playground->toArray());
});

// Route: POST /playgrounds
$app->post('/playgrounds', function(Application $app, Request $request) use ($config) {
    $data = json_decode($request->getContent(), true);
    $playground = Playground::createFromArray($data);

    if ($app['playground_mapper']->insert($playground)) {
        return $app->json($playground->toArray(), 201); // Should redirect to /playground/id instead?
    } else {
        return $app->json(array('error' => $app['playground_mapper']->getLastError()), 400);
    }
});
/* POST with CURL like this:
curl -H 'Content-Type: application/json' \
    -d '{
        "name": "Test Playground",
        "address": "123 4th St.",
        "lat": 123,
        "lng": 456
    }' \
    http://rpg-bb.dev:8080/playgrounds
 */

// Route: PUT /playgrounds/:id
$app->put('/playgrounds/{id}', function(Application $app, Request $request, $id) use ($config) {
    $data = json_decode($request->getContent(), true);
    if (!$data) {
        return $app->json(array('error' => 'Invalid JSON.'), 400);
    }

    $playground = Playground::createFromArray($data);
    $playground->id = $id;

    if ($app['playground_mapper']->update($playground)) {
        return $app->json($playground->toArray(), 200); // Should redirect to /playground/id instead?
    } else {
        return $app->json(array('error' => $app['playground_mapper']->getLastError()), 400);
    }
});

// Route: DELETE /playgrounds/:id
$app->delete('/playgrounds/{id}', function(Application $app, Request $request, $id) {
    $playground = $app['playground_mapper']->getPlayground($id);
    $app['playground_mapper']->delete($playground);
    return new Response('', 204);

});

return $app;
