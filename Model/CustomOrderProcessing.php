<?php
declare(strict_types=1);

namespace SmartWorking\CustomOrderProcessing\Model;

use Magento\Framework\Model\AbstractModel;
use SmartWorking\CustomOrderProcessing\Model\ResourceModel\CustomOrderProcessing as ResourceModel;

class CustomOrderProcessing extends AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'custom_order_processing_logger_model';

    /**
     * Initialize magento model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }
}
