<?php

namespace Playground;

/**
 * An image of a playground.
 */
class PlaygroundImage
{
    /** @var Playground */
    protected $playground;

    public $id;
    public $filename;
    public $title = '';
    public $credit = '';
    public $sortorder = 0;

    /**
     * Constructor.
     *
     * @param Playground $playground The Playground with which this image is associated.
     */
    public function __construct(Playground $playground)
    {
        $this->playground = $playground;
    }

    /**
     * Factory method for creating a PlaygroundImage.
     *
     * @param Playground $playground
     * @param array $data
     * @return PlaygroundImage
     */
    public static function createFromArray(Playground $playground, array $data)
    {
        $image = new self($playground);

        foreach ($data as $key => $val) {
            if (property_exists($image, $key) && $key != 'url') {
                $image->$key = $val;
            }
        }

        return $image;
    }

    /**
     * Get an associative array representation of the PlaygroundImage.
     *
     * @param string $base_url Optional. The base URL for playground images.
     * @return array
     */
    public function toArray($base_url = '')
    {
        $data = get_object_vars($this);
        $data['url'] = $this->getUrl($base_url);

        unset($data['playground']);

        return $data;
    }

    /**
     * Get a URL for the playground image.
     *
     * @param string $img_base_url
     * @return null|string
     */
    public function getUrl($img_base_url = '')
    {
        if (!$this->filename) {
            return null;
        }

        return $img_base_url . '/' . $this->filename;
    }

    /**
     * Get the Playground with which this image is associated.
     *
     * @return Playground
     */
    public function getPlayground()
    {
        return $this->playground;
    }
}
