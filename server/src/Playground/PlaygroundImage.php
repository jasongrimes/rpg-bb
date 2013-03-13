<?php

namespace Playground;

/**
 * An image of a playground.
 */
class PlaygroundImage
{
    /** @var Playground */
    protected $playground;

    /**
     * TODO: Isn't this really a data point that should be stored with the rest of the image data?
     * TODO: If we move it, shouldn't we update the database?
     * TODO: Can't CDN stuff be handled on the server side, with a single generic URL? (But then we can't use CNAMEs to get more parallel connections.)
     * TODO: Also what about HTTP vs HTTPS?
     * TODO: Of course that fancy stuff could be handled at runtime by passing a custom base url to getUrl().
     * @var string
     */
    protected $base_url = 'http://s3.amazonaws.com/grit-rpg/images';

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
     * Set the base URL used to create the image URL. Should not contain a trailing slash.
     *
     * @param $base_url
     */
    public function setBaseUrl($base_url)
    {
        $this->base_url = $base_url;
    }

    /**
     * Factory method for creating a PlaygroundImage.
     *
     * @param Playground $playground
     * @param array $data
     * @return PlaygroundImage
     */
    public static function createFromArray(Playground $playground, array $data, $base_url = '')
    {
        $image = new self($playground);
        if ($base_url) {
            $image->setBaseUrl($base_url);
        }

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
    public function toArray()
    {
        $data = get_object_vars($this);
        $data['url'] = $this->getUrl();

        unset($data['playground']);

        return $data;
    }

    /**
     * Get a URL for the playground image.
     *
     * @return null|string
     */
    public function getUrl()
    {
        if (!$this->filename) {
            return null;
        }

        return $this->base_url . '/' . $this->filename;
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
