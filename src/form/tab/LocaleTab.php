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
use samsonframework\orm\Relation;

class LocaleTab extends Main
{
    public $headerIndexView = 'form/tab/header/sub';
    public $contentView = 'form/tab/main/sub_content';

    protected $id = 'sub_field_tab';

    /** @var string Tab locale */
    protected $locale = '';

    /** @inheritdoc */
    public function __construct(RenderInterface $renderer, QueryInterface $query, Record $entity, $locale = SamsonLocale::DEF)
    {
        $this->locale = $locale;

        // Set name and id of module
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
        // Load fields
        $this->loadAdditionalFields($this->entity->id, 1);

        // Iterate locale and save their generic and data
        $view = '';
        foreach ($this->additionalFields as $fieldID => $additionalField) {

            // If this field is empty go further
            if ( empty($additionalField) ) {
                continue;
            }

            // Render field header
            $view .= '<div class="template-form-input-group">'.$additionalField[$this->locale]->renderHeader($this->renderer);

            // Render field content
            $view .= $additionalField[$this->locale]->render($this->renderer, $this->query, $this->materialFields[$fieldID][$this->locale]).'</div>';
        }

        // Render tab content
        $content = $this->renderer->view("form/tab/content/fields")->fields($view)->matId($this->entity->id)->output();

        return $this->renderer->view($this->contentView)
            ->content($content)
            ->subTabID($this->id)
            ->output();
    }
}
