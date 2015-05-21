<?php
/**
 * Created by PhpStorm.
 * User: egorov
 * Date: 21.05.2015
 * Time: 21:00
 */
namespace samsoncms\app\material;

/**
 * Material entity
 * @package samsoncms\app\material
 */
class Entity extends \samson\cms\Material
{
    /**
     * Find material record by identifier
     * @param \samsonframework\orm\QueryInterface Query object
     * @param string $identifier Material identifier
     * @param mixed $return Found material is returned here
     * @return bool True if material was found by identifier
     */
    public static function byId(\samsonframework\orm\QueryInterface $query, $identifier, &$return = null)
    {
        // Get material safely
        if ($query->id($identifier)->first($return)) {
            return true;
        } else { // Material not found
            return false;
        }
    }
}
