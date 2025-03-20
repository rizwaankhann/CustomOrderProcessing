<?php
declare(strict_types=1);

namespace SmartWorking\CustomOrderProcessing\Model\ResourceModel\CustomOrderProcessing;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use SmartWorking\CustomOrderProcessing\Model\CustomOrderProcessing as Model;
use SmartWorking\CustomOrderProcessing\Model\ResourceModel\CustomOrderProcessing as ResourceModel;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'custom_order_processing_logger_collection';

    /**
     * Initialize collection model.
     */
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
