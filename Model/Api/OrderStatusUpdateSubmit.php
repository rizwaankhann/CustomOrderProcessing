<?php
declare(strict_types=1);

namespace SmartWorking\CustomOrderProcessing\Model\Api;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use SmartWorking\CustomOrderProcessing\Api\OrderStatusUpdateSubmitInterface;

class OrderStatusUpdateSubmit implements OrderStatusUpdateSubmitInterface
{
    const MESSAGE = 'message';
    const STATUS = 'status';
    const PATH_STORE_EMAIL_FOR_MORE_INFORMATION = "This functionality is disabled, Please contact us.";
    const XML_PATH_CUSTOM_ORDER_STATUS_UPDATE_ENABLE = 'smartworking_general_config/general/enable';
    private ScopeConfigInterface $scopeConfigInterface;
    private OrderRepositoryInterface $orderRepository;

    /**
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        ScopeConfigInterface $scopeConfigInterface,
        OrderRepositoryInterface $orderRepository,
    ) {
        $this->scopeConfigInterface = $scopeConfigInterface;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param mixed $data
     * @return array[]
     */
    public function updateOrderStatus(mixed $data): array
    {
        $response = [self::STATUS => false, "message" => ""];
        try {
            $orderId = trim($data['order_increment_id']) ?? null;
            $newOrderStatus = trim($data['new_order_status']) ?? null;
            $status = $this->scopeConfigInterface->getValue(self::XML_PATH_CUSTOM_ORDER_STATUS_UPDATE_ENABLE,
                ScopeInterface::SCOPE_STORE);
            if ($status) {
                $order = $this->orderRepository->get($orderId);
                $newState = $this->getStateForCurrentOrderStatus($order, $newOrderStatus);

                $currentStatus = $order->getStatus();
                if (strtolower($newOrderStatus) === $currentStatus) {
                    $response[self::STATUS] = false;
                    $response[self::MESSAGE] = __("Current Order status and new order status are same, Please change the status.");
                    return [$response];
                }
                // more order status transition validation I was checking in core magento but due to time limit this can be future enhancement,

                if ($orderId && $newOrderStatus) {
                    $order->setState($newState)->setStatus($newOrderStatus);
                    $this->orderRepository->save($order);
                    $response[self::STATUS] = true;
                    $response[self::MESSAGE] = __("Order Status Updated Successfully");
                } else {
                    $response[self::STATUS] = false;
                    $response[self::MESSAGE] = __(self::PATH_STORE_EMAIL_FOR_MORE_INFORMATION);
                }
            } else {
                $response[self::STATUS] = false;
                $response[self::MESSAGE] = __(self::PATH_STORE_EMAIL_FOR_MORE_INFORMATION);
            }
        } catch (NoSuchEntityException $e) {
            $response[self::STATUS] = false;
            $response[self::MESSAGE] = __('Order does not exist with order Id %1.', $orderId);
        } catch (Exception $exception) {
            $response[self::STATUS] = false;
            $response[self::MESSAGE] = $exception->getMessage();
        }
        return [$response];
    }

    /**
     *
     * @param $order
     * @param string $status
     * @return string
     */
    private function getStateForCurrentOrderStatus($order, string $status): string
    {
        $states = $order->getConfig()->getStates();
        foreach ($states as $state => $label) {
            $statuses = $order->getConfig()->getStateStatuses($state);
            if (in_array($status, $statuses)) {
                return $state;
            }
        }
        return $order->getState();
    }

}
