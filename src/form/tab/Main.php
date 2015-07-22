<?php
/**
 * Created by PhpStorm.
 * User: onysko
 * Date: 27.05.2015
 * Time: 13:07
 */
namespace samsoncms\app\material\form\tab;

use samsoncms\form\field\Generic;
use samsonframework\core\RenderInterface;
use samsonframework\orm\QueryInterface;
use samsonframework\orm\Record;
use samsonframework\orm\Relation;

/**
 * Main material form tab
 * @package samsoncms\app\material\form\tab
 */
class Main extends \samsoncms\form\tab\Entity
{
    /** @var string Tab header */
    public $name = 'Главная';

    /** @var \samsoncms\form\field\Generic[] Collection of additional form fields */
    protected $additionalFields = array();

    /** @var \samsonframework\orm\Record[] Collection of additional material entity fields */
    protected $materialFields = array();

    /** @inheritdoc */
    public function __construct(RenderInterface $renderer, QueryInterface $query, Record $entity)
    {
        // Add generic material entity fields
        $this->fields = array(
            new Generic('Name', t('Название', true), 0),
            new Generic('Url', t('Url', true), 0),
            new Generic('Published', t('Активен', true), 11),
        );

        /** @var \samsonframework\orm\Record[] $structureIDs Get material entity navigation identifiers */
        $structureIDs = array();
        if ($query->className('structurematerial')
            ->cond('MaterialID', $entity->id)
            ->cond('Active', 1)
            ->fields('StructureID', $structureIDs)) {

            /** @var \samsonframework\orm\Record[] $structureFields Get structure-fields records for this entity with fields data */
            $structureFields = array();
            if($query->className('structurefield')
                ->cond('field_Type', array('9', '8'), Relation::NOT_EQUAL)// Exclude WYSIWYG & gallery
                ->cond('field_local', 0)// Not localized
                ->cond('Active', 1)// Not deleted
                ->cond('StructureID', $structureIDs)
                ->join('field')
                ->group_by('field_FieldID')
                ->order_by('FieldID', 'ASC')
                ->exec($structureFields)) {


                /** @var array $fieldIDs Collection of field identifiers */
                $fieldIDs = array();
                foreach ($structureFields as $structureField) {
                    // Gather used field identifiers
                    $fieldIDs[] = $structureField->FieldID;

                    // If additional field is found
                    $field = & $structureField->onetoone['_field'];
                    if (isset($field)) {
                        // Create input field grouped by field identifier
                        $this->additionalFields[$structureField->FieldID] = new Generic(
                            $field->Name,
                            isset($field->Description{0}) ? $field->Description : $field->Name,
                            $field->Type
                        );
                    }
                }

                // Get all material-fields objects for rendering input fields grouped by field identifier
                foreach($query->className('materialfield')->cond('FieldID', $fieldIDs)->cond('MaterialID', $entity->id)->exec() as $mf){
                    $this->materialFields[$mf->FieldID] = $mf;
                }
            }
        }


        // Call parent constructor to define all class fields
        parent::__construct($renderer, $query, $entity);
    }

    /** @inheritdoc */
    public function content()
    {
        // Iterate all entity fields
        $view = '';
        foreach ($this->fields as $field) {
            // Render field header
            $view .= '<div class="template-form-input-group">'.$field->renderHeader($this->renderer);
            // Render field content
            $view .= $field->render($this->renderer, $this->query, $this->entity).'</div>';
        }

        foreach ($this->additionalFields as $fieldID => $additionalField) {
            // Render field header
            $view .= '<div class="template-form-input-group">'.$additionalField->renderHeader($this->renderer);
            // Render field content
            $view .= $additionalField->render($this->renderer, $this->query, $this->materialFields[$fieldID]).'</div>';
        }

        // Render tab content
        return $this->renderer->view($this->contentView)->content($view)->output();
    }
}
