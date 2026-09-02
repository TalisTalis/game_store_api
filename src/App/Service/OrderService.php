<?php
namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Exceptions\NotFoundException;
use App\Exceptions\BadRequestException;

class OrderService
{
    public function __construct(
        private OrderRepository $orderRepo,
        private ProductRepository $productRepo
    ) {}

    public function createOrder(string $sku): Order
    {
        $product = $this->productRepo->findBySku($sku);
        
        // Генерируем UUID на стороне приложения
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));

        // Транзакция на случай, если в будущем добавится резервирование товара на складе
        $this->orderRepo->getDb()->beginTransaction();
        try {
            $order = $this->orderRepo->create(
                productId: $product->id,
                sku: $product->sku,
                amount: $product->price,
                uuid: $uuid
            );
            $this->orderRepo->getDb()->commit();
        } catch (\Throwable $e) {
            $this->orderRepo->getDb()->rollBack();
            throw $e;
        }

        return $order;
    }

    public function getOrder(string $uuid): Order
    {
        return $this->orderRepo->findByUuid($uuid);
    }
}