<?php
header('Content-Type: application/json');
require_once '../auth_check.php';
require_once '../db_connect.php';

class PosAPI {
    private $conn;
    private $request;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
        $this->request = $this->parseRequest();
    }

    private function parseRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $endpoint = $_GET['endpoint'] ?? '';
        $data = json_decode(file_get_contents('php://input'), true);
        
        return [
            'method' => $method,
            'endpoint' => $endpoint,
            'data' => $data
        ];
    }

    public function handleRequest() {
        try {
            switch ($this->request['endpoint']) {
                case 'products':
                    return $this->handleProducts();
                case 'orders':
                    return $this->handleOrders();
                case 'sales':
                    return $this->handleSales();
                case 'inventory':
                    return $this->handleInventory();
                default:
                    throw new Exception('Invalid endpoint');
            }
        } catch (Exception $e) {
            return $this->sendResponse(['error' => $e->getMessage()], 400);
        }
    }

    private function handleProducts() {
        switch ($this->request['method']) {
            case 'GET':
                $query = "SELECT p.*, c.name as category_name 
                         FROM products p 
                         JOIN categories c ON p.category_id = c.category_id";
                $result = $this->conn->query($query);
                return $this->sendResponse([
                    'products' => $result->fetch_all(MYSQLI_ASSOC)
                ]);

            case 'POST':
                $data = $this->request['data'];
                $stmt = $this->conn->prepare("
                    INSERT INTO products (name, category_id, price, cost, stock) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("siddi", 
                    $data['name'], 
                    $data['category_id'], 
                    $data['price'], 
                    $data['cost'], 
                    $data['stock']
                );
                $stmt->execute();
                return $this->sendResponse([
                    'message' => 'Product created successfully',
                    'id' => $this->conn->insert_id
                ], 201);

            case 'PUT':
                $data = $this->request['data'];
                $stmt = $this->conn->prepare("
                    UPDATE products 
                    SET name=?, price=?, cost=?, stock=? 
                    WHERE product_id=?
                ");
                $stmt->bind_param("sddii", 
                    $data['name'], 
                    $data['price'], 
                    $data['cost'], 
                    $data['stock'], 
                    $data['product_id']
                );
                $stmt->execute();
                return $this->sendResponse([
                    'message' => 'Product updated successfully'
                ]);
        }
    }

    private function handleOrders() {
        switch ($this->request['method']) {
            case 'GET':
                $query = "SELECT o.*, c.name as customer_name, e.name as employee_name
                         FROM orders o
                         LEFT JOIN customers c ON o.customer_id = c.customer_id
                         LEFT JOIN employees e ON o.employee_id = e.employee_id
                         ORDER BY o.order_date DESC";
                $result = $this->conn->query($query);
                return $this->sendResponse([
                    'orders' => $result->fetch_all(MYSQLI_ASSOC)
                ]);

            case 'POST':
                $this->conn->begin_transaction();
                try {
                    $data = $this->request['data'];
                    
                    // Insert order
                    $stmt = $this->conn->prepare("
                        INSERT INTO orders (customer_id, employee_id, total_amount, status) 
                        VALUES (?, ?, ?, 'Pending')
                    ");
                    $stmt->bind_param("iid", 
                        $data['customer_id'], 
                        $data['employee_id'], 
                        $data['total_amount']
                    );
                    $stmt->execute();
                    $order_id = $this->conn->insert_id;

                    // Insert order items
                    $stmt = $this->conn->prepare("
                        INSERT INTO order_items (order_id, product_id, quantity, unit_price) 
                        VALUES (?, ?, ?, ?)
                    ");
                    foreach ($data['items'] as $item) {
                        $stmt->bind_param("iiid", 
                            $order_id,
                            $item['product_id'],
                            $item['quantity'],
                            $item['unit_price']
                        );
                        $stmt->execute();
                    }

                    $this->conn->commit();
                    return $this->sendResponse([
                        'message' => 'Order created successfully',
                        'order_id' => $order_id
                    ], 201);

                } catch (Exception $e) {
                    $this->conn->rollback();
                    throw $e;
                }
        }
    }

    private function handleSales() {
        $period = $_GET['period'] ?? 'today';
        $query = "";
        
        switch ($period) {
            case 'today':
                $query = "SELECT 
                            SUM(total_amount) as total_sales,
                            COUNT(*) as order_count,
                            SUM(profit) as total_profit
                         FROM orders 
                         WHERE DATE(order_date) = CURDATE()
                         AND order_status = 'Completed'";
                break;
                
            case 'weekly':
                $query = "SELECT 
                            DATE(order_date) as date,
                            SUM(total_amount) as total_sales,
                            COUNT(*) as order_count
                         FROM orders 
                         WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                         AND order_status = 'Completed'
                         GROUP BY DATE(order_date)";
                break;
        }
        
        $result = $this->conn->query($query);
        return $this->sendResponse([
            'sales_data' => $result->fetch_all(MYSQLI_ASSOC)
        ]);
    }

    private function handleInventory() {
        switch ($this->request['method']) {
            case 'GET':
                $query = "SELECT p.*, 
                            (SELECT SUM(quantity_change) 
                             FROM inventory_logs 
                             WHERE product_id = p.product_id) as stock_movement
                         FROM products p
                         WHERE p.stock <= p.reorder_level";
                $result = $this->conn->query($query);
                return $this->sendResponse([
                    'low_stock_items' => $result->fetch_all(MYSQLI_ASSOC)
                ]);

            case 'POST':
                $data = $this->request['data'];
                $stmt = $this->conn->prepare("
                    INSERT INTO inventory_logs (product_id, type, quantity_change) 
                    VALUES (?, ?, ?)
                ");
                $stmt->bind_param("isi", 
                    $data['product_id'], 
                    $data['type'], 
                    $data['quantity_change']
                );
                $stmt->execute();
                
                // Update product stock
                $stmt = $this->conn->prepare("
                    UPDATE products 
                    SET stock = stock + ? 
                    WHERE product_id = ?
                ");
                $stmt->bind_param("ii", 
                    $data['quantity_change'], 
                    $data['product_id']
                );
                $stmt->execute();
                
                return $this->sendResponse([
                    'message' => 'Inventory updated successfully'
                ]);
        }
    }

    private function sendResponse($data, $status = 200) {
        http_response_code($status);
        return json_encode($data);
    }
}

// Initialize and handle the API request
$api = new PosAPI($conn);
echo $api->handleRequest();
$conn->close();
?>