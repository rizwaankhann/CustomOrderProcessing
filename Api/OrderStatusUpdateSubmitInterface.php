<?php
declare(strict_types=1);

namespace SmartWorking\CustomOrderProcessing\Api;

/**
 * Interface OrderStatusUpdateSubmit
 * @package SmartWorking\CustomOrderProcessing\Api
 */
interface OrderStatusUpdateSubmitInterface
{
    /**
     * @param mixed $data
     * @return array[]
     */
    public function updateOrderStatus(mixed $data): array;

}
