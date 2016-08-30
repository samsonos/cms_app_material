<?php
namespace samsoncms\app\material;

use samson\activerecord\dbQuery;
use samson\activerecord\dbRelation;
use samson\pager\Pager;
use samsoncms\api\Material;
use samsoncms\api\NavigationMaterial;
use samsonframework\orm\ArgumentInterface;
use samsonphp\event\Event;

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
    public $name = 'Материал';

    /** Application description */
    public $description = 'Материал';

    /** Identifier */
    protected $id = 'material';

    /** Collection page size */
    protected $pageSize = 15;

    /** @var string Generic material entity form class */
    protected $formClassName = '\samsoncms\app\material\form\Form';

    /** @var array Entity related navigation identifiers */
    public static $structures = array();

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
    public function inputUpdateHandler(&$object, $param, $previousValue, &$response)
    {
        // If current object is material and we change parameter Name, then change objects Url too if it is empty
        if ($object instanceof \samson\activerecord\material) {
            if ($param == 'Name' && $object->Url == '') {
                $object->Url = utf8_translit($object->Name);
            } elseif ($param == 'Url') {
                if ($this->query->entity(\samson\activerecord\material::class)->where('Url', $object->Url)->where('MaterialID', $object->MaterialID, ArgumentInterface::NOT_EQUAL)->first($material)) {
                    $object->Url = $previousValue;
                    $response['urlError'] = '<a target="_blank" href="'.url()->build($this->id.'/form/'.$material->id).'">'.t('Материал', true).'</a> '.t('с таким параметром уже существует', true);
                }
            }
        }
    }

    public function __async_generateurl($id)
    {
        $response = array('status' => 1);
        /** @var \samson\activerecord\material $material */
        $material = null;
        if ($this->query->entity('\samson\activerecord\material')->where('MaterialID', $id)->first($material)) {
            if ($this->query->entity('\samson\activerecord\material')->where('Url', $material->Url)->where('MaterialID', $material->MaterialID, ArgumentInterface::NOT_EQUAL)->first($existedMaterial)) {
                $response['urlError'] = '<a target="_blank" href="'.url()->build($this->id.'/form/'.$existedMaterial->id).'">'.t('Материал', true).'</a> '.t('с таким параметром уже существует', true);
            } else {
                $material->Url = utf8_translit($material->Name);
                $material->save();
                $response['createdUrl'] = $material->Url;
            }
        }
        return $response;
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
     * Controller to rendering specific collection
     *
     * @param string $navigationId Navigation filter
     * @param string $search Search filter
     * @param int $page Current page
     */
    public function __collection($navigationId = '0', $search = '', $page = 1)
    {
        // Pass all parameters to parent handler with default values
        parent::__handler($navigationId, $search, $page);
    }

    /**
     * New material entity creation controller action
     * @param int $navigation Parent navigation identifier
     */
    public function __new($navigation = array())
    {
        // Create new entity
        $entity = new Material();
        $entity->Active = 1;
        $entity->Created = date('Y-m-d H:m:s');

        // Set user
        $user = $this->system->module('social')->user();

        if (isset($user->user_id)) {
            $entity->UserID = $user->userId;
        }
        else {
            $entity->UserID = '1';
        }

        // Persist
        $entity->save();

        // Set name for created material
        $entity->Name = t($this->name, true).' №'.$entity->id;
        $entity->Url = utf8_translit($entity->Name);

        // Check unique url for material
        if ($this->query->entity(Material::class)->where(Material::F_IDENTIFIER, utf8_translit($entity->Name))->first()) {
            $entity->Url = md5(utf8_translit($entity->Name));
        }

        // Persist
        $entity->save();

        Event::fire('samsoncms.app.material.new', array(&$entity));

        $navigation = is_array($navigation) ? $navigation : array($navigation);

        // Set navigation relation
        foreach (array_merge($navigation, static::$structures) as $structureID) {
            // Create relation with structure
            $structureMaterial = new NavigationMaterial();
            $structureMaterial->MaterialID = $entity->id;
            $structureMaterial->StructureID = $structureID;
            $structureMaterial->Active = '1';
            $structureMaterial->save();
        }

        // Go to correct form URL
        url()->redirect($this->system->module('cms')->baseUrl . '/' . $this->id . '/form/' . $entity->id);
    }

    /**
     * Delete structure from entity
     * @param int $navigation Parent navigation identifier
     */
    public function __async_removenav($materialId = null, $navigation = null)
    {
        $structureMaterials = dbQuery('structurematerial')->cond('MaterialID', $materialId)->cond('StructureID', $navigation)->first();
        $structureMaterials->delete();
    }

    /**
     * Add new structure to entity
     * @param int $navigation Parent navigation identifier
     */
    public function __async_addnav($materialId = null, $navigation = null)
    {
        // Save record
//        $sm = new CMSNavMaterial(false);
        $sm = new NavigationMaterial();
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

            $activeButton = '';
            if (isset($response['activeButton'])) {
                $activeButton = $response['activeButton'];
            }

            // Set title and another params
            $this->title(t('Редактирование', true).' #'.$identifier.' - '.$this->description)
                ->view('form/index2')
                ->set($response['entity'], 'entity')    // Pass entity object to view
                ->set($response['form'], 'formContent') // Pass rendered form to view
                ->set($activeButton, 'activeButton')
            ;

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
    public function __async_collection($navigationId = '0', $search = '0', $page = 1)
    {
        // Save pager size in session
        if (isset($_GET['pagerSize'])) {
            $_SESSION['pagerSize'] = str_replace('/', '', $_GET['pagerSize']);
            // delete get parameter from pager links
            unset($_GET['pagerSize']);
        }

        // Save search filter
        if (isset($_GET['search'])) {
            $_SESSION['search'] = str_replace('/', '', $_GET['search']);
            $search = str_replace('/', '', $_GET['search']);
            unset($_GET['search']);
        }

        // Set filtration info
        $navigationId = isset($navigationId) ? $navigationId : '0';
        $search = !empty($search) ? urldecode($search) : 0;
        $page = isset($page) ? $page : 1;


        // Create pager for material collection
        $pager = new Pager(
            $page,
            isset($_SESSION['pagerSize']) ? $_SESSION['pagerSize'] : $this->pageSize, $this->id . '/' . self::VIEW_TABLE_NAME . '/' . $navigationId . '/' . $search
        );

        // Create material collection
        $collection = new $this->collectionClass($this, new dbQuery(), $pager);

        // Add navigation filter
        if (isset($navigationId) && !empty($navigationId)) {
            $collection = $collection->navigation(array($navigationId));
        }

        return array_merge(
            array(
                'status' => true,
                'navigationId' => $navigationId,
                'searchQuery' => $search,
                'pageNumber' => $page,
                'rowsCount' => $collection->search($search)->fill()->getSize()
            ),
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
            if (isset($form->tabs[0]->activeButton)) {
                $result['activeButton'] = $form->tabs[0]->activeButton;
            }
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
        if ($this->findAsyncEntityByID($materialId, $material, $result, '\samsoncms\api\Material')) {
            // Mark material as deleted
            $material->remove();
            return $this->__async_collection();
        }

        // Return asynchronous result
        return $result;
    }

    /**
     * Delete material
     *
     * @param mixed $materialId Pointer to material object or material identifier
     * @return array Operation result data
     * @deprecated use __async_remove
     */
    public function __async_removeentity($materialId)
    {
        return $this->__async_remove($materialId);
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

    /**
     * Get current materials in csv format
     * @param Int $structureId if not set navigation id on application
     * then use default collection with passed id of structure
     */
    public function __tocsv($structureId = null){

        // Create pager for material collection
        $pager = new Pager(0);

        // Get collection
        $collection = new $this->collectionClass($this, new dbQuery(), $pager);

        // Set navigation
        if (isset(static::$navigation)) {
            $collection = $collection->navigation(static::$navigation);
        } else {
            $collection = $collection->navigation(array($structureId));
        }
        $collection->fill();

        $this->tocsv($collection);
    }

    /**
     * Controller to output csv file of all materials for structure
     * @var $structure
     * @var string $delimiter Export file delimiter
     * @var string $fileName Export file name
     */
    public function tocsv($collection, $fileName = null, $delimiter = ';')
    {
        s()->async(true);

        ini_set('memory_limit', '2048M');

        // Set passed file name or generate it
        $fileName = $fileName ?: 'Export' . date('dmY') . '.csv';

        // Output file from browser
        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $fileName);
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');

        // Write to php temp because php natively support csv files creation
        $handle = fopen('php://temp', 'r+');
        //fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
        foreach ($collection->toArray() as $line) {
            fputcsv($handle, $line, $delimiter);
        }

        // Read file from temp
        rewind($handle);
        $csv = '';
        while (!feof($handle)) {
            $csv .= fread($handle, 8192);
        }
        fclose($handle);

        // Convert to Excel readable format
        echo mb_convert_encoding($csv, 'Windows-1251', 'UTF-8');
    }

    /** Output for main page */
    public function main()
    {
        $mainPageHTML = '';
        $dbMaterials = array();

        // Получим все материалы
        if (
        dbQuery('samsoncms\api\Material')
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
            $mainPageHTML = $this->view('main/index')->set($rowsHTML, 'rows')->output();
        }

        // Return material block HTML on main page
        return $mainPageHTML;
    }
}
