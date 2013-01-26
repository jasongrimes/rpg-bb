<?php

namespace Playground\Test;

use Playground\PlaygroundMapper;
use Playground\Playground;

class ControllerTest extends WebTestCase
{
    /** @var PlaygroundMapper */
    protected $mapper;

    public function setUp()
    {
        parent::setUp();

        $this->mapper = $this->app['playground_mapper'];
    }

    public function testPostPlayground()
    {
        // Create a new playground via the REST API.
        $data = array('name' => 'Some playground');

        $client = $this->createClient();
        $client->request('POST', '/playgrounds', array(), array(), array(), json_encode($data));

        // Test the response.
        $response_data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($data['name'], $response_data['name']);
        $this->assertArrayHasKey('id', $response_data);

        // Test that the new playground data was stored in the database.
        $playground = $this->mapper->getPlayground($response_data['id']);
        $this->assertInstanceOf('Playground\Playground', $playground);

        // Delete the playground.
        $this->mapper->delete($playground);
    }

    public function testPutPlayground()
    {
        // Create a new playground instance.
        $playground = Playground::createFromArray(array('name' => 'Test'));
        $this->mapper->insert($playground);

        // Update it via the REST URI.
        $new_name = 'New test';
        $data = array('name' => $new_name);
        $uri = '/playground/'. $playground->id;

        $client = $this->createClient();
        $client->request('PUT', $uri, array(), array(), array(), json_encode($data));

        // Check the response code.
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Check that the playground was updated in the database.
        $playground = $this->mapper->getPlayground($playground->id);
        $this->assertEquals($new_name, $playground->name);

        // Delete the playground.
        $this->mapper->delete($playground);
    }
}