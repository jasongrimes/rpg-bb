<?php

namespace Playground;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * A collection of Playground instances.
 */
class Playgrounds extends ArrayCollection
{
    /**
     * Get an array of Playgrounds as associative arrays.
     *
     * @return array
     */
    public function toArray($img_base_url = '')
    {
        $arr = array();
        foreach ($this->toPlaygroundArray() as $playground) {
            $arr[] = $playground->toArray($img_base_url);
        }

        return $arr;
    }

    /**
     * @return Playground[]
     */
    public function toPlaygroundArray()
    {
        return parent::toArray();
    }
}