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
    public function toArray()
    {
        $arr = array();
        foreach ($this->toPlaygroundArray() as $playground) {
            $arr[] = $playground->toArray();
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