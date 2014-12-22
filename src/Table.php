<?php
namespace samson\cms\web\material;

use samson\activerecord\Argument;
use samson\activerecord\Condition;
use samson\activerecord\dbRelation;
use samson\activerecord\dbConditionGroup;
use samson\activerecord\dbConditionArgument;
use samson\cms\Navigation;
use samson\pager\pager;
use samson\activerecord\dbMySQLConnector;

/**
 * Class for dislaying and interactiong with SamsonCMS materials table
 * @author Egorov Vitaly <egorov@samsonos.com>
 */
class Table extends \samson\cms\table\Table
{
    /** Table rows count */
    const ROWS_COUNT = 15;

    /** Parent materials CMSNav */
    protected $nav;

    /** Current search keywords */
    protected $search;

    /** Array of drafts for current materials */
    protected $drafts = array();

    /** Array of drafts with out materials */
    protected $single_drafts = array();

    /** Search material fields */
    public $search_fields = array('Name', 'Url');

    /** Default table template file */
    public $table_tmpl = 'table/index';

    /** Default table row template */
    public $row_tmpl = 'table/row/index';

    /** Default table notfound row template */
    public $notfound_tmpl = 'table/row/notfound';

    /** Default table empty row template */
    public $empty_tmpl = 'table/row/empty';

    /**
     * Constructor
     * @param Navigation $nav 		Parent CMSNav to filter materials
     * @param string $search	Keywords to search in materials
     * @param string $page		Current table page number
     */
    public function __construct(Navigation & $nav = null, $search = null, $page = null)
    {
        // Save parent cmsnav
        $this->nav = & $nav;

        // Save search keywords
        $this->search = $search;

        $prefix = $this->setPagerPrefix();

        // Create pager
        $this->pager = new \samson\pager\Pager($page, self::ROWS_COUNT, $prefix);

        // Collection of filtered material identifiers
        $filteredIDs = array();

        // If search filter is set - add search condition to query
        if (isset($this->search{0}) && $this->search != '0') {
            // Create additional fields query
            $searchQuery = dbQuery('materialfield')->join('material');

            // Create or condition
            $searchCondition = new Condition('OR');

            // Iterate all possible material fields
            foreach (cms()->material_fields as $f) {
                // Create special condition for additional field
                $cg = new Condition('AND');
                $cg->add(new Argument('FieldID', $f->FieldID))
                    ->add(new Argument('Value', '%' . $search . '%', dbRelation::LIKE));

                // Add new condition to group
                $searchCondition->add($cg);
            }

            // Add all search conditions from material table
            foreach ($this->search_fields as $item) {
                $searchCondition->add(new Argument('material_'.$item, '%'.$search.'%', dbRelation::LIKE));
            }

            // Set condition
            $searchQuery->cond($searchCondition);

            // Get filtered identifiers
            $filteredIDs = $searchQuery->fieldsNew('MaterialID');
        }

        // Create DB query object
        $this->query = dbQuery('\samson\activerecord\material')
            ->cond('parent_id', 0)
            ->cond('Draft', 0)
            ->cond('Active', 1)
            ->own_order_by('Modyfied', 'DESC')
        ;

        // Perform query by structure-material and get material ids
        $ids = array();
        if (isset($nav) && dbQuery('samson\cms\CMSNavMaterial')
                ->cond('StructureID', $nav->id)
                ->cond('Active', 1)->fields('MaterialID', $ids)) {
            // Set corresponding material ids related to specified navigation
            if (sizeof($filteredIDs)) {
                $filteredIDs = array_intersect($filteredIDs, $ids);
            } else {
                $filteredIDs = $ids;
            }
        }

        // If we have filtration identifiers
        if (sizeof($filteredIDs)) {
            // Add the, to query
            $this->query->id($filteredIDs);
        }

        $this->queryHandler();

        // Call parent constructor
        parent::__construct($this->query, $this->pager);
    }

    public function beforeHandler() {
        $ids = $this->query->fieldsNew('MaterialID');

        $this->query = dbQuery('\samson\cms\material')
                        ->join('user')
                        ->join('structurematerial')
                        ->join('samson\cms\Navigation');

        if (sizeof($ids)) {
            $this->query->id($ids);
        } else {
            $this->query->id(0);
        }
    }

    public function queryHandler()
    {
        return $this->query;
    }

    public function setPagerPrefix()
    {
        // Generate pager url prefix
        return 'material/table/'.(isset($this->nav) ? $this->nav->id : '0').'/'.(isset($this->search{0}) ? $this->search : '0').'/';
    }

    /** @see \samson\cms\table\Table::row() */
    public function row(& $material, Pager & $pager = null)
    {
        // Set table row view context
        m()->view($this->row_tmpl);

        // If there is navigation for material - pass them
        if (isset($material->onetomany['_structure'])) {
            m()->navs($material->onetomany['_structure']);
        }

        // Render row template
        return m()
            ->cmsmaterial($material)
            ->user(isset($material->onetoone['_user']) ? $material->onetoone['_user'] : '')
            ->pager($this->pager)
            ->nav_id(isset($this->nav) ? $this->nav->id : '0')
            ->search(urlencode($this->search))
            ->output();
    }
}
