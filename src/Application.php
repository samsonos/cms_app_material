<?php
namespace samsoncms\app\material;

use samson\activerecord\dbQuery;
use samson\cms\CMSNavMaterial;
use samson\pager\Pager;
use samson\cms\Material;
use samson\cms\App;

/*use samsonos\cms\ui\MenuItem;*/

/**
 * SamsonCMS generic material application.
 *
 * This application covers all actions that can be done
 * with materials and related entities in SamsonCMS.
 *
 * @package samson\cms\web\material
 */
class Application extends App
{
    /** View materials table prefix */
    const VIEW_TABLE_NAME = 'table';

    /** Application name */
    public $name = 'Материалы';

    /** Identifier */
    protected $id = 'material';

    /** Table rows count */
    protected $materialCount = 15;

    /** Controllers */

    /**
     * Generic controller
     *
     * @param null $navigationId
     * @param null $search
     * @param null $page
     */
    public function __handler($navigationId = null, $search = null, $page = null)
    {
        // Generate localized title
        $title = t($this->name, true);

        // Set view scope
        $renderer = $this->view('index');

        // Try to find structure
        if (isset($navigationId) && dbQuery('\samson\cms\Navigation')->id($navigationId)->first($navigationId)) {
            // Add structure title
            $title = t($navigationId->Name, true) . ' - ' . $title;

            // Pass Navigation to view
            $this->set($navigationId, 'navigation');
        }

        // Old-fashioned direct search input form POST if not passed
        $search = !isset($search) ? (isset($_POST['search']) ? $_POST['search'] : '') : $search;

        if (!isset($navigationId)) {
            $this->set('all_materials', true);
        }
        if (is_object($navigationId)) {
            $navigationId = $navigationId->id;
        }

        // Set view data
        $renderer
            ->title($title)
            ->set('search', $search)
            ->set($this->__async_table($navigationId, $search, $page));
    }

    /** Generic material form controller */
    public function __form($materialId = null, $navigation = null)
    {
        // If this is form for a new material with structure relation
        if ($materialId == 0 && isset($navigation)) {
            // Create new material db record
            $material = new \samson\cms\CMSMaterial(false);
            $material->Active = 1;
            $material->Created = date('Y-m-d H:m:s');

            $user = m('social')->user();
            $material->UserID = $user->UserID;
            $material->save();

            // Set new material as current
            $materialId = $material->id;

            // Convert parent CMSNavigation to an array
            $navigationArray = !is_array($navigation) ? array($navigation) : $navigation;

            // Fill parent CMSNavigation relations for material
            foreach ($navigationArray as $navigation) {
                // Create relation with structure
                $structureMaterial = new \samson\activerecord\structurematerial(false);
                $structureMaterial->MaterialID = $material->id;
                $structureMaterial->StructureID = $navigation;
                $structureMaterial->Active = 1;
                $structureMaterial->save();
            }
        }

        // Create form object
        $form = new Form($materialId, $navigation);

        if ($materialId == 0) {
            $this->set('new_material', true);
        }
        // Render form
        $this->html($form->render());
    }

    /** Main logic */

    /** Async form */
    function __async_form($materialId = null, $navigation = null)
    {
        // Create form object
        $form = new Form($materialId);

        // Success
        return array('status' => true, 'form' => $form->render(), 'url' => $this->id . '/form/' . $materialId);
    }

    /** Async materials save */
    function __async_save()
    {
        /** @var array $result Asynchronous controller result */
        $result = array('status' => false);

        // If we have POST data
        if (isset($_POST)) {
            // Create empty object
            /* @var $material \samson\cms\CMSMaterial */
            $material = new Material(false);

            // If material identifier is passed and it's valid
            if (isset($_POST['MaterialID']) && $_POST['MaterialID'] > 0) {
                $material = dbQuery('samson\cms\Material')->id($_POST['MaterialID'])->first();
            } else { // New material creation
                // Fill creation ts
                $material->Created = date('Y-m-d H:m:s');
                $material->Active = 1;
            }

            $material->Modyfied = date('Y-m-d H:m:s');
            // Make it not draft
            $material->Draft = 0;

            if (isset($_POST['Name'])) {
                $material->Name = $_POST['Name'];
            }
            if (isset($_POST['Published'])) {
                $material->Published = $_POST['Published'];
            }
            if (isset($_POST['type'])) {
                $material->type = $_POST['type'];
            }
            if (isset($_POST['Url'])) {
                $material->Url = $_POST['Url'];
            }

            // Save object to DB
            $material->save();

            /** @var \samson\activerecord\structurematerial $structureMaterial Clear existing
             * relations between material and structures */
            foreach (dbQuery('structurematerial')->cond('MaterialID', $material->id)->exec() as $structureMaterial) {
                $structureMaterial->delete();
            }

            // Iterate relations between material and structures
            if (isset($_POST['StructureID'])) {
                foreach ($_POST['StructureID'] as $structureId) {
                    // Save record
                    $sm = new CMSNavMaterial(false);
                    $sm->MaterialID = $material->id;
                    $sm->StructureID = $structureId;
                    $sm->Active = 1;
                    $sm->save();
                }
            }

            // Success
            $result['status'] = true;
            $result[] = $this->__async_form($material->id);
//            array_merge(array('status' => true), );
        }

        // Fail
        return $result;
    }

    /**
     * Render materials list with pager
     *
     * @param string $navigationId Structure identifier
     * @param string $search Keywords to filter table
     * @param int $page Current table page
     * @return array Asynchronous response containing status and materials list with pager on success
     * or just status on asynchronous controller failure
     */
    public function __async_table($navigationId = '0', $search = '', $page = 1)
    {
        $navigationId = isset($navigationId ) ? $navigationId : '0';
        $search = !empty($search) ? $search  : 0;
        $page = isset($page ) ? $page : 1;

        // We must always receive at least one navigataion id to filter materials
        $navigationIds = isset($navigationId) && !empty($navigationId)
            ? array($navigationId)
            : dbQuery('structure')->fieldsNew('StructureID'); // Use all navigation entities as filter

        $pager = new Pager(
            $page,
            $this->materialCount,
            $this->id . '/'.self::VIEW_TABLE_NAME.'/' . $navigationId . '/' . $search
        );

        $collection = new Collection($this, new dbQuery(), $pager);

        return array_merge(
            array('status' => 1),
            $collection
                ->navigation($navigationIds)
                ->search($search)
                ->fill()
                ->toView(self::VIEW_TABLE_NAME . '_')
        );
    }

    /**
     * Publish/Unpublish material
     * @param mixed $materialId Pointer to material object or material identifier
     * @return array Operation result data
     */
    public function __async_publish($materialId)
    {
        /** @var Material $material SamsonCMS Material object */
        $material = null;
        /** @var array $result Asynchronous controller result */
        $result = array('status' => false);

        // Get material safely
        if (dbQuery('\samson\cms\Material')->id($materialId)->first($material)) {
            // Toggle material published status
            $material->Published = $material->Published ? 0 : 1;

            // Save changes to DB
            $material->save();

            // Действие не выполнено
            $result['status'] = true;
        } else { // Return error array
            $result['message'] = 'Material "' . $materialId . '" not found';
        }
        // Return asynchronous result
        return $result;
    }

    /**
     * Delete material
     * @param mixed $materialId Pointer to material object or material identifier
     * @return array Operation result data
     */
    function __async_remove($materialId)
    {
        /** @var Material $material */
        $material = null;
        /** @var array $result Asynchronous controller result */
        $result = array('status' => false);

        // Get material safely
        if (dbQuery('\samson\cms\Material')->id($materialId)->first($material)) {
            // Mark material as deleted
            $material->Active = 0;

            // Save changes to DB
            $material->save();

            $result['status'] = true;
        } else {
            // Return error array
            $result['message'] = 'Material "' . $materialId . '" not found';
        }
        // Return asynchronous result
        return $result;
    }

    /**
     * Copy material
     *
     * @param mixed $materialId Pointer to material object or material identifier
     * @return array Operation result data
     */
    function __async_copy($materialId)
    {
        /** @var array $result Asynchronous controller result */
        $result = array('status' => false);
        /** @var \samson\cms\Material $material Material object to copy */
        $material = null;

        // Get material safely
        if (dbQuery('\samson\cms\Material')->id($materialId)->first($material)) {
            // Copy found material
            $material->copy();
            // Set success status
            $result['status'] = true;
        } else {  // Set error message
            $result['message'] = 'Material "' . $materialId . '" not found';
        }

        // Return asynchronous result
        return $result;
    }

    /** Output for main page */
    public function main()
    {
        $mainPageHTML = '';

        // Получим все материалы
        if (
        dbQuery('samson\cms\cmsmaterial')
            ->join('user')
            ->cond('Active', 1)
            ->cond('Draft', 0)
            ->order_by('Created', 'DESC')
            ->limit(5)
            ->exec($dbMaterials)) {


            // Render material rows
            $rowsHTML = '';
            foreach ($dbMaterials as $dbMaterial) {
                $rowsHTML .= $this->view('main/row')
                    ->set($dbMaterial, 'material')
                    ->set($dbMaterial->onetoone['_user'], 'user')
                    ->output();
            }

            for ($i = sizeof($dbMaterials); $i < 5; $i++) {
                $rowsHTML .= $this->view('main/row')->output();
            }

            // Render main template
            $mainPageHTML = $this->view('main/index')->set('rows', $rowsHTML)->output();
        }
        // Return material block HTML on main page
        return $mainPageHTML;
    }
}
