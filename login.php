<?php
session_start();
require 'db.php';

if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$error   = "";
$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = "Please enter your email and password.";
    } else {
        $stmt = $conn->prepare("SELECT id, name, username, email, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Store user in session
                $_SESSION['user'] = [
                'id'       => $user['id'],
                'name'     => $user['name'],
                'username' => $user['username'],  // ⭐ ADD THIS LINE
                'email'    => $user['email'],
                'role'     => $user['role'],
                ];
                if ($user['role'] === 'admin') {
                    header("Location: admin.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login - Bite by Bite</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#fff8f0; font-family:Arial,Helvetica,sans-serif; }
header { background:#fff1b5; padding:15px 40px; display:flex; justify-content:space-between; align-items:center; }
header h1 { color:#634b49; font-size:24px; }
nav a { margin-left:20px; text-decoration:none; color:#634b49; font-weight:bold; }
nav a:hover { text-decoration:underline; }

.login-container { display:flex; justify-content:center; align-items:center; min-height:80vh; padding:30px 20px; }
.login-box { background:white; padding:40px; border-radius:12px; box-shadow:0 5px 20px rgba(0,0,0,0.1); width:100%; max-width:360px; }
.login-box h2 { text-align:center; color:#634b49; margin-bottom:24px; font-size:22px; }

.field { margin-bottom:14px; }
.field label { display:block; font-size:13px; font-weight:bold; color:#634b49; margin-bottom:5px; }
.field input { width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:7px; font-size:14px; transition:border-color 0.2s; }
.field input:focus { outline:none; border-color:#634b49; }

.error   { color:red;     font-size:13px; margin-bottom:12px; padding:8px 12px; background:#fdecea; border-radius:6px; }
.success { color:#2e7d32; font-size:13px; margin-bottom:12px; padding:8px 12px; background:#e6f9e6; border-radius:6px; }

button { width:100%; padding:11px; background:#fff1b5; border:none; border-radius:7px; font-weight:bold; color:#634b49; cursor:pointer; font-size:15px; transition:background 0.2s; }
button:hover { background:#c1dbe8; }
.register-link { text-align:center; margin-top:16px; font-size:13px; color:#888; }
.register-link a { color:#634b49; font-weight:bold; text-decoration:none; }

footer { background:#43302e; color:white; text-align:center; padding:20px; margin-top:40px; }
footer p { margin:0; }
</style>
</head>
<body>

<header>
  <h1>Bite by Bite - Mumbai</h1>
  <nav>
    <a href="index.php">Home</a>
    <a href="trails.php">Trails</a>
    <a href="login.php">Login</a>
    <a href="register.php">Register</a>
  </nav>
</header>
<?php include 'cookie-banner.php'; ?>

<div class="login-container">
  <div class="login-box">
    <h2>Login</h2>

    <?php if ($error):   ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <form method="POST" action="login.php">
      <div class="field">
        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="your@email.com" required>
      </div>
      <div class="field">
        <label>Password</label>
        <input type="password" name="password" placeholder="Your password" required>
      </div>
      <button type="submit">Login</button>
    </form>

    <div class="register-link">Don't have an account? <a href="register.php">Register</a></div>
  </div>
</div>

<footer><p>© 2026 Bite by Bite - Mumbai | Created by Shreya Sakala, Gayatri Bedekar & Palak Sanklecha</p></footer>
</body>
</html>
