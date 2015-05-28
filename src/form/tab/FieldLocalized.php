<?php
/**
 * Created by PhpStorm.
 * User: onysko
 * Date: 27.05.2015
 * Time: 15:06
 */

namespace samsoncms\app\material\form\tab;


use samson\core\SamsonLocale;
use samsoncms\form\tab\Generic;
use samsonframework\core\RenderInterface;
use samsonframework\orm\QueryInterface;
use samsonframework\orm\Record;

class FieldLocalized extends Generic
{
    public $headerIndexView = 'form/tab/header/sub';
    public $contentView = 'form/tab/field_tab/sub_content';

    protected $id = 'sub_field_tab';

    /** @var string Tab locale */
    protected $locale = '';

    /** @inheritdoc */
    public function __construct(RenderInterface $renderer, QueryInterface $query, Record $entity, $locale = SamsonLocale::DEF)
    {
        $this->locale = $locale;

        if ($locale != '') {
            $this->id .= '-'.$this->locale;
            $this->name = $this->locale;
        } else {
            $this->name = 'all';
        }

        // Call parent constructor to define all class fields
        parent::__construct($renderer, $query, $entity);
    }

    /** @inheritdoc */
    public function content()
    {
        $table = new FieldTabTable($this->entity, $this->entity['onetomany']['_structure'], $this->locale);

        return $this->renderer->view($this->contentView)
            ->content($table->render(null, $this->renderer))
            ->subTabID($this->id)
            ->output();
    }
}
