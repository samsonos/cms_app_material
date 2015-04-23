<?php
/**
 * Created by PhpStorm.
 * User: egorov
 * Date: 21.04.2015
 * Time: 14:52
 */
namespace samsoncms\app\user\field;

use samsonframework\core\RenderInterface;
use samsonframework\orm\QueryInterface;
use samsoncms\field\Generic;

/**
 * Overridden full name field
 * @package samsoncms\app\user
 */
class Publish extends Generic
{
    /**  Overload parent constructor and pass needed params there */
    public function __construct()
    {
        // Create object instance with fixed parameters
        parent::__construct('Published', t('Показывать', true), 0, 'publish');
    }
}
