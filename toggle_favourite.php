<?php
// toggle_favourite.php
// Called via fetch() from the browser to save/remove a favourite
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id  = $_SESSION['user']['id'];
$spot_id  = isset($_POST['spot_id'])  ? intval($_POST['spot_id'])  : null;
$trail_id = isset($_POST['trail_id']) ? intval($_POST['trail_id']) : null;

if (!$spot_id && !$trail_id) {
    echo json_encode(['success' => false, 'message' => 'No spot or trail specified']);
    exit();
}

// Check if already favourited
if ($spot_id) {
    $stmt = $conn->prepare("SELECT id FROM favourites WHERE user_id = ? AND spot_id = ?");
    $stmt->bind_param("ii", $user_id, $spot_id);
} else {
    $stmt = $conn->prepare("SELECT id FROM favourites WHERE user_id = ? AND trail_id = ?");
    $stmt->bind_param("ii", $user_id, $trail_id);
}
$stmt->execute();
$exists = $stmt->get_result()->num_rows > 0;
$stmt->close();

if ($exists) {
    // Remove favourite
    if ($spot_id) {
        $stmt = $conn->prepare("DELETE FROM favourites WHERE user_id = ? AND spot_id = ?");
        $stmt->bind_param("ii", $user_id, $spot_id);
    } else {
        $stmt = $conn->prepare("DELETE FROM favourites WHERE user_id = ? AND trail_id = ?");
        $stmt->bind_param("ii", $user_id, $trail_id);
    }
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true, 'action' => 'removed']);
} else {
    // Add favourite
    if ($spot_id) {
        $stmt = $conn->prepare("INSERT INTO favourites (user_id, spot_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $spot_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO favourites (user_id, trail_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $trail_id);
    }
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true, 'action' => 'added']);
}
?>
