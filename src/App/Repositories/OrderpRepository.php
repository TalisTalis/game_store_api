<?php
namespace App\Repositories;

use App\Database;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Exceptions\NotFoundException;

class OrderRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function create(int $productId, string $sku, string $amount, string $uuid): Order
    {
        $sql = "INSERT INTO orders (uuid, product_id, sku, amount, status) 
                VALUES (:uuid, :product_id, :sku, :amount, :status) RETURNING id, created_at";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'uuid' => $uuid,
            'product_id' => $productId,
            'sku' => $sku,
            'amount' => $amount,
            'status' => OrderStatus::Pending->value
        ]);
        
        $data = $stmt->fetch();
        
        return new Order(
            id: (int)$data['id'],
            uuid: $uuid,
            productId: $productId,
            sku: $sku,
            amount: $amount,
            status: OrderStatus::Pending,
            createdAt: $data['created_at']
        );
    }

    public function findByUuid(string $uuid): Order
    {
        $sql = "SELECT o.*, od.secret_key 
                FROM orders o 
                LEFT JOIN order_deliveries od ON o.id = od.order_id 
                WHERE o.uuid = :uuid";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uuid' => $uuid]);
        $data = $stmt->fetch();

        if (!$data) {
            throw new NotFoundException("Order not found");
        }

        return new Order(
            id: (int)$data['id'],
            uuid: $data['uuid'],
            productId: (int)$data['product_id'],
            sku: $data['sku'],
            amount: $data['amount'],
            status: OrderStatus::from($data['status']),
            createdAt: $data['created_at'],
            secretKey: $data['secret_key']
        );
    }

    public function updateStatus(int $id, OrderStatus $status): void
    {
        $sql = "UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id";
        $this->db->prepare($sql)->execute([
            'status' => $status->value,
            'id' => $id
        ]);
    }

    public function addDeliveryKey(int $orderId, string $key): void
    {
        $sql = "INSERT INTO order_deliveries (order_id, secret_key) VALUES (:order_id, :key)";
        $this->db->prepare($sql)->execute([
            'order_id' => $orderId,
            'key' => $key
        ]);
    }
}