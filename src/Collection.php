<?php
/**
 * Created by PhpStorm.
 * User: egorov
 * Date: 23.12.2014
 * Time: 22:12
 */
namespace samsoncms\app\material;

use samson\activerecord\dbQuery;
use samson\pager\Pager;
use samsonframework\orm\QueryInterface;
use samsonos\cms\collection\Paged;

/**
 * Collection of materials
 * @package samsonos\cms\app\material
 */
class Collection extends Paged
{
    public $indexView = 'www/list/index';
    public $itemView = 'www/list/item/index';
    public $emptyView = 'www/list/item/empty';
    public $entityName = 'samson\activerecord\material';

    /**
     * Function for joining tables to get some extra data in result set
     *
     * @param dbQuery $query Collection query
     */
    public function joinTables(dbQuery $query)
    {
        $query->join('user')
            ->order_by('Modyfied', 'DESC')
            ->join('structurematerial')
            ->join('structure')
            ->cond('structure.system', 0)
        ;
    }

    /**
     * Function to cut off related and table materials
     *
     * @param array $materialIds Array of material identifiers
     */
    public function parentIdInjection(array & $materialIds)
    {
        // Cut off related and table materials
        $this->query
            ->className('material')
            ->cond('MaterialID', $materialIds)
            ->cond('parent_id', 0)
            ->cond('Active', 1)
            ->order_by('Modyfied', 'DESC')
            ->fieldsNew('MaterialID', $materialIds);
    }

    /** {@inheritdoc} */
    public function renderItem($item)
    {
        /** @var string $structureHTML HTML representation of material structures */
        $structureHTML = '';
        /** @var string $search Search string */
        $search = empty($this->search) ? '0' : $this->search[0];
        /** @var int|string $navigation Filter navigation identifier */
        $navigation = (count($this->navigation) == 1 && count($this->navigation[0]) == 1)
            ? $this->navigation[0][0] : '0';

        /** @var \samson\activerecord\structure $structure Material structures list */
        foreach ($item->onetomany['_structure'] as $structure) {
            $structureHTML .= '<a class="inner" title="' . t('Перейти к материалам ЭСС', true) .
                '" href="' . url_build($this->renderer->id(), $structure->id) . '">' . $structure->Name . '</a> ';
        }

        // Return item HTML
        return $this->renderer
            ->view($this->itemView)
            ->set($item, 'item')
            ->set($item->onetoone['_user'], 'user')
            ->set('structures', $structureHTML)
            ->set('currentPage', $this->pager->current_page)
            ->set('search', $search)
            ->set('navigation', $navigation)
            ->output();
    }

    /** {@inheritdoc} */
    public function __construct($renderer, $query = null, $pager = null)
    {
        // Create query and pager instances by default
        $query = isset($query) ? $query : new dbQuery();
        $pager = isset($pager) ? $pager : new Pager();

        // Call external handlers
        $this->entityHandler(array($this, 'joinTables'));
        $this->handler(array($this, 'parentIdInjection'));

        // Call parents
        parent::__construct($renderer, $query, $pager);
    }
}
