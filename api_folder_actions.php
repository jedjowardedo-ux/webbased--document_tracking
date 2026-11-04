<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pdo = getPDO();
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$user_id = $_SESSION['user_id'];
$office_id = $_SESSION['office_id']; 

if ($action === 'create') {
    $name = trim($data['folder_name'] ?? '');
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Folder name is required']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO folders (folder_name, office_id, creator_id) VALUES (?, ?, ?)");
        $stmt->execute([$name, $office_id, $user_id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        // 23505 is Postgres code for Unique Violation
        if ($e->getCode() == '23505') {
            echo json_encode(['success' => false, 'message' => 'This folder already exists in your office.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
    }
} 

elseif ($action === 'delete') {
    $folder_id = $data['folder_id'] ?? 0;
    
    // Safety Check: Only allow deletion if the folder belongs to the user's office
    $stmt = $pdo->prepare("DELETE FROM folders WHERE id = ? AND office_id = ?");
    $stmt->execute([$folder_id, $office_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Folder not found or access denied.']);
    }
}