<?php

namespace Playground;

/**
 * A playground.
 */
class Playground
{
    public $id;
    public $name;
    public $address = '';
    public $lat = '0';
    public $lng = '0';
    public $ages = '';
    public $tot_swings = '';
    public $main_surface = '';
    public $restrooms = '';
    public $picnic_shelter = '';

    /** @var PlaygroundImage[] */
    protected $images = array();

    public $meta = array();

    /**
     * Factory method for creating a Playground instance from an array of data.
     *
     * @param array $data
     * @return Playground
     */
    public static function createFromArray(array $data)
    {
        $playground = new self();
        foreach ($data as $key => $value) {
            if (property_exists($playground, $key) && $key != 'images') {
                $playground->$key = $value;
            }
        }
        if (array_key_exists('distance', $data)) {
            $playground->meta['distance'] = $data['distance'];
        }

        return $playground;
    }

    /**
     * Add an image to the Playground.
     *
     * @param array $data
     * @return PlaygroundImage
     */
    public function addImage(array $data, $base_url = '')
    {
        $image = PlaygroundImage::createFromArray($this, $data);
        if ($base_url) {
            $image->setBaseUrl($base_url);
        }
        $image->sortorder = count($this->images) + 1;
        $this->images[] = $image;

        return $image;
    }

    /**
     * Get a list of images of this playground.
     *
     * @return PlaygroundImage[]
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * Get an associative array representation of the Playground.
     *
     * @return array
     */
    public function toArray()
    {
        $data = get_object_vars($this);

        $data['images'] = array();
        foreach ($this->images as $image) {
            $data['images'][] = $image->toArray();
        }

        return $data;
    }
}
