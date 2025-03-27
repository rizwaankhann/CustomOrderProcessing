<?php
declare(strict_types=1);

namespace SmartWorking\CustomOrderProcessing\Test\Integration\Model\Api;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;

class OrderStatusUpdateSubmitTest extends WebapiAbstract
{
    private const SERVICE_NAME = 'SmartWorkingCustomOrderProcessingOrderStatusUpdateSubmitV1';
    private const RESOURCE_PATH = '/V1/customUpdateOrderStatus';
    /**
     * @var OrderRepositoryInterface
     */
    private mixed $orderRepository;
    /**
     * @var CacheInterface|mixed
     */
    private $cache;

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default/vendor/general/enable 1
     * @magentoConfigFixture default/vendor/general/change_lifetime 30
     */
    public function testSuccessfulOrderStatusUpdate()
    {
        $order = $this->getTestOrder();
        $newStatus = Order::STATE_PROCESSING;

        $response = $this->makeApiCall($order->getIncrementId(), $newStatus);

        // Verify API response
        $this->assertTrue($response[0]['status']);
        $this->assertStringContainsString('Successfully', $response[0]['message']);

        // Verify database state
        $updatedOrder = $this->orderRepository->get($order->getId());
        $this->assertEquals($newStatus, $updatedOrder->getStatus());
    }

    private function getTestOrder(): OrderInterface
    {
        return $this->orderRepository->getList(
            $this->searchCriteriaBuilder->create()
        )->getFirstItem();
    }

    private function makeApiCall(string $incrementId, string $newStatus): array
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => Request::HTTP_METHOD_POST
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'operation' => self::SERVICE_NAME . 'UpdateOrderStatus'
            ]
        ];

        return $this->_webApiCall($serviceInfo, [
            'order_increment_id' => $incrementId,
            'new_order_status' => $newStatus
        ]);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default/vendor/general/enable 0
     */
    public function testFeatureDisabledScenario()
    {
        $order = $this->getTestOrder();
        $response = $this->makeApiCall($order->getIncrementId(), Order::STATE_PROCESSING);

        $this->assertFalse($response[0]['status']);
        $this->assertStringContainsString('disabled', $response[0]['message']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default/vendor/general/enable 1
     */
    public function testCacheCooldownProtection()
    {
        $order = $this->getTestOrder();
        $cacheKey = 'api_order_status_change_' . $order->getIncrementId();

        // First request - this request should succeed
        $firstResponse = $this->makeApiCall($order->getIncrementId(), Order::STATE_PROCESSING);
        $this->assertTrue($firstResponse[0]['status']);

        // Second request - this request should fail
        $secondResponse = $this->makeApiCall($order->getIncrementId(), Order::STATE_PROCESSING);
        $this->assertFalse($secondResponse[0]['status']);
        $this->assertStringContainsString('too many requests', $secondResponse[0]['message']);
        $this->assertNotEmpty($this->cache->load($cacheKey));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default/vendor/general/enable 1
     */
    public function testSameStatusUpdateRejection()
    {
        $order = $this->getTestOrder();
        $currentStatus = $order->getStatus();

        $response = $this->makeApiCall($order->getIncrementId(), $currentStatus);

        $this->assertFalse($response[0]['status']);
        $this->assertStringContainsString('same', $response[0]['message']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_canceled.php
     * @magentoConfigFixture default/vendor/general/enable 1
     */
    public function testCanceledOrderProtection()
    {
        $order = $this->getTestOrder();
        $response = $this->makeApiCall($order->getIncrementId(), Order::STATE_PROCESSING);

        $this->assertFalse($response[0]['status']);
        $this->assertStringContainsString('canceled', $response[0]['message']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_on_hold.php
     * @magentoConfigFixture default/vendor/general/enable 1
     */
    public function testOnHoldOrderProtection()
    {
        $order = $this->getTestOrder();
        $response = $this->makeApiCall($order->getIncrementId(), Order::STATE_PROCESSING);

        $this->assertFalse($response[0]['status']);
        $this->assertStringContainsString('on hold', $response[0]['message']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_with_shipment.php
     * @magentoConfigFixture default/vendor/general/enable 1
     */
    public function testShippedStatusSucceedsWithShipment()
    {
        $order = $this->getTestOrder();

        $response = $this->makeApiCall(
            $order->getIncrementId(),
            'shipped'
        );

        // Assert API response
        $this->assertTrue($response[0]['status']);
        $this->assertStringContainsString('Successfully', $response[0]['message']);

        // Verify order status update
        $updatedOrder = $this->orderRepository->get($order->getId());
        $this->assertEquals('shipped', $updatedOrder->getStatus());
    }

    protected function setUp(): void
    {
        parent::setUp();
        $objectManager = Bootstrap::getObjectManager();
        $this->orderRepository = $objectManager->get(OrderRepositoryInterface::class);
        $this->cache = $objectManager->get(CacheInterface::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->cache->clean();
    }
}
