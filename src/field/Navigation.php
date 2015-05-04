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
class Navigation extends Generic
{
    /** @var string Path to field view file */
    protected $innerView = 'www/collection/field/navigation';

    /**  Overload parent constructor and pass needed params there */
    public function __construct()
    {
        // Create object instance with fixed parameters
        parent::__construct('navigation', t('Раздел', true), 0, 'navigation', false);
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
        /** @var \samson\activerecord\structure $structure Material structures list */
        $html = array();
        foreach ($object->onetomany['_structure'] as $structure) {
            $html[] = '<a class="inner" title="' . t('Перейти к материалам ЭСС', true) .
                '" href="' . url_build($renderer->id(), $structure->id) . '">' . $structure->Name . '</a>';
        }

        // Render input field view
        return $renderer
            ->view($this->innerView)
            ->set('class', $this->css)
            ->set($object, 'item')
            ->set('navigation', implode(', ', $html))
            ->output();
    }
}
