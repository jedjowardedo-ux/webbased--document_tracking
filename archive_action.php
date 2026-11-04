<?php
// Relative paths work best here since these are in the same folder
require_once 'includes/config.php';
require_once 'includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['doc_id'])) {
    $pdo = getPDO();
    $doc_id = $_POST['doc_id'];
    $user_id = $_SESSION['user_id'];

    try {
        // Update the document to archived
        $stmt = $pdo->prepare("UPDATE \"document\" SET is_archived = TRUE WHERE id = ? AND uploader_id = ?");
        $stmt->execute([$doc_id, $user_id]);

        // Redirecting to track.php in the SAME folder
        header('Location: track.php?archived=success');
        exit();
    } catch (PDOException $e) {
        die("Error archiving document: " . $e->getMessage());
    }
}