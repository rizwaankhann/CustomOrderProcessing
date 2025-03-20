<?php
declare(strict_types=1);

namespace SmartWorking\CustomOrderProcessing\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class CustomOrderProcessing extends AbstractDb
{
    /**
     * @var string
     */
    protected string $_eventPrefix = 'custom_order_processing_logger_resource_model';

    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init('custom_order_processing_logger', 'id');
    }
}
