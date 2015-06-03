<?php
/**
 * Created by PhpStorm.
 * User: onysko
 * Date: 27.05.2015
 * Time: 13:07
 */

namespace samsoncms\app\material\form;


use samsoncms\app\material\form\tab\Entity;
use samsoncms\app\material\form\tab\Field;
use samsonframework\core\RenderInterface;
use samsonframework\orm\QueryInterface;
use samsonframework\orm\Record;
use samsonphp\event\Event;

class Form extends \samsoncms\form\Form
{
    /** @inheritdoc */
    public function __construct(RenderInterface $renderer, QueryInterface $query, Record $entity)
    {
        $this->tabs = array(
            new Entity($renderer, $query, $entity),
            new Field($renderer, $query, $entity)
        );

        parent::__construct($renderer, $query, $entity);

        // Fire new event after creating form tabs
        Event::fire('samsoncms.material.form.created', array(& $this));
    }
}
