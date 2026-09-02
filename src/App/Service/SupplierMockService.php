<?php
namespace App\Services;

class SupplierMockService
{
    public function requestKey(string $sku): string
    {
        // Эмуляция задержки сети до поставщика
        usleep(100000); // 100ms
        
        // Эмуляция случайного сбоя (раскомментировать для тестов rollback)
        // if (rand(1, 100) === 1) throw new \RuntimeException("Supplier timeout");
        
        return "KEY-" . strtoupper(substr(md5($sku . time()), 0, 16));
    }
}