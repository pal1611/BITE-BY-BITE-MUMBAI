<?php
session_start();
require 'db.php';

if (isset($_SESSION['user'])) { header("Location: index.php"); exit(); }

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $password = $_POST['password']      ?? '';
    $confirm  = $_POST['confirm']       ?? '';

    if (!$name || !$username || !$email || !$phone || !$password || !$confirm) {
        $error = "All fields are required.";
    } elseif (!preg_match('/^\d{10}$/', $phone)) {
        $error = "Please enter a valid 10-digit phone number.";
    } elseif (!preg_match('/^[a-zA-Z ]+$/', $name)) {
        $error = "Full name should contain only letters.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $error = "Username must be 3–20 characters: letters, numbers and underscores only.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email); $stmt->execute(); $stmt->store_result();
        if ($stmt->num_rows > 0) { $error = "An account with this email already exists."; $stmt->close(); }
        else {
            $stmt->close();
            $stmt2 = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt2->bind_param("s", $username); $stmt2->execute(); $stmt2->store_result();
            if ($stmt2->num_rows > 0) { $error = "This username is already taken."; $stmt2->close(); }
            else {
                $stmt2->close();
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $stmt3 = $conn->prepare("INSERT INTO users (name, username, email, phone, password, role) VALUES (?,?,?,?,?,'user')");
                $stmt3->bind_param("sssss", $name, $username, $email, $phone, $hashed);
                if ($stmt3->execute()) {
                    $_SESSION['success'] = "Registration successful! Please log in.";
                    header("Location: login.php"); exit();
                } else { $error = "Registration failed. Please try again."; }
                $stmt3->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register - Bite by Bite</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#fff8f0;font-family:Arial,Helvetica,sans-serif;}
header{background:#fff1b5;padding:15px 40px;display:flex;justify-content:space-between;align-items:center;}
header h1{color:#634b49;font-size:24px;}
nav a{margin-left:20px;text-decoration:none;color:#634b49;font-weight:bold;}
.register-container{display:flex;justify-content:center;align-items:center;min-height:80vh;padding:30px 20px;}
.register-box{background:white;padding:40px;border-radius:12px;box-shadow:0 5px 20px rgba(0,0,0,0.1);width:100%;max-width:420px;}
.register-box h2{text-align:center;color:#634b49;margin-bottom:24px;font-size:22px;}
.field{margin-bottom:14px;}
.field label{display:block;font-size:13px;font-weight:bold;color:#634b49;margin-bottom:5px;}
.field input{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:7px;font-size:14px;transition:border-color 0.2s;}
.field input:focus{outline:none;border-color:#634b49;}
.field .hint{font-size:11px;color:#aaa;margin-top:3px;}
.error{color:red;font-size:13px;margin-bottom:12px;padding:8px 12px;background:#fdecea;border-radius:6px;}
button{width:100%;padding:11px;background:#fff1b5;border:none;border-radius:7px;font-weight:bold;color:#634b49;cursor:pointer;font-size:15px;}
button:hover{background:#c1dbe8;}
.login-link{text-align:center;margin-top:16px;font-size:13px;color:#888;}
.login-link a{color:#634b49;font-weight:bold;text-decoration:none;}
footer{background:#43302e;color:white;text-align:center;padding:20px;margin-top:40px;}
footer p{margin:0;}
</style>
</head>
<body>
<header>
  <h1>Bite by Bite - Mumbai</h1>
  <nav><a href="index.php">Home</a><a href="login.php">Login</a><a href="register.php">Register</a></nav>
</header>
<div class="register-container">
  <div class="register-box">
    <h2>Create Account</h2>
    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST" action="register.php">
      <div class="field"><label>Full Name</label><input type="text" name="name" value="<?= htmlspecialchars($_POST['name']??'') ?>" placeholder="Your full name" required></div>
      <div class="field"><label>Username</label><input type="text" name="username" value="<?= htmlspecialchars($_POST['username']??'') ?>" placeholder="e.g. foodie_shreya" required><div class="hint">3–20 characters. Letters, numbers and underscores only. Visible to other users.</div></div>
      <div class="field"><label>Phone Number</label><input type="text" name="phone" value="<?= htmlspecialchars($_POST['phone']??'') ?>" placeholder="10-digit number" required></div>
      <div class="field"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($_POST['email']??'') ?>" placeholder="your@email.com" required></div>
      <div class="field"><label>Password</label><input type="password" name="password" placeholder="At least 6 characters" required></div>
      <div class="field"><label>Confirm Password</label><input type="password" name="confirm" placeholder="Repeat your password" required></div>
      <button type="submit">Register</button>
    </form>
    <div class="login-link">Already have an account? <a href="login.php">Login</a></div>
  </div>
</div>
<footer><p>© 2026 Bite by Bite - Mumbai | Created by Shreya Sakala, Gayatri Bedekar & Palak Sanklecha</p></footer>
</body>
</html>
