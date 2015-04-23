<?php
/**
 * Created by PhpStorm.
 * User: egorov
 * Date: 23.12.2014
 * Time: 22:12
 */
namespace samsoncms\app\material;

use samsoncms\app\user\field\Navigation;
use samsoncms\app\user\field\User;
use samsonframework\orm\QueryInterface;
use samsoncms\field\Generic;
use samsoncms\field\Control;

/**
 * Collection of materials
 * @package samsonos\cms\app\material
 */
class Collection2 extends \samsoncms\MetaCollection
{
    //public $indexView = 'www/list/index';
    //public $itemView = 'www/list/item/index';
    public $emptyView = 'www/list/item/empty';

    /**
     * Function for joining tables to get some extra data in result set
     *
     * @param dbQuery $query Collection query
     */
    public function joinTables(&$query)
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
    public function __construct($renderer, $query = null, $pager = null, $navigation = array())
    {
        // Call parents
        parent::__construct($renderer, $query, $pager);

        // Set navigation filter
        $this->navigation($navigation);

        // Call external handlers
        $this->entityHandler(array($this, 'joinTables'));
        $this->handler(array($this, 'parentIdInjection'));

        // Fill default column fields for collection
        $this->fields = array(
            new Generic('MaterialID', '#', 0, 'id', false),
            new Generic('Name', t('Наименование', true), 0),
            new Generic('Url', t('Идентификатор', true), 0),
            new Navigation(),
            new Generic('Modyfied', t('Последнее изменение', true), 7, 'modified', false),
            new User(),
            new Control(),
        );
    }
}
