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
            ->cond('structurematerial.Active', 1)
            ->join('structure')
            ->cond('structure.Active', 1)
            ->cond('structure.system', 0);
    }

    /**
     * Function to cut off related materials
     *
     * @param array $materialIds Array of material identifiers
     */
    public function parentIdInjection(array & $materialIds)
    {
        $this->query
            ->className('material')
            ->cond('MaterialID', $materialIds)
            ->cond('parent_id', 0)
            ->order_by('Modyfied', 'DESC')
            ->fieldsNew('MaterialID', $materialIds);
    }

    /** {@inheritdoc} */
    public function renderItem($item)
    {
        $structureHTML = '';
        foreach ($item->onetomany['_structure'] as $structure) {
            $structureHTML .= '<a class="inner" title="' . t('Перейти к материалам ЭСС', true) .
                '" href="' . url_build($this->renderer->id(), $structure->id) . '">' . $structure->Name . '</a>, ';
        }
        $structureHTML = substr($structureHTML, 0, strlen($structureHTML) - 2);
        return $this->renderer
            ->view($this->itemView)
            ->set($item, 'item')
            ->set($item->onetoone['_user'], 'user')
            ->set('structures', $structureHTML)
            ->output();
    }

    /** {@inheritdoc} */
    public function __construct($renderer, $query = null, $pager = null)
    {
        $query = isset($query) ? $query : new dbQuery();
        $pager = isset($pager) ? $pager : new Pager();

        $this->entityHandler(array($this, 'joinTables'));
        $this->handler(array($this, 'parentIdInjection'));

        // Call parents
        parent::__construct($renderer, $query, $pager);
    }
}
