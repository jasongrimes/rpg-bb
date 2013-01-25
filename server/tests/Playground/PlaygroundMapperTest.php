<?php

namespace Playground\Tests;

use Silex\WebTestCase;
use Playground\PlaygroundMapper;
use Playground\Playground;

class PlaygroundMapperTest extends WebTestCase
{
    /** @var PlaygroundMapper */
    protected $mapper;

    public function createApplication()
    {
        return require __DIR__ . '/../../app.php';
    }

    public function setUp()
    {
        parent::setUp();

        $this->mapper = new PlaygroundMapper($this->app['db']);
    }

    public function testLifecycle()
    {
        $playground = Playground::createFromArray(array(
            'name' => 'Test playground',
            'images' => array(
                array('filename' => 'dummy.png'),
            ),
        ));
        $this->mapper->insert($playground);

        $new_playground = $this->mapper->getPlayground($playground->id);

        $this->assertEquals($playground->toArray(), $new_playground->toArray());
        $this->assertEquals(reset($playground->getImages())->toArray(), reset($new_playground->getImages())->toArray());

        $this->mapper->delete($playground);
    }
}