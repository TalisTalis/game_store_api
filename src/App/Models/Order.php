<?php
namespace App\Models;

use App\Enums\OrderStatus;

readonly class Order
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $productId,
        public string $sku,
        public string $amount,
        public OrderStatus $status,
        public string $createdAt,
        public ?string $secretKey = null
    ) {}
}