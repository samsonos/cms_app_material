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

    /** @var string Generic material entity form class */
    protected $formClassName = '\samsoncms\app\material\form\Form';

    /** Module initialization */
    public function init(array $params = array())
    {
        // Subscribe to input change event
        \samsonphp\event\Event::subscribe('samson.cms.input.change', array($this, 'inputUpdateHandler'));
    }

    /**
     * Input field saving handler
     * @param \samsonframework\orm\Record $object
     * @param string $param Field
     * @param string $previousValue Previous object field value
     * @param string $response Response
     */
    public function inputUpdateHandler(& $object, $param, $previousValue, $response = null)
    {
        // If current object is material and we change parameter Name, then change objects Url too if it is empty
        if ($object instanceof \samson\activerecord\material) {
            if ($param == 'Name' && $object->Url == '') {
                $object->Url = utf8_translit($object->Name);
            }
        }
    }
    
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
     * New material entity creation controller action
     * @param int $navigation Parent navigation identifier
     */
    public function __new($navigation = null)
    {
        // Create new entity
        $entity = new \samson\activerecord\material();
        $entity->Active = 1;
        $entity->Created = date('Y-m-d H:m:s');

        // Set user
        $user = m('social')->user();
        $entity->UserID = $user->user_id;

        // Persist
        $entity->save();

        // Set navigation relation
        if (isset($navigation)) {
            // Create relation with structure
            $structureMaterial = new \samson\activerecord\structurematerial();
            $structureMaterial->MaterialID = $entity->id;
            $structureMaterial->StructureID = $navigation;
            $structureMaterial->Active = '1';
            $structureMaterial->save();
        }

        // Go to correct form URL
        url()->redirect($this->id . '/form/' . $entity->id);
    }

    /**
     * Delete structure from entity
     * @param int $navigation Parent navigation identifier
     */
    public function __removenav($materialId = null, $navigation = null)
    {
        $structureMaterials = dbQuery('structurematerial')->cond('MaterialID', $materialId)->cond('StructureID', $navigation)->first();
        $structureMaterials->delete();
    }

    /**
     * Add new structure to entity
     * @param int $navigation Parent navigation identifier
     */
    public function __addnav($materialId = null, $navigation = null)
    {
        // Save record
        $sm = new CMSNavMaterial(false);
        $sm->MaterialID = $materialId;
        $sm->StructureID = $navigation;
        $sm->Active = '1';
        $sm->save();
    }

    /** @inheritdoc */
    public function __form($identifier)
    {
        // Call asynchronous controller action
        $response = $this->__async_form($identifier);

        // If we have successfully completed asynchronous action
        if ($response['status']) {
            $this->view('form/index2')
                ->set($response['entity'], 'entity')    // Pass entity object to view
                ->set('formContent', $response['form']) // Pass rendered form to view
                ;

            m('local')->title(t('Редактирование', true).' #'.$identifier.' - '.$this->description);

            return true;
        }

        // Controller action have failed
        return A_FAILED;
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
     * Asynchronous material entity form rendering action
     * @param int $identifier Material entity identifier
     * @return array Asynchronous response array
     */
    public function __async_form($identifier = null)
    {
        $result = array('status' => false);

        // Try to find entity
        $entity = null;
        if ($this->findAsyncEntityByID($identifier, $entity, $result)) { // Try to find
            // Build form for entity
            $form = new $this->formClassName($this, $this->query->className($this->entity), $entity);
            //elapsed('rendering form');
            // Render form
            $result['form'] = $form->render();
            $result['entity'] = $entity;
        }

        return $result;
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
        if ($this->findAsyncEntityByID($materialId, $material, $result)) {
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
    public function __async_remove($materialId)
    {
        /** @var Entity $material */
        $material = null;
        /** @var array $result Asynchronous controller result */
        $result = array('status' => false);

        // Call default async action
        if ($this->findAsyncEntityByID($materialId, $material, $result)) {
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
        if ($this->findAsyncEntityByID($materialId, $material, $result)) {
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
        $dbMaterials = array();

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
}
