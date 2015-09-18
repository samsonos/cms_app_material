<?php
/**
 * Created by PhpStorm.
 * User: onysko
 * Date: 05.06.2015
 * Time: 15:15
 */

namespace samsoncms\app\material\form\tab;


use samson\cms\CMSMaterialField;
use samson\core\SamsonLocale;
use samsoncms\form\tab\Generic;
use samsonframework\core\RenderInterface;
use samsonframework\orm\QueryInterface;
use samsonframework\orm\Record;
use samsonphp\event\Event;

class MaterialField extends Generic
{
    /** @var string Tab name or identifier */
    protected $name = 'MaterialField Tab';

    protected $id = 'materialfield_tab';

    /** @inheritdoc */
    public function __construct(RenderInterface $renderer, QueryInterface $query, Record $entity, \samson\activerecord\field $field)
    {
        $this->name = $field->Description != '' ? $field->Description : $field->Name;
        $this->id .= '_'.$field->Name;
        // Prepare locales array with one default locale by default
        $locales = array('');

        // If field supports localization - set full locales array
        if ($field->local == 1) $locales = SamsonLocale::$locales;

        /** @var CMSMaterialField $materialField */
        $materialField = null;

        // Iterate defined locales
        if (sizeof(SamsonLocale::$locales)){
            foreach ($locales as $locale) {
                // Try to find existing CMSMaterialField record
                if (!dbQuery('\samson\cms\CMSMaterialField')
                    ->MaterialID($entity->id)
                    ->FieldID($field->id)
                    ->locale($locale)
                    ->first($materialField)
                ) {
                    // Create CMSMaterialField record
                    $materialField = new \samson\cms\CMSMaterialField(false);
                    $materialField->Active = 1;
                    $materialField->MaterialID = $entity->id;
                    $materialField->FieldID = $field->id;
                    $materialField->locale = $locale;
                    $materialField->save();
                }
                // Add child tab
                $this->subTabs[] = new MaterialFieldLocalized($renderer, $query, $entity, $materialField, $locale);
            }
        }

        // Call parent constructor to define all class fields
        parent::__construct($renderer, $query, $entity);

        Event::fire('samsoncms.material.materialfieldtab.created', array(& $this, $field));
    }

    /** @inheritdoc */
    public function content()
    {
        $content = '';

        foreach ($this->subTabs as $subTab) {
            $content .= $subTab->content();
        }

        return $this->renderer->view($this->contentView)->content($content)->output();
    }
}
