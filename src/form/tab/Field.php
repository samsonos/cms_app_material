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

class Field extends Generic
{
    /** @var string Tab name or identifier */
    protected $name = 'Field Tab';

    protected $id = 'field_tab';

    /** @inheritdoc */
    public function __construct(RenderInterface $renderer, QueryInterface $query, Record $entity)
    {
        $entity = dbQuery('\samson\cms\CMSMaterial')
            ->cond('MaterialID', $entity->id)
            ->join('structurematerial')
            ->join('structure')
            ->first();

        $structures = $entity['onetomany']['_structure'];

        $nonLocalizedFieldsCount = dbQuery('structurefield')->join('field')->cond('StructureID', array_keys($structures))->cond('field_local', 0)->count();
        $localizedFieldsCount = dbQuery('structurefield')->join('field')->cond('StructureID', array_keys($structures))->cond('field_local', 1)->count();

        // If we have not localized fields
        if ($nonLocalizedFieldsCount > 0) {
            // Create default sub tab
            $this->subTabs[] = new FieldLocalized($renderer, $query, $entity, '');
        }

        // Iterate available locales if we have localized fields
        if (sizeof(SamsonLocale::$locales) && $localizedFieldsCount > 0) {
            foreach (SamsonLocale::$locales as $locale) {
                // Create child tab
                $subTab = new FieldLocalized($renderer, $query, $entity, $locale);
                $this->subTabs[] = $subTab;
            }
        }

        // Call parent constructor to define all class fields
        parent::__construct($renderer, $query, $entity);
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
