<?php
session_start();

// Handle form submission
$saved = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $essential  = true; // always on
    $functional = isset($_POST['functional']) ? 1 : 0;
    $analytics  = isset($_POST['analytics'])  ? 1 : 0;

    // Set cookies for 1 year
    $expiry = time() + (365 * 24 * 60 * 60);
    setcookie('cookie_consent',   'set',          $expiry, '/', '', false, true);
    setcookie('cookie_functional', $functional,   $expiry, '/', '', false, true);
    setcookie('cookie_analytics',  $analytics,    $expiry, '/', '', false, true);
    $saved = true;
}

// Read current preferences
$consentGiven = isset($_COOKIE['cookie_consent']);
$functional   = isset($_COOKIE['cookie_functional']) ? (int)$_COOKIE['cookie_functional'] : 1;
$analytics    = isset($_COOKIE['cookie_analytics'])  ? (int)$_COOKIE['cookie_analytics']  : 0;

// Handle "Accept All" / "Reject All" quick actions
if (isset($_GET['action'])) {
    $expiry = time() + (365 * 24 * 60 * 60);
    if ($_GET['action'] === 'accept_all') {
        setcookie('cookie_consent',    'set', $expiry, '/', '', false, true);
        setcookie('cookie_functional', 1,     $expiry, '/', '', false, true);
        setcookie('cookie_analytics',  1,     $expiry, '/', '', false, true);
    } elseif ($_GET['action'] === 'reject_all') {
        setcookie('cookie_consent',    'set', $expiry, '/', '', false, true);
        setcookie('cookie_functional', 0,     $expiry, '/', '', false, true);
        setcookie('cookie_analytics',  0,     $expiry, '/', '', false, true);
    }
    header("Location: cookie-preferences.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Cookie Preferences - Bite by Bite</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#fff8f0;font-family:Arial,Helvetica,sans-serif;color:#333;}
header{background:#fff1b5;padding:15px 40px;display:flex;justify-content:space-between;align-items:center;}
header h1{color:#634b49;font-size:24px;}
nav a{margin-left:20px;text-decoration:none;color:#634b49;font-weight:bold;}
nav a:hover{text-decoration:underline;}

.container{max-width:760px;margin:40px auto;padding:0 22px;}
.page-title{font-size:26px;color:#634b49;margin-bottom:8px;}
.page-sub{font-size:14px;color:#666;margin-bottom:28px;line-height:1.6;}

.quick-actions{display:flex;gap:12px;margin-bottom:28px;flex-wrap:wrap;}
.btn-accept{background:#634b49;color:white;border:none;padding:11px 24px;border-radius:8px;font-weight:bold;font-size:14px;cursor:pointer;text-decoration:none;display:inline-block;}
.btn-accept:hover{background:#43302e;}
.btn-reject{background:#f0e8e0;color:#634b49;border:none;padding:11px 24px;border-radius:8px;font-weight:bold;font-size:14px;cursor:pointer;text-decoration:none;display:inline-block;}
.btn-reject:hover{background:#e0d0c8;}

.success-msg{background:#e6f9e6;color:#2e7d32;padding:12px 16px;border-radius:8px;font-size:14px;margin-bottom:22px;display:flex;align-items:center;gap:8px;}

.cookie-card{background:white;border-radius:12px;box-shadow:0 4px 14px rgba(0,0,0,0.07);padding:22px;margin-bottom:16px;}
.cookie-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;}
.cookie-name{font-size:16px;font-weight:bold;color:#634b49;}
.cookie-badge{font-size:12px;padding:3px 10px;border-radius:20px;font-weight:bold;}
.badge-required{background:#e3f2fd;color:#1565c0;}
.cookie-desc{font-size:14px;color:#555;line-height:1.6;margin-bottom:14px;}
.cookie-examples{font-size:13px;color:#888;margin-bottom:14px;}
.cookie-examples strong{color:#634b49;}

/* Toggle switch */
.toggle-wrap{display:flex;align-items:center;gap:10px;}
.toggle-label{font-size:13px;color:#634b49;font-weight:bold;}
.toggle{position:relative;width:46px;height:24px;}
.toggle input{opacity:0;width:0;height:0;}
.toggle-slider{position:absolute;cursor:pointer;inset:0;background:#ccc;border-radius:24px;transition:background 0.2s;}
.toggle-slider:before{position:absolute;content:"";height:18px;width:18px;left:3px;bottom:3px;background:white;border-radius:50%;transition:transform 0.2s;}
input:checked + .toggle-slider{background:#634b49;}
input:checked + .toggle-slider:before{transform:translateX(22px);}
input:disabled + .toggle-slider{opacity:0.6;cursor:not-allowed;}

.divider{height:1px;background:#f0e8e0;margin:14px 0;}
.save-btn{background:#fff1b5;color:#634b49;border:none;padding:11px 28px;border-radius:8px;font-weight:bold;font-size:15px;cursor:pointer;margin-top:8px;}
.save-btn:hover{background:#c1dbe8;}

.info-section{background:white;border-radius:12px;box-shadow:0 4px 14px rgba(0,0,0,0.07);padding:22px;margin-top:28px;}
.info-section h3{font-size:16px;color:#634b49;margin-bottom:12px;}
.info-section p{font-size:14px;color:#555;line-height:1.7;margin-bottom:10px;}
.info-section a{color:#634b49;font-weight:bold;}

footer{background:#43302e;color:white;text-align:center;padding:20px;margin-top:40px;}
footer p{margin:0;color:white;}
@media(max-width:600px){header{padding:15px 20px;}.quick-actions{flex-direction:column;}}
</style>
</head>
<body>

<header>
  <h1>Bite by Bite - Mumbai</h1>
  <nav>
    <a href="index.php">Home</a>
    <?php if(isset($_SESSION['user'])): ?>
      <a href="logout.php">Logout</a>
    <?php else: ?>
      <a href="login.php">Login</a>
      <a href="register.php">Register</a>
    <?php endif; ?>
  </nav>
</header>

<div class="container">

  <h1 class="page-title">🍪 Cookie Preferences</h1>
  <p class="page-sub">
    We use cookies to make Bite by Bite work properly and to improve your experience.
    You can choose which cookies you allow below. Essential cookies are always on as the site
    cannot function without them. Your preferences are saved for one year.
  </p>

  <?php if ($saved): ?>
    <div class="success-msg">✅ Your cookie preferences have been saved.</div>
  <?php endif; ?>

  <!-- Quick actions -->
  <div class="quick-actions">
    <a href="?action=accept_all" class="btn-accept">Accept All Cookies</a>
    <a href="?action=reject_all" class="btn-reject">Essential Only</a>
  </div>

  <!-- Cookie settings form -->
  <form method="POST" action="cookie-preferences.php">

    <!-- Essential -->
    <div class="cookie-card">
      <div class="cookie-header">
        <span class="cookie-name">Essential Cookies</span>
        <span class="cookie-badge badge-required">Always active</span>
      </div>
      <p class="cookie-desc">These cookies are required for the website to function and cannot be switched off. They are set in response to actions you take, such as logging in or filling in forms.</p>
      <p class="cookie-examples"><strong>Examples:</strong> Login session, CSRF protection, cookie consent record.</p>
      <div class="toggle-wrap">
        <span class="toggle-label">Always on</span>
        <label class="toggle"><input type="checkbox" checked disabled><span class="toggle-slider"></span></label>
      </div>
    </div>

    <div class="divider"></div>

    <!-- Functional -->
    <div class="cookie-card">
      <div class="cookie-header">
        <span class="cookie-name">Functional Cookies</span>
      </div>
      <p class="cookie-desc">These cookies enable enhanced functionality and personalisation such as remembering your preferences, language settings, and which trails you've recently viewed.</p>
      <p class="cookie-examples"><strong>Examples:</strong> Saved trails, recent searches, preferred filters.</p>
      <div class="toggle-wrap">
        <span class="toggle-label"><?= $functional ? 'Enabled' : 'Disabled' ?></span>
        <label class="toggle">
          <input type="checkbox" name="functional" id="functionalToggle" <?= $functional ? 'checked' : '' ?> onchange="this.previousElementSibling.innerText=this.checked?'Enabled':'Disabled'">
          <span class="toggle-slider"></span>
        </label>
      </div>
    </div>

    <div class="divider"></div>

    <!-- Analytics -->
    <div class="cookie-card">
      <div class="cookie-header">
        <span class="cookie-name">Analytics Cookies</span>
      </div>
      <p class="cookie-desc">These cookies help us understand how visitors interact with Bite by Bite by collecting and reporting information anonymously. This helps us improve the site.</p>
      <p class="cookie-examples"><strong>Examples:</strong> Page views, session duration, most visited trails.</p>
      <div class="toggle-wrap">
        <span class="toggle-label"><?= $analytics ? 'Enabled' : 'Disabled' ?></span>
        <label class="toggle">
          <input type="checkbox" name="analytics" id="analyticsToggle" <?= $analytics ? 'checked' : '' ?> onchange="this.previousElementSibling.innerText=this.checked?'Enabled':'Disabled'">
          <span class="toggle-slider"></span>
        </label>
      </div>
    </div>

    <button type="submit" class="save-btn">Save My Preferences</button>
  </form>

  <!-- Info section -->
  <div class="info-section">
    <h3>More about cookies</h3>
    <p>Cookies are small text files stored on your device when you visit a website. They help the site remember information about your visit, making your next visit easier and the site more useful to you.</p>
    <p>We do not sell your data to third parties. For full details on how we handle your data, please refer to our Privacy Policy.</p>
    <p>You can also manage cookies directly in your browser settings. Note that blocking all cookies may affect the functionality of this site.</p>
    <p>Your current status: <strong><?= $consentGiven ? '✅ Preferences saved' : '⚠️ No preferences set yet' ?></strong></p>
  </div>

</div>

<footer>
  <p>© 2026 Bite by Bite - Mumbai | Created by Shreya Sakala, Gayatri Bedekar & Palak Sanklecha
  &nbsp;·&nbsp; <a href="cookie-preferences.php" style="color:#fff1b5;">Cookie Preferences</a></p>
</footer>

</body>
</html>
