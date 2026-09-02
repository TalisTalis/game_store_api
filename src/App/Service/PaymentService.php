<?php
namespace App\Services;

use App\Enums\OrderStatus;
use App\Repositories\OrderRepository;
use App\Exceptions\BadRequestException;

class PaymentService
{
    public function __construct(
        private OrderRepository $orderRepo,
        private SupplierMockService $supplier
    ) {}

    /**
     * Обработка вебхука об успешной оплате.
     * ИДЕМПОТЕНТЕН: многократный вызов не вызывает побочных эффектов.
     */
    public function handleSuccessfulPayment(string $orderUuid): void
    {
        $order = $this->orderRepo->findByUuid($orderUuid);

        // Идемпотентность: если уже выдан, просто возвращаем успех
        if ($order->status === OrderStatus::Delivered) {
            return; 
        }

        // Если упал при прошлом вызове после оплаты, но до выдачи — возобновляем процесс выдачи
        if ($order->status === OrderStatus::Paid || $order->status === OrderStatus::Pending) {
            $this->processDelivery($order);
            return;
        }

        throw new BadRequestException("Invalid order status for payment: {$order->status->value}");
    }

    private function processDelivery($order): void
    {
        $this->orderRepo->updateStatus($order->id, OrderStatus::Paid);

        try {
            $key = $this->supplier->requestKey($order->sku);
            
            // Транзакция обязательна, чтобы ключ и статус не рассинхронились
            $this->orderRepo->getDb()->beginTransaction();
            $this->orderRepo->addDeliveryKey($order->id, $key);
            $this->orderRepo->updateStatus($order->id, OrderStatus::Delivered);
            $this->orderRepo->getDb()->commit();
        } catch (\Throwable $e) {
            if ($this->orderRepo->getDb()->inTransaction()) {
                $this->orderRepo->getDb()->rollBack();
            }
            $this->orderRepo->updateStatus($order->id, OrderStatus::Failed);
            throw $e; // Пробрасываем дальше для логирования
        }
    }
}