<?php

namespace Playground;

use Doctrine\DBAL\Connection;

/**
 * Data mapper for Playground instances.
 */
class PlaygroundMapper
{
    /** @var Connection */
    protected $conn;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Get a Playground instance by its ID.
     *
     * @param int $id
     * @return Playground|null
     */
    public function getPlayground($id)
    {
        return $this->getPlaygrounds(array('id' => $id))->first();
    }

    /**
     * Get a collection of Playground instances that match the given criteria.
     *
     * @param array $criteria
     * @return Playgrounds
     */
    public function getPlaygrounds($criteria = array())
    {
        /** @var Playground[] $playgrounds */
        $playgrounds = new Playgrounds();

        $pg_data = $this->getPlaygroundsData($criteria);
        foreach ($pg_data as $data) {
            $playground = Playground::createFromArray($data);
            $playgrounds[$playground->id] = $playground;
        }

        $img_data = $this->getPlaygroundImageData($playgrounds->getKeys());
        foreach ($img_data as $playground_id => $pg_img_data) {
            $playground = $playgrounds[$playground_id];
            foreach ($pg_img_data as $data) {
                $playground->addImage($data);
            }
        }

        return $playgrounds;
    }

    /**
     * @param array $criteria
     * @return array
     */
    protected function getPlaygroundsData($criteria = array())
    {
        $params = array();

        $is_geo_search = (array_key_exists('lat', $criteria) && array_key_exists('lng', $criteria) && array_key_exists('radius', $criteria));

        $sql = 'SELECT * ';

        if ($is_geo_search) {
            $sql .= ', ( 3959 * acos( cos( radians(:lat) ) * cos( radians( lat ) ) *
                cos( radians( lng ) - radians(:lng) ) + sin( radians(:lat) ) *
                sin( radians( lat ) ) ) ) AS distance ';
            $params['lat'] = $criteria['lat'];
            $params['lng'] = $criteria['lng'];
        }

        $sql .= 'FROM playground ';
        $sql .= 'WHERE 1 ';

        if (array_key_exists('id', $criteria)) {
            $sql .= 'AND id = :id ';
            $params['id'] = $criteria['id'];
        }

        if ($is_geo_search) {
            $sql .= 'GROUP BY id '; // TODO: Is this needed?
            $sql .= 'HAVING distance < :radius ';
            $sql .= 'ORDER BY distance ';
            $params['radius'] = $criteria['radius'];
        }

        $playgrounds_data = $this->conn->fetchAll($sql, $params);

        /*
        if (empty($playgrounds_data)) {
            return array();
        }

        $playground_ids = array();
        foreach ($playgrounds_data as $row) {
            $playground_ids[] = $row['id'];
        }

        $img_data = $this->getPlaygroundImageData($playground_ids);

        foreach ($playgrounds_data as &$playground_data) {
            $playground_data['images'] = array_key_exists($playground_data['id'], $img_data) ? $img_data[$playground_data['id']] : array();
        }
        */

        return $playgrounds_data;
    }

    /**
     * @param array $playground_ids
     * @return array
     */
    protected function getPlaygroundImageData(array $playground_ids)
    {
        $data = array();

        $sql = 'SELECT * FROM playground_image ';
        $sql .= 'WHERE playground_id IN (' . implode(', ', $playground_ids) . ')';
        $sql .= 'ORDER BY sortorder ';

        $rows = $this->conn->fetchAll($sql);
        foreach ($rows as $row) {
            $data[$row['playground_id']][] = $row;
        }

        return $data;
    }

    public function insert(Playground $playground)
    {
        $keys = array('name', 'address', 'lat', 'lng', 'ages', 'tot_swings', 'main_surface', 'restrooms', 'picnic_shelter');

        $sql = 'INSERT INTO playground ';
        $sql .= '(' . implode(', ', $keys) . ') ';
        $sql .= 'VALUES (:' . implode(', :', $keys) . ') ';

        $params = array_intersect_key($playground->toArray(), array_flip($keys));

        $this->conn->executeUpdate($sql, $params);

        $playground->id = $this->conn->lastInsertId();

        foreach ($playground->getImages() as $image) {
            $this->insertImage($image);
        }

        return true; // TODO: Should do validation, and return false if it fails.
    }

    protected function insertImage(PlaygroundImage $image)
    {
        $keys = array('id', 'filename', 'title', 'credit', 'sortorder', 'playground_id');

        $sql = 'INSERT INTO playground_image ';
        $sql .= '(' . implode(', ', $keys) . ') ';
        $sql .= 'VALUES (:' . implode(', :', $keys) . ') ';

        $params = array_intersect_key($image->toArray(), array_flip($keys));
        $params['playground_id'] = $image->getPlayground()->id;

        $this->conn->executeUpdate($sql, $params);

        $image->id = $this->conn->lastInsertId();
    }

    public function delete(Playground $playground)
    {
        foreach ($playground->getImages() as $image) {
            $this->deleteImage($image);
        }
        $this->conn->executeUpdate('DELETE FROM playground WHERE id = ?', array($playground->id));
    }

    protected function deleteImage(PlaygroundImage $image)
    {
        $this->conn->executeUpdate('DELETE FROM playground_image WHERE id = ?', array($image->id));
    }

}