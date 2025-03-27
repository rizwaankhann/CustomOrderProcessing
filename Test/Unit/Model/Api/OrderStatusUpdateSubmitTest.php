<?php
declare(strict_types=1);

namespace SmartWorking\CustomOrderProcessing\Test\Unit\Model\Api;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Config as OrderConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SmartWorking\CustomOrderProcessing\Model\Api\OrderStatusUpdateSubmit;

class OrderStatusUpdateSubmitTest extends TestCase
{
    private OrderStatusUpdateSubmit $orderStatusUpdateSubmit;
    private ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject $scopeConfigMock;
    private OrderRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject $orderRepositoryMock;
    private \PHPUnit\Framework\MockObject\MockObject|CacheInterface $cacheMock;
    private LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $loggerMock;
    private Order|\PHPUnit\Framework\MockObject\MockObject $orderMock;

    public function testFeatureDisabled()
    {
        $this->scopeConfigMock->method('getValue')
            ->willReturn(false);

        $result = $this->orderStatusUpdateSubmit->updateOrderStatus([
            'order_increment_id' => '000000005',
            'new_order_status' => 'processing'
        ]);

        $this->assertFalse($result[0]['status']);
        $this->assertStringContainsString('disabled', (string)$result[0]['message']);
    }

    public function testInvalidOrderId()
    {
        $this->scopeConfigMock->method('getValue')
            ->willReturn(true);

        $result = $this->orderStatusUpdateSubmit->updateOrderStatus([
            'order_increment_id' => '00997405',
            'new_order_status' => 'processing'
        ]);

        $this->assertFalse($result[0]['status']);
        $this->assertStringContainsString('Order does not exist', (string)$result[0]['message']);
    }

    public function testSameStatusUpdate()
    {
        $this->scopeConfigMock->method('getValue')
            ->willReturn(true);

        $this->orderMock->method('getStatus')->willReturn('processing');
        $this->orderRepositoryMock->method('get')->willReturn($this->orderMock);

        $result = $this->orderStatusUpdateSubmit->updateOrderStatus([
            'order_increment_id' => '000000005',
            'new_order_status' => 'processing'
        ]);

        $this->assertFalse($result[0]['status']);
        $this->assertStringContainsString('order status are same', (string)$result[0]['message']);
    }

    public function testCompletedOrderStatusChange()
    {
        $this->scopeConfigMock->method('getValue')
            ->willReturn(true);

        $this->orderMock->method('getStatus')->willReturn('complete');
        $this->orderRepositoryMock->method('get')->willReturn($this->orderMock);

        $result = $this->orderStatusUpdateSubmit->updateOrderStatus([
            'order_increment_id' => '000000005',
            'new_order_status' => 'processing'
        ]);

        $this->assertFalse($result[0]['status']);
        $this->assertStringContainsString('completed or canceled order is not allowed', (string)$result[0]['message']);
    }

    public function testSuccessfulStatusUpdate()
    {
        $this->scopeConfigMock->method('getValue')
            ->willReturn(true);

        $this->orderMock->method('getStatus')->willReturn('pending');
        $this->orderRepositoryMock->method('get')->willReturn($this->orderMock);
        $this->cacheMock->method('load')->willReturn(false);

        $this->orderRepositoryMock->expects($this->once())
            ->method('save')
            ->with($this->orderMock);

        $result = $this->orderStatusUpdateSubmit->updateOrderStatus([
            'order_increment_id' => '000000005',
            'new_order_status' => 'processing'
        ]);

        $this->assertTrue($result[0]['status']);
        $this->assertStringContainsString('Successfully', (string)$result[0]['message']);
    }

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $this->cacheMock = $this->createMock(CacheInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->orderMock = $this->createMock(Order::class);

        $this->orderStatusUpdateSubmit = new OrderStatusUpdateSubmit(
            $this->scopeConfigMock,
            $this->orderRepositoryMock,
            $this->loggerMock,
            $this->cacheMock
        );

        // here I mocked the OrderConfig because If getConfig() is not mocked, it returns null
        $orderConfigMock = $this->createMock(OrderConfig::class);
        $this->orderMock->method('getConfig')->willReturn($orderConfigMock);
        $orderConfigMock->method('getStates')->willReturn([
            'new' => 'New',
            'closed' => 'Closed',
            'processing' => 'Processing',
            'canceled' => 'Canceled',
            'payment_review' => 'Payment Review',
            'complete' => 'Complete',
            'holded' => 'On Hold'
        ]);
        $orderConfigMock->method('getStateStatuses')->willReturnCallback(
            function ($state) {
                $stateStatuses = [
                    'new' => ['pending', 'pending_payment'],
                    'processing' => ['processing', 'pending', 'pending_fulfillment'],
                    'closed' => ['closed'],
                    'payment_review' => ['payment_review'],
                    'holded' => ['holded'],
                    'complete' => ['complete'],
                    'canceled' => ['canceled']
                ];
                return $stateStatuses[$state] ?? [];
            }
        );
        $this->orderMock->method('getConfig')->willReturn($orderConfigMock);
        // set this to true to allow order to update successfully
        $this->orderMock->method('canHold')->willReturn(true);
        $this->orderMock->method('setState')->willReturnSelf();
        $this->orderMock->method('setStatus')->willReturnSelf();
    }
}
