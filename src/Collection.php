<?php
/**
 * Created by PhpStorm.
 * User: egorov
 * Date: 23.12.2014
 * Time: 22:12
 */

namespace samsonos\cms\app\material;

/**
 * Collection of materials
 * @package samsonos\cms\app\material
 */
class Collection extends \samsonos\cms\collection\Generic
{
    /** @var array Collection of structure to get products */
    protected $structures = array();

    /** @var int Amount of tours at one page */
    protected $pageSize = 25;

    /** @var array Collection of inner db handlers */
    protected $innerDBHandlers = array();

    /** @var array Collection of outer db handlers */
    protected $outerDBHandlers = array();

    /** @var string Query resulting entity name */
    protected $entityName = '\samson\cms\Materoa;';

    /** @var string Empty view file */
    protected $emptyView = '';

    /** @var  \samson\pager\Pager Pagination */
    protected $pager;

    /**
     * Render products collection block
     * @param string $prefix Prefix for view variables
     * @param array $restricted Collection of ignored keys
     * @return array Collection key => value
     */
    public function toView($prefix = null, array $restricted = array())
    {
        return array(
            $prefix.'html' => $this->render(),
            $prefix.'pager' => $this->pager->toHTML()
        );
    }

    /**
     * Pager db request handler
     * @param \samson\activerecord\dbQuery $query
     */
    public function pagerDBHandler(&$query)
    {
        // Get only actual materials identifiers
        $materialIDs = dbQuery('materialfield')
            ->cond('Active', '1')
            ->cond('FieldID', Tour::F_ENDS)
            ->cond('numeric_value', time(), dbRelation::LOWER_EQ)
            ->group_by('MaterialID')
            ->fieldsNew('MaterialID');

        $query->cond('MaterialID', $materialIDs);

        // Create count request to count pagination
        $countQuery = clone $query;
        $this->pager->update($countQuery->count());

        // Set current page query limits
        $query->limit($this->pager->start, $this->pager->end);
    }


    /** Fill collection with data */
    public function fill()
    {
        // Perform CMS request to get tours
        if (CMS::getMaterialsByStructures(
            $this->structures,
            $this->collection,
            $this->entityName,
            $this->outerDBHandlers,
            array(),
            $this->innerDBHandlers
        )) {
            // Handle success result
        }

        return $this->collection;
    }

    /**
     * Constructor
     * @param \samson\core\IViewable $renderer View render object
     * @param int $page Current page number
     */
    public function __construct($renderer, $page = 1)
    {
        // Create pagination
        $this->pager = new Pager($page, $this->pageSize);

        // Add pager handler to outer db handlers collection
        $this->outerDBHandlers[] = array($this, 'pagerDBHandler');

        // Fill collection
        $this->collection = $this->fill();

        // Call parents
        parent::__construct($renderer);
    }
} 