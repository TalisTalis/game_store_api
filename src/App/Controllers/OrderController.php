<?php
namespace App\Controllers;

use App\Services\OrderService;
use App\Services\PaymentService;
use App\Exceptions\NotFoundException;
use App\Exceptions\BadRequestException;

class OrderController
{
    public function __construct(
        private OrderService $orderService,
        private PaymentService $paymentService
    ) {}

    public function create(array $payload): array
    {
        if (empty($payload['sku'])) {
            throw new BadRequestException("SKU is required");
        }

        $order = $this->orderService->createOrder($payload['sku']);
        
        return [
            'status' => 'success',
            'data' => [
                'uuid' => $order->uuid,
                'status' => $order->status->value,
                'message' => 'Order created. Awaiting payment.'
            ]
        ];
    }

    public function get(string $uuid): array
    {
        $order = $this->orderService->getOrder($uuid);

        $data = [
            'uuid' => $order->uuid,
            'sku' => $order->sku,
            'amount' => $order->amount,
            'status' => $order->status->value,
            'created_at' => $order->createdAt
        ];

        // Показываем ключ только если заказ выдан
        if ($order->status === \App\Enums\OrderStatus::Delivered) {
            $data['secret_key'] = $order->secretKey;
        }

        return ['status' => 'success', 'data' => $data];
    }

    public function paymentWebhook(array $payload): array
    {
        if (empty($payload['order_uuid']) || $payload['status'] !== 'success') {
            return ['status' => 'ignored'];
        }

        $this->paymentService->handleSuccessfulPayment($payload['order_uuid']);

        return ['status' => 'processed'];
    }
}