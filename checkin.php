<?php
// checkin.php — toggles a check-in for a food spot
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user']['id'];
$spot_id = intval($_POST['spot_id'] ?? 0);

if (!$spot_id) {
    echo json_encode(['success' => false, 'message' => 'No spot specified']);
    exit();
}

// Check if already checked in
$stmt = $conn->prepare("SELECT id FROM checkins WHERE user_id = ? AND spot_id = ?");
$stmt->bind_param("ii", $user_id, $spot_id);
$stmt->execute();
$exists = $stmt->get_result()->num_rows > 0;
$stmt->close();

if ($exists) {
    $stmt = $conn->prepare("DELETE FROM checkins WHERE user_id = ? AND spot_id = ?");
    $stmt->bind_param("ii", $user_id, $spot_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true, 'action' => 'removed']);
} else {
    $stmt = $conn->prepare("INSERT INTO checkins (user_id, spot_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $spot_id);
    $stmt->execute();
    $stmt->close();

    // Check if trail is now complete
    $res = $conn->prepare("
        SELECT fs.trail_id, COUNT(fs.id) AS total,
        (SELECT COUNT(*) FROM checkins c2
         JOIN food_spots fs2 ON c2.spot_id = fs2.id
         WHERE c2.user_id = ? AND fs2.trail_id = fs.trail_id) AS done
        FROM food_spots fs WHERE fs.id = ?
    ");
    $res->bind_param("ii", $user_id, $spot_id);
    $res->execute();
    $row = $res->get_result()->fetch_assoc();
    $res->close();

    $trailComplete = ($row && $row['total'] > 0 && $row['done'] >= $row['total']);
    echo json_encode(['success' => true, 'action' => 'added', 'trail_complete' => $trailComplete]);
}
?>
