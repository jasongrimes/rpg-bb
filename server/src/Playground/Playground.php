<?php

namespace Playground;

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

    public $meta;

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

    public function addImage(array $data)
    {
        $image = PlaygroundImage::createFromArray($this, $data);
        $image->sortorder = count($this->images) + 1;
        $this->images[] = $image;

        return $image;
    }

    /**
     * @return PlaygroundImage[]
     */
    public function getImages()
    {
        return $this->images;
    }

    public function toArray($img_base_url = '')
    {
        $data = get_object_vars($this);

        $data['images'] = array();
        foreach ($this->images as $image) {
            $data['images'][] = $image->toArray($img_base_url);
        }

        return $data;
    }
}
