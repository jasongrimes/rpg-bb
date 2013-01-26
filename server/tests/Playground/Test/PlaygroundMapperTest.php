<?php

namespace Playground\Test;

use Playground\PlaygroundMapper;
use Playground\Playground;

class PlaygroundMapperTest extends WebTestCase
{
    /** @var PlaygroundMapper */
    protected $mapper;

    public function setUp()
    {
        parent::setUp();

        $this->mapper = new PlaygroundMapper($this->app['db']);
    }

    public function testLifecycle()
    {
        $playground = Playground::createFromArray(array('name' => 'Test playground'));
        $playground->addImage(array('filename' => 'dummy.png'));
        $this->mapper->insert($playground);

        $playground_copy = $this->mapper->getPlayground($playground->id);

        $this->assertEquals($playground->toArray(), $playground_copy->toArray());
        $this->assertEquals(reset($playground->getImages())->toArray(), reset($playground_copy->getImages())->toArray());

        $this->mapper->delete($playground);
    }

    public function testUpdate()
    {
        $playground = Playground::createFromArray(array('name' => 'Test'));
        $this->mapper->insert($playground);

        $playground->address = '123 4th St.';
        $this->mapper->update($playground);

        $playground_copy = $this->mapper->getPlayground($playground->id);

        $this->assertEquals($playground->toArray(), $playground_copy->toArray());

        $this->mapper->delete($playground);
    }
}