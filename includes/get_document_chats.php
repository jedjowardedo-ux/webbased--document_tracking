<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once '../includes/config.php';

$document_id = isset($_GET['document_id']) ? intval($_GET['document_id']) : 0;

if ($document_id === 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Document ID is required']);
    exit();
}

try {
    $pdo = getPDO();
    $sql = "SELECT c.id, c.sender_id, u.name as sender_name, c.message_text, c.created_at 
            FROM document_chats c
            JOIN \"user\" u ON c.sender_id = u.id
            WHERE c.document_id = ? 
            ORDER BY c.created_at ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$document_id]);
    
    echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>