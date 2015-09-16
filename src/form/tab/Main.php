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

    /** @var string Default path to content view */
    public $contentView = 'form/tab/main/materialContent';

    /** @var \samsoncms\form\field\Generic[] Collection of additional form fields */
    protected $additionalFields = array();

    /** @var \samsonframework\orm\Record[] Collection of additional material entity fields */
    protected $materialFields = array();

    public function loadAdditionalFields($entityID, & $formFields = array(), & $materialFields = array())
    {

        $this->addFieldToTab($entityID);

        /** @var \samsonframework\orm\Record[] $structureIDs Get material entity navigation identifiers */
        $structureIDs = array();
        if ($this->query->className('structurematerial')
            ->cond('MaterialID', $entityID)
            ->cond('Active', 1)
            ->fields('StructureID', $structureIDs)) {

            /** @var \samsonframework\orm\Record[] $structureFields Get structure-fields records for this entity with fields data */
            $structureFields = array();
            if($this->query->className('structurefield')
                ->cond('field_Type', array('9', '8'), Relation::NOT_EQUAL)// Exclude WYSIWYG & gallery
                //->cond('field_local', 0)// Not localized
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
                        $formFields[$structureField->FieldID] = new Generic(
                            $field->Name,
                            isset($field->Description{0}) ? $field->Description : $field->Name,
                            $field->Type
                        );
                    }
                }

                // Get all material-fields objects for rendering input fields grouped by field identifier
                foreach($this->query->className('materialfield')->cond('FieldID', $fieldIDs)->cond('MaterialID', $entityID)->exec() as $mf){
                    $this->materialFields[$mf->FieldID] = $mf;
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

        $this->addFieldToTab($entity->id);

        // Call parent constructor to define all class fields
        parent::__construct($renderer, $query, $entity);

        //$this->loadAdditionalFields($entity->id);
    }

    public function addFieldToTab($entityId){

        $entity = dbQuery('\samson\cms\CMSMaterial')
            ->cond('MaterialID', $entityId)
            ->join('structurematerial')
            ->join('structure')
            ->first();

        if (isset($entity['onetomany']) && isset($entity['onetomany']['_structure'])) {
            $structures = $entity['onetomany']['_structure'];
            $structuresId = array();
            foreach ($structures as $structure) {
                if ($structure->type == 0) {
                    $structuresId[] = $structure->StructureID;
                    break;
                }
            }
            $nonLocalizedFields = dbQuery('structurefield')->join('field')->cond('StructureID', $structuresId)->cond('field_local', 0);
            $nonLocalizedFieldsCount = $nonLocalizedFields->count();

            // If we have not localized fields
            if ($nonLocalizedFieldsCount > 0) {

                foreach ($nonLocalizedFields->exec() as $filed) {
                    if (isset($filed['onetoone']['_field'])) {

                        $filedFull = $filed['onetoone']['_field'];
                        if (!empty($filedFull)) {

                            if ($filedFull->Type == 5 || $filedFull->Type == 9) {
                                continue;
                            }
                            if ($filedFull->Type == 4){
                                trace($filedFull, 1);
                            }

                            // Get field type
                            $this->additionalFields[] = new Generic($filedFull->Name, t($filedFull->Description, true), $filedFull->Type);

                            // Get materialfield for getting their data
                            $this->materialFields[] = dbQuery('materialfield')->cond('MaterialID', $entityId)->cond('FieldID', $filedFull->id)->cond('locale', 0)->first();
                        }
                    }
                }
            }
        }
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

        $navs = dbQuery('structure')->exec();

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
