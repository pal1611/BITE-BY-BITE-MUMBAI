<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Pick a random trail or spot
$type = $_GET['type'] ?? 'both'; // 'trail', 'spot', or 'both'

if ($type === 'trail') {
    $result = $conn->query("SELECT id FROM trails ORDER BY RAND() LIMIT 1")->fetch_assoc();
    header("Location: trail-details.php?id=" . $result['id']);
    exit();
} elseif ($type === 'spot') {
    $result = $conn->query("SELECT id FROM food_spots ORDER BY RAND() LIMIT 1")->fetch_assoc();
    header("Location: spot-details.php?id=" . $result['id']);
    exit();
} else {
    // Randomly pick either a trail or a spot
    $pick = rand(0, 1) ? 'trail' : 'spot';
    if ($pick === 'trail') {
        $result = $conn->query("SELECT id FROM trails ORDER BY RAND() LIMIT 1")->fetch_assoc();
        header("Location: trail-details.php?id=" . $result['id']);
    } else {
        $result = $conn->query("SELECT id FROM food_spots ORDER BY RAND() LIMIT 1")->fetch_assoc();
        header("Location: spot-details.php?id=" . $result['id']);
    }
    exit();
}
?>
