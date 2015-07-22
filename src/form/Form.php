<?php
/**
 * Created by PhpStorm.
 * User: onysko
 * Date: 27.05.2015
 * Time: 13:07
 */
namespace samsoncms\app\material\form;

use samsoncms\app\material\form\tab\Main;
use samsoncms\app\material\form\tab\Field;
use samsoncms\app\material\form\tab\MaterialField;
use samsonframework\core\RenderInterface;
use samsonframework\orm\QueryInterface;
use samsonframework\orm\Record;
use samsonphp\event\Event;

/**
 * SamsonCMS material application generic form
 * @package samsoncms\app\material\form
 */
class Form extends \samsoncms\form\Form
{
    /** @var array Collection of navigation identifiers  */
    public $navigationIDs = array();

    /** @inheritdoc */
    public function __construct(RenderInterface $renderer, QueryInterface $query, Record $entity)
    {
        // Fill generic tabs
        $this->tabs = array(
            new Main($renderer, $query, $entity),
            new Field($renderer, $query, $entity)
        );

        $this->navigationIDs = dbQuery('structurematerial')->cond('MaterialID', $entity->id)->fields('StructureID');

        if (sizeof($this->navigationIDs)) {
            $wysiwygFields = dbQuery('field')
                ->cond('Type', 8)
                ->join('structurefield')
                ->cond('structurefield_StructureID', $this->navigationIDs)
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
