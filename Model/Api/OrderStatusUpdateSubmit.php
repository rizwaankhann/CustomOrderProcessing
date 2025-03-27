<?php
declare(strict_types=1);

namespace SmartWorking\CustomOrderProcessing\Model\Api;

use Exception;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use SmartWorking\CustomOrderProcessing\Api\OrderStatusUpdateSubmitInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;

class OrderStatusUpdateSubmit implements OrderStatusUpdateSubmitInterface
{
    const MESSAGE = 'message';
    const STATUS = 'status';
    const PATH_STORE_EMAIL_FOR_MORE_INFORMATION = "This functionality is disabled, Please contact us.";
    const XML_PATH_CUSTOM_ORDER_STATUS_UPDATE_ENABLE = 'smartworking_general_config/general/enable';
    const XML_PATH_CUSTOM_ORDER_STATUS_CHANGE_LIFETIME = 'smartworking_general_config/general/change_lifetime';
    private ScopeConfigInterface $scopeConfigInterface;
    private OrderRepositoryInterface $orderRepository;
    private LoggerInterface $logger;
    private CacheInterface $cache;
    private RemoteAddress $remoteAddress;

    /**
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     * @param CacheInterface $cache
     * @param RemoteAddress $remoteAddress
     */
    public function __construct(
        ScopeConfigInterface $scopeConfigInterface,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger,
        CacheInterface $cache,
        RemoteAddress $remoteAddress
    ) {
        $this->scopeConfigInterface = $scopeConfigInterface;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->cache = $cache;
        $this->remoteAddress = $remoteAddress;
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
                if(!$order){
                    throw new LocalizedException(__('Order does not exist.'));
                }
                $newState = $this->getStateForCurrentOrderStatus($order, $newOrderStatus);
                // this will prevent the order status update restriction under 30 seconds and this value is configurable in store configuration.
                // added storeId and ip address also in cacheKey for better cache management based on user
                $orderStatusChangeLifetime = $this->scopeConfigInterface->getValue(self::XML_PATH_CUSTOM_ORDER_STATUS_CHANGE_LIFETIME,
                    ScopeInterface::SCOPE_STORE);
                $storeId = $order->getStoreId();
                $ipAddress = $this->remoteAddress->getRemoteAddress();
                $cacheKey = 'api_order_status_change_' . $orderId.'_'.$storeId.'_'.$ipAddress;
                if ($this->cache->load($cacheKey)) {
                    throw new LocalizedException(__('We have received too many requests for this order for change status. Please wait for sometime.'));
                }
                $this->cache->save('1', $cacheKey, [], $orderStatusChangeLifetime);
                // check if current order status and new order status is same
                $currentStatus = $order->getStatus();
                if (strtolower($newOrderStatus) === $currentStatus) {
                    throw new InputException(__('Current Order status and new order status are same, Please modify the status'));
                }
                // check if order id proper or not
                if (!is_numeric($orderId) || $orderId <= 0) {
                    throw new InputException(__('Invalid order ID format, please provide valid order id'));
                }
                // check if order is completed or cancelled then restrict the order status change
                $restrictedStates = ['complete', 'canceled'];
                if (in_array($order->getStatus(), $restrictedStates, true)) {
                    throw new LocalizedException(__('Status of a completed or canceled order is not allowed to be changed.'));
                }
                // check if order is on hold and block status change
                if (!$order->canHold()) {
                    throw new LocalizedException(__('Order is currently on hold, status change not allowed.'));
                }
                // check if status is complete and payment is still due
                if ($status === 'complete' && $order->getTotalDue() > 0) {
                    throw new LocalizedException(__('Order cannot be complete. Payment is still due.'));
                }
                // check if no order shipment is generated and new status is shipped
                if ($status === 'shipped' && !$order->hasShipments()) {
                    throw new LocalizedException(__('Order cannot mark as shipped until shipment is generated.'));
                }

                if ($orderId && $newOrderStatus) {
                    $order->setState($newState)->setStatus($newOrderStatus);
                    $this->orderRepository->save($order);
                    $response[self::STATUS] = true;
                    $response[self::MESSAGE] = __("Order Status Updated Successfully");
                } else {
                    $response[self::STATUS] = false;
                    $response[self::MESSAGE] = __('Please provide valid orderId and order status.');
                }
            } else {
                $response[self::STATUS] = false;
                $response[self::MESSAGE] = __(self::PATH_STORE_EMAIL_FOR_MORE_INFORMATION);
            }
        } catch (NoSuchEntityException $e) {
            $response[self::STATUS] = false;
            $response[self::MESSAGE] = __('Order does not exist with order Id %1.', $orderId);
            $this->logger->error('order does not exist with order Id ' . $orderId);
        } catch (Exception $exception) {
            $response[self::STATUS] = false;
            $response[self::MESSAGE] = $exception->getMessage();
            $this->logger->error('Exception thrown in V1/customUpdateOrderStatus API, ' . $exception->getMessage());
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
