<?php
/**
 * Created by PhpStorm.
 * User: egorov
 * Date: 21.04.2015
 * Time: 14:52
 */
namespace samsoncms\app\material\field;

use samsonframework\core\RenderInterface;
use samsonframework\orm\QueryInterface;
use samsoncms\field\Generic;

/**
 * Overridden full name field
 * @package samsoncms\app\user
 */
class User extends Generic
{
    /**  Overload parent constructor and pass needed params there */
    public function __construct()
    {
        // Create object instance with fixed parameters
        parent::__construct('user', t('Автор', true), 0, 'user', false);
    }

    /**
     * Render collection entity field inner block
     * @param RenderInterface $renderer
     * @param QueryInterface $query
     * @param mixed $object Entity object instance
     * @return string Rendered entity field
     */
    public function render(RenderInterface $renderer, QueryInterface $query, $object)
    {
        $html = '';
        $user = & $object->onetoone['_user'];
        if (isset($user)) {
            $html = isset($user['s_name']) ? $user['s_name'] : '';
            $html .= isset($user['f_name']) ? ' '.$user['f_name'] : '';
            $html .= isset($user['t_name']) ? ' '.$user['t_name'] : '';
        }

        // Render input field view
        return $renderer
            ->view($this->innerView)
            ->set('class', $this->css)
            ->set('field_html', $html)
            ->output();
    }
}
