<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Controllers\OrderController;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Services\SupplierMockService;
use App\Exceptions\NotFoundException;
use App\Exceptions\BadRequestException;

header('Content-Type: application/json');

// Simple DI
 $controller = new OrderController(
    new OrderService(new OrderRepository(), new ProductRepository()),
    new PaymentService(new OrderRepository(), new SupplierMockService())
);

// Parse path
 $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
 $method = $_SERVER['REQUEST_METHOD'];

// Read JSON input
 $input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    if ($uri === '/api/orders' && $method === 'POST') {
        echo json_encode($controller->create($input));
        http_response_code(201);
    } 
    elseif (preg_match('#^/api/orders/([a-f0-9\-]+)$#', $uri, $matches) && $method === 'GET') {
        echo json_encode($controller->get($matches[1]));
    } 
    elseif ($uri === '/api/webhooks/payment' && $method === 'POST') {
        echo json_encode($controller->paymentWebhook($input));
    } 
    else {
        echo json_encode(['error' => 'Endpoint not found']);
        http_response_code(404);
    }
} catch (NotFoundException $e) {
    http_response_code(404);
    echo json_encode(['error' => $e->getMessage()]);
} catch (BadRequestException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} catch (\Throwable $e) {
    // В реальном приложении здесь логгер (Monolog и т.д.)
    error_log($e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
}