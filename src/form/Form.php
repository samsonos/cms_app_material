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
use samsoncms\app\material\form\tab\MaterialField;
use samsonframework\core\RenderInterface;
use samsonframework\orm\QueryInterface;
use samsonframework\orm\Record;
use samsonphp\event\Event;

class Form extends \samsoncms\form\Form
{
    public $structureIds = array();

    /** @inheritdoc */
    public function __construct(RenderInterface $renderer, QueryInterface $query, Record $entity)
    {
        $this->tabs = array(
            new Entity($renderer, $query, $entity),
            new Field($renderer, $query, $entity)
        );

        $this->structureIds = dbQuery('structurematerial')->cond('MaterialID', $entity->id)->fields('StructureID');

        if (sizeof($this->structureIds)) {
            $wysiwygFields = dbQuery('field')
                ->cond('Type', 8)
                ->join('structurefield')
                ->cond('structurefield_StructureID', $this->structureIds)
                ->exec();

            foreach ($wysiwygFields as $field) {
                $this->tabs[] = new MaterialField($renderer, $query, $entity, $field);
            }
        }

        parent::__construct($renderer, $query, $entity);

        // Fire new event after creating form tabs
        Event::fire('samsoncms.material.form.created', array(& $this));
    }
}
