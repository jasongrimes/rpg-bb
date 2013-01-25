<?php

namespace Playground;

class PlaygroundImage
{
    /** @var Playground */
    protected $playground;

    public $id;
    public $filename;
    public $title = '';
    public $credit = '';
    public $sortorder = 0;

    public function __construct(Playground $playground)
    {
        $this->playground = $playground;
    }

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

    public function toArray($base_url = '')
    {
        $data = get_object_vars($this);
        $data['url'] = $this->getUrl($base_url);

        unset($data['playground']);

        return $data;
    }

    public function getUrl($img_base_url = '')
    {
        if (!$this->filename) {
            return null;
        }

        return $img_base_url . '/' . $this->filename;
    }

    /**
     * @return Playground
     */
    public function getPlayground()
    {
        return $this->playground;
    }
}
