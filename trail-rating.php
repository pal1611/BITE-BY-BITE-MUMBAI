<?php
/**
 * trail-rating.php
 * AJAX endpoint — submit or update a trail rating (1–5 stars)
 *
 * POST params:
 *   trail_id  int   — ID of the trail being rated
 *   rating    int   — Rating value 1–5
 *
 * Returns JSON:
 *   { success: true,  avg: 4.2, count: 17 }
 *   { success: false, error: "message" }
 */

session_start();
require 'db.php';

header('Content-Type: application/json');

// Must be logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

// Must be POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

$user_id  = (int) $_SESSION['user']['id'];
$trail_id = (int) ($_POST['trail_id'] ?? 0);
$rating   = (int) ($_POST['rating']   ?? 0);

// Validate
if ($trail_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid trail ID']);
    exit();
}
if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'error' => 'Rating must be between 1 and 5']);
    exit();
}

// Check trail exists
$check = $conn->prepare("SELECT id FROM trails WHERE id = ?");
$check->bind_param("i", $trail_id);
$check->execute();
$check->store_result();
if ($check->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Trail not found']);
    $check->close();
    exit();
}
$check->close();

// Insert or update rating (unique per user+trail)
$stmt = $conn->prepare("
    INSERT INTO trail_ratings (trail_id, user_id, rating)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE rating = VALUES(rating)
");
$stmt->bind_param("iii", $trail_id, $user_id, $rating);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'error' => 'Failed to save rating']);
    $stmt->close();
    exit();
}
$stmt->close();

// Return updated average and count
$avg_stmt = $conn->prepare("
    SELECT ROUND(AVG(rating), 1) AS avg_r, COUNT(*) AS cnt
    FROM trail_ratings
    WHERE trail_id = ?
");
$avg_stmt->bind_param("i", $trail_id);
$avg_stmt->execute();
$result = $avg_stmt->get_result()->fetch_assoc();
$avg_stmt->close();

echo json_encode([
    'success'    => true,
    'avg'        => (float) $result['avg_r'],
    'count'      => (int)   $result['cnt'],
    'userRating' => $rating,
]);
