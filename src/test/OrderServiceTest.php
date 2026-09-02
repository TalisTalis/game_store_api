<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Services\SupplierMockService;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Exceptions\NotFoundException;

class OrderServiceTest extends TestCase
{
    private OrderService $orderService;
    private PaymentService $paymentService;
    private OrderRepository $orderRepoMock;
    private SupplierMockService $supplierMock;

    protected function setUp(): void
    {
        $this->orderRepoMock = $this->createMock(OrderRepository::class);
        $productRepoMock = $this->createMock(ProductRepository::class);
        $this->supplierMock = $this->createMock(SupplierMockService::class);

        $productRepoMock->method('findBySku')->willReturn(
            new Product(1, 'GAME-001', 'Game', '1000.00')
        );

        $this->orderService = new OrderService($this->orderRepoMock, $productRepoMock);
        $this->paymentService = new PaymentService($this->orderRepoMock, $this->supplierMock);
    }

    public function testCreateOrderSuccess(): void
    {
        $expectedOrder = new Order(1, 'uuid-123', 1, 'GAME-001', '1000.00', OrderStatus::Pending, '2023-10-10 10:00:00');
        
        $this->orderRepoMock->expects($this->once())
            ->method('create')
            ->willReturn($expectedOrder);

        $result = $this->orderService->createOrder('GAME-001');
        
        $this->assertEquals('uuid-123', $result->uuid);
        $this->assertEquals(OrderStatus::Pending, $result->status);
    }

    public function testPaymentWebhookIdempotency(): void
    {
        // Если заказ уже выдан, поставщик НЕ должен вызываться
        $deliveredOrder = new Order(1, 'uuid-123', 1, 'GAME-001', '1000.00', OrderStatus::Delivered, '2023-10-10', 'KEY-XXX');
        
        $this->orderRepoMock->method('findByUuid')->willReturn($deliveredOrder);
        
        $this->supplierMock->expects($this->never())->method('requestKey');
        $this->orderRepoMock->expects($this->never())->method('updateStatus');

        // Вызываем вебхук дважды
        $this->paymentService->handleSuccessfulPayment('uuid-123');
        $this->paymentService->handleSuccessfulPayment('uuid-123');
        
        // Если исключений нет — идемпотентность работает
        $this->assertTrue(true);
    }
}