<?php
/**
 * Created by PhpStorm.
 * User: onysko
 * Date: 27.05.2015
 * Time: 13:07
 */

namespace samsoncms\app\material\form\tab;


use samsoncms\form\field\Generic;
use samsonframework\core\RenderInterface;
use samsonframework\orm\QueryInterface;
use samsonframework\orm\Record;

class Entity extends \samsoncms\form\tab\Entity
{
    /** @inheritdoc */
    public function __construct(RenderInterface $renderer, QueryInterface $query, Record $entity)
    {
        $this->fields = array(
            new Generic('Name', t('Название', true), 0),
            new Generic('Url', t('Url', true), 0),
            new Generic('Published', t('Активен', true), 11),
        );

        // Call parent constructor to define all class fields
        parent::__construct($renderer, $query, $entity);
    }
}
