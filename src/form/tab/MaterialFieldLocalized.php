<?php
/**
 * Created by PhpStorm.
 * User: onysko
 * Date: 05.06.2015
 * Time: 15:27
 */

namespace samsoncms\app\material\form\tab;


use samson\activerecord\dbQuery;
use samson\cms\CMSMaterialField;
use samson\core\SamsonLocale;
use samsoncms\form\tab\Generic;
use samsonframework\core\RenderInterface;
use samsonframework\orm\QueryInterface;
use samsonframework\orm\Record;

class MaterialFieldLocalized extends Generic
{
    public $headerIndexView = 'form/tab/header/sub';
    public $contentView = 'form/tab/mf/wysiwyg';

    protected $id = 'sub_materialfield_tab';

    /** @var string Tab locale */
    protected $locale = '';

    /** @var \samsoncms\input\Field CMS input object for rendering */
    protected $inputField;

    /**
     * @param RenderInterface $renderer
     * @param QueryInterface $query
     * @param Record $entity
     * @param CMSMaterialField $materialField
     * @param string $locale
     */
    public function __construct(RenderInterface $renderer, QueryInterface $query, Record $entity, CMSMaterialField $materialField, $locale = '')
    {
        // Get type of filed
        $field = dbQuery('field')->cond('FieldID', $materialField->FieldID)->first();

        // Create CMS Field object from CMSMaterialField object
        $this->inputField = m('samsoncms_input_application')->createFieldByType(new dbQuery(), $field->Type, $materialField);

        // Save tab header name as locale name
        $this->name = $locale;

        // Generate unique html identifier
        $this->id = $this->id.'_'.$this->name;
        $this->id .= $materialField->id;

        // Call parent constructor to define all class fields
        parent::__construct($renderer, $query, $entity);
    }

    /** @inheritdoc */
    public function content()
    {
        return $this->renderer->view($this->contentView)
            ->content($this->inputField)
            ->subTabID($this->id)
            ->output();
    }
}
