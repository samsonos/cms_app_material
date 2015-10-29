<?php
namespace samsoncms\app\material;

use samson\activerecord\Condition;
use samson\core\SamsonLocale;
use samson\activerecord\dbRelation;
use samson\cms\CMSMaterial;
use samson\activerecord\dbMySQL;
use samson\activerecord\dbMySQLConnector;
use samsonframework\orm\Argument;

/**
 * CMSMaterial form 
 * 
 * @author Kotenko Nikita <nick.w2r@gmail.com>
 * @author Egorov Vitaly <egorov@samsonos.com>
 */
class Form
{
    /**
     * Pointer to CMSMaterial object
     * @var \samson\cms\CMSMaterial
     */
    public $material;

    /**
     * Collection of CMSNavs related to material
     * @see \samson\cms\CMSNav
     */
    public $navs = array();

    /**
     * Collection of CMSField releted to material CMSNavs
     * @see \samson\cms\CMSField
     */
    public $fields = array();

    /**
     * Collection of CMSMaterialField releted to material
     * @see \samson\cms\CMSMaterialField
     */
    public $mfs = array();

    /**
     * Collection of loaded form tabs
     * @see \samson\cms\web\material\FormTab
     */
    public $tabs = array();

    /** Tabs sorter by index */
    private function tabs_sorter($t1, $t2)
    {
        return $t1->index > $t2->index;
    }

    /**
     * Constructor
     * @param string $material_id CMSMaterial identifier
     */
    public function __construct($material_id = null, $parentStructure = null)
    {
        // Variable to store navigation ids to get fields by them from structurefields
        $navigationForFields = array();
        // Add structure material condition
        $scg = new Condition('or');
        $scg->arguments[] = new Argument(dbMySQLConnector::$prefix . 'structurematerial_Active', 1);
        $scg->arguments[] = new Argument(dbMySQLConnector::$prefix . 'structurematerial_Active', NULL, dbRelation::ISNULL);

        // Perform CMSMaterial request with related CMSNavs
        if (dbQuery(ns_classname('CMSMaterial', 'samson\cms'))
            ->MaterialID($material_id)
            ->join('structurematerial')
            ->join('structure')
            ->join('user')
            ->Active(1)
            ->cond($scg)
            ->first($this->material)
        ) {
            // Existing material handling

            // If material has relations with cmsnav
            $cmsnavs = &$this->material->onetomany['_structure'];
            if (isset($cmsnavs)) {
                // WYSIWYG query
                $fields_query = dbQuery('\samson\cms\CMSNavField')
                    ->join('\samson\cms\CMSField')
                    ->order_by('FieldID', 'ASC')
                    ->Active(1);


                // If material has related cmsnavs - gather material related cmsnavs info
                foreach ($cmsnavs as $structure) {
                    $this->navs[$structure->id] = $structure;
                    if ($structure->type != 2) {
                        $navigationForFields[] = $structure->id;
                    }
                }



                // Add cmsnavs ids to query
                $fields_query->StructureID($navigationForFields);

                // Perform DB request
                if ($fields_query->exec($fields)) foreach ($fields as $data) {
                    // Pointer to field object
                    $db_field = &$data->onetoone['_field'];

                    // Add field data to collection
                    $this->fields[] = $db_field;

                    if (isset($db_field->Type) && $db_field->Type == '8') {
                        $this->tabs[] = new MaterialFieldLocalizedTab($this, $db_field, 'WYSIWYG');
                    }
                }
            }
        }
        // TODO: This else block below does not work at all because its condition is checked in controller
        // Material does not found
        else {
            // Material empty draft creation
            $this->material = new CMSMaterial();
            $this->material->Draft = $this->material->id;
            $this->material->Name = 'Новый материал';
            $this->material->Created = date('h:m:i d.m.y');
//			$this->material->user_id = auth()->user->id;
            $this->material->user_id = m('social')->user()->user_id;
            $this->material->Active = 1;
            $this->material->save();
            if (isset($parentStructure)) {
                /** @var \samson\cms\web\navigation\CMSNav $str */
                $str = null;
                if (dbQuery('\samson\cms\web\navigation\CMSNav')->id($parentStructure)->first($str)) {
                    while (isset($str)) {
                        $this->navs[$str->id] = $str;
                        $str = $str->parent();
                    }
                }
            }
        }

        // Autoload base tab classes
        class_exists('samsoncms\app\material\MainTab');
        class_exists('samsoncms\app\material\FieldLocalizedTab');

        // Iterate declared classes to find other FormTab children to load to form
        foreach (get_declared_classes() as $class) {
            // If class if samson\cms\web\material\FormTab child
            if (is_subclass_of($class, ns_classname('FormTab', 'samsoncms\app\material'))) {
                // Tab supports automatic rendering flag
                eval('$ar = ' . $class . '::$AUTO_RENDER;'); // PHP 5.2 support
                if ($ar === true) {
                    // Create and add FormTab instance to form tabs collection
                    $this->tabs[] = new $class($this);
                }
            }
        }

        // Sort tabs by their index
        usort($this->tabs, array($this, 'tabs_sorter'));
    }

    /** Render CMSMaterial form to HTML */
    public function render()
    {
        $tabs_html = '';
        $tabs_header = '';
        $tabs_control = '';

        // Iterate loaded form tabs
        foreach ($this->tabs as $tab) {
            // Gather tabs data to differrent collections
            $tabs_html .= $tab->content();
            $tabs_header .= $tab->header();
            $tabs_control .= $tab->control();
        }

        // Render view
        return m()
            ->title(t('Форма', true))
            ->view('form/index')
            ->cmsmaterial($this->material)
            ->tabs($tabs_html)
            ->tabs_control($tabs_control)
            ->tabs_headers($tabs_header)
            ->output();
    }

}
