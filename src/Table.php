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
    public $search_fields = array( 'Name', 'Url'  );

    /** Default table template file */
    public $table_tmpl = 'table/index';

    /** Default table row template */
    public $row_tmpl = 'table/row/index';

    /** Default table notfound row template */
    public $notfound_tmpl = 'table/row/notfound';

    /** Default table empty row template */
    public $empty_tmpl = 'table/row/empty';

    /**
     * Prepare DB query search condition by keywords
     * @param string $keywords Keywords condition to add to query
     */
    private function prepareSearchCondition($keywords)
    {
        // If keywords has chars
        if (isset($keywords{0})) {
            // Create condition group
            $scg = new Condition('or');

            // Iterate base material and nav fields to generate search conditions
            foreach ($this->search_fields as $item) {
                // If condition group is passed - add it to search condition
                if (is_a($item, \samson\core\Autoloader::className('Condition', 'samson\activerecord'))) {
                    $scg->arguments[] = $item;
                } else {// Create condition argument
                    $scg->arguments[] = new Argument($item, '%'.$keywords.'%', dbRelation::LIKE);
                }
            }

            // Add query condition
            $this->query->own_condition->arguments[] = $scg;
        }
    }

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

        // Generate pager url prefix
        $prefix = 'material/table/'.(isset($nav) ? $nav->id : '0').'/'.(isset($search{0}) ? $search : 'no-search').'/';

        // Create pager
        $this->pager = new \samson\pager\Pager($page, self::ROWS_COUNT, $prefix);

        // Create DB query object
        $this->query = dbQuery('material')
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
            $this->query->id($ids);
        }

        // Call parent constructor
        parent::__construct($this->query, $this->pager);
    }

    /** @see \samson\cms\table\Table::render() */
    public function render(array $rows = null, $module = null)
    {
        // If no rows is passed use generic rows
        if (!isset($rows)) {
            // If search filter is set - add search condition to query
            if (isset($this->search)) {
                $this->prepareSearchCondition($this->search);
            }

            //db()->debug();
            /** @var \samson\cms\Material[] $materials Get original materials */
            $materials = array();
            if ($this->query->exec($materials)) {

                // Get all materials with joined data
               $materials = dbQuery('\samson\cms\material')
                    ->id(array_keys($materials)) // Pass all material identifiers
                    ->join('user')
                    ->join('structurematerial')
                    ->join('samson\cms\Navigation')
                ->exec();

                // Generic rendering routine
                return parent::render($materials);
            } else { // Query failed
                // Render empty or not found row content
                $row = '';
                if (!isset($this->search{0})) {
                    $row = $this->emptyrow($this->query, $this->pager);
                } else { // Not found
                    $row = m()->output($this->notfound_tmpl);
                }

                // Manually render table
                return m()
                    ->view($this->table_tmpl)
                    ->set($this->pager)
                    ->rows($row)
                ->output();
            }

            //db()->debug(false);
        }

        // Perform table rendering
        return parent::render($rows);
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