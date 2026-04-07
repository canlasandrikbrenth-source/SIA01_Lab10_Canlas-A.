<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$host = '127.0.0.1';
$db   = 'northwind';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed', 'message' => $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? '';
switch ($action) {
    case 'yearRevenue':
        $sql = "SELECT YEAR(OrderDate) AS SalesYear, ROUND(SUM(UnitPrice * Quantity * (1 - Discount)), 2) AS TotalRevenue FROM orders JOIN order_details ON orders.OrderID = order_details.OrderID GROUP BY SalesYear ORDER BY SalesYear";
        break;
    case 'monthlyRevenue':
        $sql = "SELECT DATE_FORMAT(o.OrderDate, '%Y-%m') AS `YYYY-MM`, ROUND(SUM(od.UnitPrice * od.Quantity * (1 - od.Discount)), 2) AS MonthlyRevenue FROM orders o JOIN order_details od ON o.OrderID = od.OrderID GROUP BY `YYYY-MM` ORDER BY MonthlyRevenue DESC LIMIT 10";
        break;
    case 'productRevenue':
        $sql = "SELECT p.ProductName, ROUND(SUM(od.UnitPrice * od.Quantity * (1 - od.Discount)), 2) AS ProductRevenue FROM products p JOIN order_details od ON p.ProductID = od.ProductID GROUP BY p.ProductName ORDER BY ProductRevenue DESC LIMIT 11";
        break;
    case 'customerRevenue':
        $sql = "SELECT c.CompanyName, ROUND(SUM(od.UnitPrice * od.Quantity * (1 - od.Discount)), 2) AS TotalSpent FROM customers c JOIN orders o ON c.CustomerID = o.CustomerID JOIN order_details od ON o.OrderID = od.OrderID GROUP BY c.CompanyName ORDER BY TotalSpent DESC LIMIT 10";
        break;
    case 'ordersCalendar':
        $sql = "SELECT o.OrderID, c.CompanyName, DATE(o.OrderDate) AS OrderDate, ROUND(SUM(od.UnitPrice * od.Quantity * (1 - od.Discount)), 2) AS TotalOrderValue FROM orders o JOIN customers c ON o.CustomerID = c.CustomerID JOIN order_details od ON o.OrderID = od.OrderID GROUP BY o.OrderID, c.CompanyName, DATE(o.OrderDate) ORDER BY OrderDate";
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        exit;
}

try {
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();
    echo json_encode($rows);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Query execution failed', 'message' => $e->getMessage()]);
}
