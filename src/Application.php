<?php
namespace samsoncms\app\material;

use samson\activerecord\dbQuery;
use samson\activerecord\dbRelation;
use samson\cms\CMSNavMaterial;
use samson\pager\Pager;
use samson\cms\Material;

/**
 * SamsonCMS generic material application.
 *
 * This application covers all actions that can be done
 * with materials and related entities in SamsonCMS.
 *
 * @package samson\cms\web\material
 */
class Application extends \samsoncms\Application
{
    /** View materials table prefix */
    const VIEW_TABLE_NAME = 'collection';

    /** Application name */
    public $name = 'Материалы';

    /** Application description */
    public $description = 'Материалы';

    /** Identifier */
    protected $id = 'material';

    /** Collection page size */
    protected $pageSize = 15;

    /** Controllers */

    /**
     * Universal controller action.Entity collection rendering.
     *
     * @param string $navigationId Navigation filter
     * @param string $search Search filter
     * @param int $page Current page
     */
    public function __handler($navigationId = '0', $search = '', $page = 1)
    {
        // Pass all parameters to parent handler with default values
        parent::__handler($navigationId, $search, $page);
    }

    /**
     * Generic material form controller
     *
     * @param int|null $materialId Editing material identifier if not null.
     * If null is passed material creation form will be displayed
     * @param int|null $navigation Structure material belongs to.
     */
    public function __edit2($materialId = null, $navigation = null)
    {
        // Get form data
        $form = $this->__async_edit2($materialId, $navigation);

        // Render form
        $this->html($form['form']);
    }

    /** Main logic */

    /**
     * Asynchronous controller for form rendering
     *
     * @param int|null $materialId Material identifier to build form.
     * @param int|null $navigation WHY???
     * @return array Asynchronous controller result
     */
    public function __async_edit2($materialId = null, $navigation = null)
    {
        // If this is form for a new material with structure relation
        if ($materialId === 0 && isset($navigation)) {
            // Create new material db record
            $material = db()->entity('\samson\cms\CMSMaterial');
            $material->Active = 1;
            $material->Created = date('Y-m-d H:m:s');

            $user = m('social')->user();
            $material->UserID = $user->user_id;
            $material->save();

            // Set new material as current
            $materialId = $material->id;

            // Convert parent CMSNavigation to an array
            $navigationArray = !is_array($navigation) ? array($navigation) : $navigation;

            // Fill parent CMSNavigation relations for material
            foreach ($navigationArray as $navigation) {
                // Create relation with structure
                $structureMaterial = new \samson\activerecord\structurematerial();
                $structureMaterial->MaterialID = $material->id;
                $structureMaterial->StructureID = $navigation;
                $structureMaterial->Active = '1';
                $structureMaterial->save();
            }
        }

        // Create form object
        $form = new Form($materialId, $navigation);

        // Success
        return array(
            'status' => true,
            'form' => $form->render(),
            'url' => $this->id . '/form/' . $materialId
        );
    }

    /** Asynchronous controller for material save */
    public function __async_save()
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
                $material->Active = '1';
            }

            // Set material modification date
            $material->Modyfied = date('Y-m-d H:m:s');
            // Make it not draft
            $material->Draft = 0;

            // Try to find changed information
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

            $structureMaterials = dbQuery('structurematerial')->cond('MaterialID', $material->id)->exec();
            /** @var \samson\activerecord\structurematerial $structureMaterial Clear existing
             * relations between material and structures */
            foreach ($structureMaterials as $structureMaterial) {
                $structureMaterial->delete();
            }

            // Iterate relations between material and structures
            if (isset($_POST['StructureID'])) {
                foreach ($_POST['StructureID'] as $structureId) {
                    // Save record
                    $sm = new CMSNavMaterial(false);
                    $sm->MaterialID = $material->id;
                    $sm->StructureID = $structureId;
                    $sm->Active = '1';
                    $sm->save();
                }
            }

            // Success
            $result['status'] = true;
            $result[] = $this->__async_edit2($material->id);
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
    public function __async_collection($navigationId = '0', $search = '', $page = 1)
    {
        // Save pager size in session
        if (isset($_GET['pagerSize'])) {
            $_SESSION['pagerSize'] = $_GET['pagerSize'];
            // delete get parameter from pager links
            unset($_GET['pagerSize']);
        }
        // Set filtration info
        $navigationId = isset($navigationId) ? $navigationId : '0';
        $search = !empty($search) ? $search : 0;
        $page = isset($page) ? $page : 1;

        // Create pager for material collection
        $pager = new Pager(
            $page,
            isset($_SESSION['pagerSize']) ? $_SESSION['pagerSize'] : $this->pageSize,
            $this->id . '/' . self::VIEW_TABLE_NAME . '/' . $navigationId . '/' . $search
        );

        // Create material collection
        $collection = new $this->collectionClass($this, new dbQuery(), $pager);

        // Add navigation filter
        if (isset($navigationId) && !empty($navigationId)) {
            $collection = $collection->navigation(array($navigationId));
        }

        return array_merge(
            array('status' => 1),
            $collection
                ->search($search)
                ->fill()
                ->toView(self::VIEW_TABLE_NAME . '_')
        );
    }

    /**
     * Perform material search by identifier and fulfill asynchronous
     * response array.
     * @param int|string $materialId Material entity identifier
     * @param mixed $material Found material return value
     * @param array $result Fulfilled asynchronous response array retrun value
     * @return bool True if material has been found
     */
    public function asyncMaterialAction($materialId, & $material = null, & $result = array())
    {
        /** @var array $result Asynchronous controller result */
        $result = array('status' => false);

        // Get material safely
        if (Entity::byId(dbQuery('\samson\cms\Material'), $materialId, $material)) {
            $result['status'] = true;
            return true;
        } else { // Return error array
            $result['message'] = 'Material "' . $materialId . '" not found';
            // Return asynchronous result
            return false;
        }
    }

    /**
     * Publish/Unpublish material
     *
     * @param mixed $materialId Pointer to material object or material identifier
     * @return array Operation result data
     */
    public function __async_publish($materialId)
    {
        /** @var array $result Asynchronous controller result */
        $result = array('status' => false);
        /** @var Entity $material SamsonCMS Material object */
        $material = null;

        // Call default async action
        if ($this->asyncMaterialAction($materialId, $material, $result)) {
            // Toggle material published status
            $material->Published = $material->Published ? 0 : 1;

            // Save changes to DB
            $material->save();
        }

        // Return asynchronous result
        return $result;
    }

    /**
     * Delete material
     *
     * @param mixed $materialId Pointer to material object or material identifier
     * @return array Operation result data
     */
    public function __async_remove2($materialId)
    {
        /** @var Entity $material */
        $material = null;
        /** @var array $result Asynchronous controller result */
        $result = array('status' => false);

        // Call default async action
        if ($this->asyncMaterialAction($materialId, $material, $result)) {
            // Mark material as deleted
            $material->Active = 0;

            // Save changes to DB
            $material->save();
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
    public function __async_copy($materialId)
    {
        /** @var array $result Asynchronous controller result */
        $result = array('status' => false);
        /** @var Entity $material Material object to copy */
        $material = null;

        // Call default async action
        if ($this->asyncMaterialAction($materialId, $material, $result)) {
            // Copy found material
            $material->copy();
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
            ->cond('Name', "", dbRelation::NOT_EQUAL)
            ->limit(5)
            ->exec($dbMaterials)
        ) {


            // Render material rows
            $rowsHTML = '';
            foreach ($dbMaterials as $dbMaterial) {
                $rowsHTML .= $this->view('main/row')
                    ->set($dbMaterial, 'material')
                    ->set(isset($dbMaterial->onetoone['_user']) ? $dbMaterial->onetoone['_user'] : array(), 'user')
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

    /** @deprecated Use  __async_collection(), will be removed soon */
    public function __async_table($navigationId = '0', $search = '', $page = 1)
    {
        return $this->__async_collection($navigationId, $search, $page);
    }

    /** @deprecated Use  __async_remove2(), will be removed soon */
    public function __async_remove($materialId = null, $navigation = null)
    {
        return $this->__async_remove2($materialId, $navigation);
    }

    /** @deprecated Use  __async_edit2(), will be removed soon */
    public function __async_form($materialId = null, $navigation = null)
    {
        return $this->__async_edit2($materialId, $navigation);
    }
}
