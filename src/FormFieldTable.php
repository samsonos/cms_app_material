<?php
namespace samsoncms\app\material;

use samson\activerecord\dbRelation;
use samson\core\SamsonLocale;
use samson\activerecord\dbQuery;
use samson\pager\Pager;
use samson\activerecord\dbMySQLConnector;
use samsonphp\event\Event;

/**
 * Class for rendering SamsonCMS Field table
 * 
 * @author Nikita Kotenko <nick.w2r@gmail.com>
 * @author Egorov Vitaly <egorov@samsonos.com>
 */
class FormFieldTable extends \samson\cms\table\Table
{
    /** Change table template */
    public $table_tmpl = 'form/fieldtable/tmpl';

    /** Default table row template */
    public $row_tmpl = 'form/fieldtable/row/index';

    /** Existing CMSMaterial field records */
    private $materialfields = array();

    /** Pointer to CMSMaterial */
    private $db_material;

    /** Pointer to Form */
    private $form;

    /** Fields locale */
    private $locale;

    public $dbQuery;

    //public $debug = true;

    /**
     * Constructor
     * @param CMSMaterial $db_material CMSMaterial pointer
     * @param string $locale Field table locale
     */
    public function __construct(\samson\cms\CMSMaterial & $db_material, Form & $form, $locale = SamsonLocale::DEF)
    {
        $this->locale = $locale;

        $this->dbQuery = new dbQuery();

        // Save pointer to Form
        $this->form = &$form;

        // Save pointer to CMSMaterial
        $this->db_material = &$db_material;

        // Prepare db query for all related material fields to structures
        $this->query = dbQuery('samson\cms\CMSNavField')
            ->cond(dbMySQLConnector::$prefix . 'field_Type', array('8', '5'), dbRelation::NOT_EQUAL)
            ->join('samson\cms\CMSField')
            ->group_by(dbMySQLConnector::$prefix . 'field_FieldID')
            ->order_by('FieldID', 'ASC')
            ->Active(1);

        // Get all related structures and remove them from request
        $relatedStructureIDs = dbQuery('structure')->cond('type', 1)->fields('StructureID');

        if ($db_material->type != 0 && sizeof($relatedStructureIDs)) {
            $fieldsIDs = dbQuery('structurefield')->cond('StructureID', $relatedStructureIDs)->fields('FieldID');
            if (sizeof($fieldsIDs)) {
                // Remove related structure fields from field table
                $this->query->cond('FieldID', $fieldsIDs, dbRelation::NOT_EQUAL);
            }
        }

        // Delete table structures from query
        $navs = $form->navs;
        foreach ($navs as $key => $nav) {
            if ($nav->type == 2) {
                unset($navs[$key]);
            }
        }
        // If material has related structures add them to query
        $structureIDs = array_keys($navs);
        if (sizeof($structureIDs)) {
            $this->query->StructureID($structureIDs);
        }

        // Create materialfield db query
        $mfQuery = dbQuery('materialfield')
            ->locale($locale)
            ->MaterialID($db_material->id)
            ->Active(1);

        // Add localization condition if necessary
        if ($locale != '') { // Show only localizable fields
            $this->query->cond(dbMySQLConnector::$prefix . 'field_local', 1);
        } else { // Show only not localizable fields
            $this->query->cond(dbMySQLConnector::$prefix . 'field_local', 0);
        }

        // Perform DB request to find existing materialfields
        //db()->debug(true);
        if ($mfQuery->exec($db_materialfields)) {
            // Recollect materialfields by their identifiers
            foreach ($db_materialfields as $db_materialfield) {
                // Save materialfields object by field identifier
                $this->materialfields[$db_materialfield->FieldID] = $db_materialfield;
            }
        }

        //db()->debug(false);

        //trace('Locale:'.$locale);
        //trace('Fields:'.implode(',', array_keys($this->materialfields)));

        // Constructor treed
        parent::__construct($this->query);
    }

    /** @see \samson\cms\site\Table::row() */
    public function row(&$db_row, Pager &$pager = null, $module = null)
    {
        // Get field metadata
        $db_field = &$db_row->onetoone['_field'];

        // If parent Field object not found countinue
        if (!isset($db_field)) return e('StructureField# ## - Field not found(##)', E_SAMSON_CMS_ERROR, array($db_row->id, $db_row->FieldID));

        // Try to get already created materialfield object by field id
        if (isset($this->materialfields[$db_row->FieldID])) $db_mf = &$this->materialfields[$db_row->FieldID];
        // Otherwise create new material field object
        else {
            //trace('New field for'.$db_field->id);
            $db_mf = new \samson\activerecord\materialfield(false);
            $db_mf->locale = $this->locale;
            $db_mf->Active = 1;
            $db_mf->MaterialID = $this->db_material->id;
            $db_mf->FieldID = $db_field->id;
            $db_mf->save();
        }

        // Create input element for field
        $input = null;

        // Depending on field type
        if ($db_field->Type != 9) {
            // Create input element for field
            $input = m('samsoncms_input_application')->createFieldByType($this->dbQuery, $db_field->Type, $db_mf);
        } else {
            return false;
        }
        if ($db_field->Type == 4) {
            /** @var \samsoncms\input\select\Application $input Input select type */
            $input->build($db_field->Value);
        }

        Event::fire('samson.cms.web.material.input', array($db_field, & $input, & $db_mf, & $this->dbQuery));

        // Render field row
        return m('material')
            ->view($this->row_tmpl)
            ->cmsfield($input)
            ->matfield($db_mf)
            ->fieldname(isset($db_field->Description{0}) ? $db_field->Description : $db_field->Name)
            ->field($db_field)
            ->pager($pager)
            ->output();
    }

    public function render(array $db_rows = null, $module = null)
    {
        // Rows HTML
        $rows = '';

        // if no rows data is passed - perform db request
        if (!isset($db_rows)) $db_rows = $this->query->exec();

        // If we have table rows data
        if (is_array($db_rows)) {
            // Save quantity of rendering rows
            $this->last_render_count = sizeof($db_rows);

            // Debug info
            $rn = 0;
            $rc = sizeof($db_rows);

            // Iterate db data and perform rendering
            foreach ($db_rows as & $db_row) {
                if ($this->debug) elapsed('Rendering row ' . $rn++ . ' of ' . $rc . '(#' . $db_row->id . ')');
                $rows .= $this->row($db_row, $this->pager);
                //catch(\Exception $e){ return e('Error rendering row#'.$rn.' of '.$rc.'(#'.$db_row->id.')'); }
            }

        } // No data found after query, external render specified
        else $rows .= $this->emptyrow($this->query, $this->pager);

        //elapsed('render pages: '.$this->pager->total);

        // Render table view
        return m($module)
            ->view($this->table_tmpl)
            ->set($this->pager)
            ->rows($rows)
            ->output();
    }
}
