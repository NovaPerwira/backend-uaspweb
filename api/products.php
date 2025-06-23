<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Ambil semua produk dari database secara ASCENDING (kecil ke besar)
    $stmt = $db->prepare("SELECT * FROM products ORDER BY created_at ASC");
    $stmt->execute();

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tampilkan sebagai JSON
    header('Content-Type: application/json');
    echo json_encode([
        "status" => "success",
        "data" => $products
    ]);
    exit;
}
