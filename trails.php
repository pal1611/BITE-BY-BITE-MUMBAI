<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user   = $_SESSION['user'];
$search = trim($_GET['search'] ?? '');

// Fetch trails, optionally filtered by search
if ($search) {
    $stmt = $conn->prepare("SELECT * FROM trails WHERE name LIKE ? OR area LIKE ? OR description LIKE ? ORDER BY name");
    $like = "%$search%";
    $stmt->bind_param("sss", $like, $like, $like);
} else {
    $stmt = $conn->prepare("SELECT * FROM trails ORDER BY name");
}
$stmt->execute();
$trails = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Food Trails - Bite by Bite</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#fff8f0; font-family:Arial,Helvetica,sans-serif; color:#333; }
header { background:#fff1b5; padding:15px 40px; display:flex; justify-content:space-between; align-items:center; position:sticky; top:0; z-index:100; box-shadow:0 2px 8px rgba(0,0,0,0.08); }
header h1 { color:#634b49; font-size:24px; }
.user-menu { position:relative; }
.user-menu-trigger { font-weight:bold; color:#634b49; font-size:14px; cursor:pointer; display:flex; align-items:center; gap:5px; background:none; border:none; padding:0; }
.user-menu-trigger:hover { color:#43302e; }
.dropdown { display:none; position:absolute; right:0; top:calc(100% + 10px); background:white; border-radius:12px; box-shadow:0 8px 28px rgba(0,0,0,0.13); min-width:210px; overflow:hidden; z-index:200; border:0.5px solid #e8ddd5; }
.dropdown.open { display:block; }
.dropdown-header { background:#fff1b5; padding:14px 18px; font-size:13px; color:#888; border-bottom:1px solid #e8ddd5; }
.dropdown-header strong { display:block; font-size:15px; color:#634b49; font-weight:bold; }
.dropdown a, .dropdown button { display:flex; align-items:center; gap:10px; padding:11px 18px; text-decoration:none; color:#333; font-size:14px; background:none; border:none; width:100%; text-align:left; cursor:pointer; transition:background 0.15s; }
.dropdown a:hover, .dropdown button:hover { background:#c1dbe8; }
.dropdown .divider { height:1px; background:#f0e8e0; margin:4px 0; }
.dropdown .logout-item { color:#b71c1c; }
.dropdown .logout-item:hover { background:#fdecea; }

.search-box { text-align:center; margin:24px 20px; }
.search-form { display:inline-flex; gap:8px; align-items:center; }
.search-form input { padding:10px 20px; width:320px; border-radius:25px; border:1px solid #ccc; font-size:15px; }
.search-form input:focus { outline:none; border-color:#634b49; }
.search-form button { padding:10px 20px; background:#fff1b5; border:none; border-radius:25px; color:#634b49; font-weight:bold; cursor:pointer; }
.search-form button:hover { background:#c1dbe8; }

.trails { padding:30px 20px; background:#e6f7ff; }
.trails h2 { text-align:center; color:#634b49; margin-bottom:30px; font-size:22px; }
.trail-container { display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:20px; max-width:1100px; margin:auto; }
.trail-card { background:white; padding:22px; border-radius:12px; box-shadow:0 5px 15px rgba(0,0,0,0.08); text-align:center; }
.trail-card h3 { color:#634b49; margin-bottom:6px; font-size:17px; }
.trail-card .meta { font-size:12px; color:#888; margin-bottom:10px; }
.trail-card p { font-size:14px; color:#555; margin-bottom:14px; line-height:1.6; }
.btn { background:#fff1b5; color:#634b49; padding:9px 20px; border-radius:6px; text-decoration:none; font-weight:bold; font-size:13px; display:inline-block; }
.btn:hover { background:#c1dbe8; }
.no-results { text-align:center; color:#aaa; font-size:15px; padding:40px; font-style:italic; }

footer { background:#43302e; color:white; text-align:center; padding:20px; margin-top:40px; }
footer p { margin:0; }
</style>
</head>
<body>

<?php  ?>
<?php include 'header.php'; ?>
<script>
</script>
<?php include 'cookie-banner.php'; ?>

<div class="search-box">
  <form class="search-form" method="GET" action="trails.php">
    <input type="text" name="search" placeholder="Search trails..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Search</button>
    <?php if ($search): ?><a href="trails.php" style="color:#888;font-size:13px;text-decoration:none;">Clear</a><?php endif; ?>
  </form>
</div>

<section class="trails">
  <h2>All Food Trails</h2>
  <div class="trail-container">
    <?php if (empty($trails)): ?>
      <div class="no-results" style="grid-column:1/-1;">No trails found for "<?= htmlspecialchars($search) ?>".</div>
    <?php else: ?>
      <?php foreach ($trails as $trail): ?>
        <div class="trail-card">
          <h3><?= htmlspecialchars($trail['name']) ?></h3>
          <div class="meta">📍 <?= htmlspecialchars($trail['area']) ?> &nbsp;·&nbsp; 💰 <?= htmlspecialchars($trail['cost']) ?> &nbsp;·&nbsp; ⏱ <?= htmlspecialchars($trail['duration']) ?></div>
          <p><?= htmlspecialchars($trail['description']) ?></p>
          <a href="trail-details.php?id=<?= $trail['id'] ?>" class="btn">View Trail</a>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<footer><p>© 2026 Bite by Bite - Mumbai | Created by Shreya Sakala, Gayatri Bedekar & Palak Sanklecha</p></footer>
</body>
</html>
