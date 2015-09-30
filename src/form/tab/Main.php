<?php
/**
 * Created by PhpStorm.
 * User: onysko
 * Date: 27.05.2015
 * Time: 13:07
 */
namespace samsoncms\app\material\form\tab;

use samson\activerecord\materialfield;
use samsoncms\form\field\Generic;
use samsonframework\core\RenderInterface;
use samsonframework\orm\QueryInterface;
use samsonframework\orm\Record;
use samsonframework\orm\Relation;
use samson\core\SamsonLocale;

/**
 * Main material form tab
 * @package samsoncms\app\material\form\tab
 */
class Main extends \samsoncms\form\tab\Entity
{
    /** @var string Tab header */
    public $name = 'Главная';

    /** @var string Default path to content view */
    public $contentView = 'form/tab/main/materialContent';

    /** @var \samsoncms\form\field\Generic[] Collection of additional form fields */
    protected $additionalFields = array();

    /** @var \samsonframework\orm\Record[] Collection of additional material entity fields */
    protected $materialFields = array();

    /**
     * Store all additional fields in the material
     * @param $entityID
     * @param int $locale Load locale fields or not
     */
    public function loadAdditionalFields($entityID, $locale = 0, $structureIds = null)
    {
        /** @var \samsonframework\orm\Record[] $structureIDs Get material entity navigation identifiers */
        $structureIDs = array();
        if ($this->query->className('structurematerial')
            ->cond('MaterialID', $entityID)
            ->cond('Active', 1)
            ->fields('StructureID', $structureIDs)) {

            // Get only structure which not material table
            $newStructure = null;
            if (
                $this->query->className('structure')
                    ->cond('StructureID', $structureIDs)
                    ->cond('type', array('2'), Relation::NOT_EQUAL)
                    ->fields('StructureID', $newStructure)
            ) {
                $structureIDs = $newStructure;
            }

            /** @var \samsonframework\orm\Record[] $structureFields Get structure-fields records for this entity with fields data */
            $structureFields = array();
            if($this->query->className('structurefield')
                ->cond('field_Type', array('9', '8', '5'), Relation::NOT_EQUAL)// Exclude WYSIWYG & gallery
                ->cond('field_local', $locale)// Not localized
                ->cond('Active', 1)// Not deleted
                ->cond('StructureID', empty($structureIds) ? $structureIDs : $structureIds)
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

                        // If need get not localized fields
                        if ($locale == 0) {

                            // If there no materialfield item then create it
                            $mf = null;
                            if (
                            !$this->query->className('materialfield')
                                ->cond('FieldID', $field->id)
                                ->cond('MaterialID', $entityID)
                                ->cond('Active', 1)
                                ->first($mf)
                            ) {

                                // Create new materialfield
                                $mf = new \samson\activerecord\materialfield(false);

                                $mf->locale = '';
                                $mf->Active = 1;
                                $mf->MaterialID = $entityID;
                                $mf->FieldID = $field->id;
                                $mf->save();

                            }

                            // Create input field grouped by field identifier
                            $this->additionalFields[] = new Generic(
                                $field->Name,
                                isset($field->Description{0}) ? $field->Description : $field->Name,
                                $field->Type
                            );

                            // Save mf
                            $this->materialFields[] = $mf;

                            // It is localized fields
                        } else {

                            // Get current locales
                            $locales = SamsonLocale::$locales;
                            $mf = null;
                            if (sizeof(SamsonLocale::$locales)){

                                // Init arrays
                                $localeGeneric = array();
                                $localeData = array();

                                // Iterate locale and save their generic and data
                                foreach ($locales as $local) {

                                    // If there no materialfield item then create it
                                    if (
                                        !$this->query->className('materialfield')
                                            ->cond('FieldID', $field->id)
                                            ->cond('MaterialID', $entityID)
                                            ->cond('Active', 1)
                                            ->cond('locale', $local)
                                            ->first($mf)
                                    ) {

                                        // Create new materialfield
                                        $mf = new \samson\activerecord\materialfield(false);

                                        $mf->locale = $local;
                                        $mf->Active = 1;
                                        $mf->MaterialID = $entityID;
                                        $mf->FieldID = $field->id;
                                        $mf->save();

                                    }

                                    // Create generic for this field
                                    $localeGeneric[$local] = new Generic(
                                        $field->Name,
                                        isset($field->Description{0}) ? $field->Description : $field->Name,
                                        $field->Type
                                    );

                                    // Save mf
                                    $localeData[$local] = $mf;
                                }

                                // Save all localized data
                                $this->additionalFields[] = $localeGeneric;
                                $this->materialFields[] = $localeData;
                            }
                        }
                    }
                }
            }
        }
    }

    /** @inheritdoc */
    public function __construct(RenderInterface $renderer, QueryInterface $query, Record $entity)
    {
        // Add generic material entity fields
        $this->fields = array(
            new Generic('Name', t('Название', true), 0),
            new Generic('Url', t('Url', true), 0),
            new Generic('Published', t('Активен', true), 11),
        );

        // Call parent constructor to define all class fields
        parent::__construct($renderer, $query, $entity);
    }

    /** @inheritdoc */
    public function content()
    {
        // Load additional fields
        $this->loadAdditionalFields($this->entity->id);

        // Iterate all entity fields
        $view = '';
        foreach ($this->fields as $field) {
            // Render field header
            $view .= '<div class="template-form-input-group">'.$field->renderHeader($this->renderer);
            // Render field content
            $view .= $field->render($this->renderer, $this->query, $this->entity).'</div>';
        }

        // Iterate material entity additional not localized fields
        foreach ($this->additionalFields as $fieldID => $additionalField) {
            // Render field header
            $view .= '<div class="template-form-input-group">'.$additionalField->renderHeader($this->renderer);
            // Render field content
            $view .= $additionalField->render($this->renderer, $this->query, $this->materialFields[$fieldID]).'</div>';
        }

        $structureIDs = dbQuery('structurematerial')
            ->cond('MaterialID', $this->entity->id)
            ->cond('Active', 1)
            ->fields('StructureID');

        // Iterate all loaded CMSNavs
        $parentSelect = '';

        $navs = dbQuery('structure')
            // Show only visible structures
            ->cond('visible', 1)
            ->exec();

        // Iterate all structures of this material
        foreach ($navs as $db_structure) {
            // If material is related to current CMSNav
            $selected = '';
            if (in_array($db_structure->id, $structureIDs)) {
                $selected = 'selected';
            }
            // Generate CMSNav option
            $parentSelect .= '<option ' . $selected . ' value="' .
                $db_structure->id . '">' . $db_structure->Name . '</option>';
        }

        $nameSelectStructureField = t('Теги структуры',true);

        // Render tab content
        return $this->renderer->view($this->contentView)->nameSelectStructureField($nameSelectStructureField)->parentSelect($parentSelect)->content($view)->matId($this->entity->id)->output();
    }
}
