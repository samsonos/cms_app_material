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

    /**
     * @param dbQuery $query
     */
    public function joinTables(dbQuery $query)
    {
        $query->join('user')->join('structurematerial')->join('structure')->order_by('material.Modyfied', 'DESC');
    }

    /** {@inheritdoc} */
    public function renderItem($item)
    {
        $structureHTML = '';
        foreach ($item->onetomany['_structure'] as $structure) {
            if ($structure->system != 1) {
                $structureHTML .= '<a class="inner" title="' . t('Перейти к материалам ЭСС', true) .
                    '" href="' . url_build($this->renderer->id(), $structure->id) . '">' . $structure->Name . '</a>, ';
            }
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

        // Call parents
        parent::__construct($renderer, $query, $pager);
    }
}
