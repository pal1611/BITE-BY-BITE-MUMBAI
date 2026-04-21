<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user    = $_SESSION['user'];
$user_id = $user['id'];

// Fetch favourited spots
$favSpots = $conn->prepare("
    SELECT fs.*, t.name AS trail_name, t.id AS trail_id
    FROM favourites f
    JOIN food_spots fs ON f.spot_id = fs.id
    JOIN trails t ON fs.trail_id = t.id
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
");
$favSpots->bind_param("i", $user_id);
$favSpots->execute();
$favSpots = $favSpots->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch favourited trails
$favTrails = $conn->prepare("
    SELECT t.*
    FROM favourites f
    JOIN trails t ON f.trail_id = t.id
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
");
$favTrails->bind_param("i", $user_id);
$favTrails->execute();
$favTrails = $favTrails->get_result()->fetch_all(MYSQLI_ASSOC);

$totalFavs = count($favSpots) + count($favTrails);
$typeLabel = ['veg' => '🟢 Veg', 'nonveg' => '🔴 Non-Veg', 'both' => '🟡 Veg & Non-Veg'];
$typeClass = ['veg' => 'badge-veg', 'nonveg' => 'badge-nonveg', 'both' => 'badge-both'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Favourites - Bite by Bite</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#fff8f0; font-family:Arial,Helvetica,sans-serif; color:#333; }
header { background:#fff1b5; padding:15px 40px; display:flex; justify-content:space-between; align-items:center; position:relative; min-height:60px; }
header h1 { color:#634b49; font-size:24px; }
.user-menu{position:relative;}.user-menu-trigger{font-weight:bold;color:#634b49;font-size:14px;cursor:pointer;display:flex;align-items:center;gap:5px;background:none;border:none;padding:0;}.user-menu-trigger:hover{color:#43302e;}.dropdown{display:none;position:absolute;right:0;top:calc(100% + 10px);background:white;border-radius:12px;box-shadow:0 8px 28px rgba(0,0,0,0.13);min-width:210px;overflow:hidden;z-index:200;border:0.5px solid #e8ddd5;}.dropdown.open{display:block;}.dropdown-header{background:#fff1b5;padding:14px 18px;font-size:13px;color:#888;border-bottom:1px solid #e8ddd5;}.dropdown-header strong{display:block;font-size:15px;color:#634b49;font-weight:bold;}.dropdown a,.dropdown button{display:flex;align-items:center;gap:10px;padding:11px 18px;text-decoration:none;color:#333;font-size:14px;background:none;border:none;width:100%;text-align:left;cursor:pointer;transition:background 0.15s;}.dropdown a:hover,.dropdown button:hover{background:#c1dbe8;}.dropdown .divider{height:1px;background:#f0e8e0;margin:4px 0;}.dropdown .logout-item{color:#b71c1c;}.dropdown .logout-item:hover{background:#fdecea;}

.page-header { max-width:900px; margin:28px auto 0; padding:0 20px; display:flex; justify-content:space-between; align-items:center; }
.page-header h2 { color:#634b49; font-size:22px; }
.count-badge { background:#fff1b5; color:#634b49; padding:4px 14px; border-radius:20px; font-size:13px; font-weight:bold; border:1px solid #e0d070; }

.container { max-width:900px; margin:20px auto; padding:0 20px; }

.tabs { display:flex; gap:8px; margin-bottom:20px; }
.tab-pill { padding:7px 18px; border-radius:20px; border:1px solid #ddd; background:white; color:#888; font-size:13px; font-weight:bold; cursor:pointer; text-decoration:none; transition:all 0.2s; }
.tab-pill.active { background:#634b49; color:white; border-color:#634b49; }

.spot-card { background:white; border-radius:12px; box-shadow:0 5px 15px rgba(0,0,0,0.08); margin-bottom:14px; display:flex; overflow:hidden; transition:transform 0.2s; }
.spot-card:hover { transform:translateY(-2px); }
.spot-img { width:130px; min-height:110px; object-fit:cover; flex-shrink:0; }
.spot-img-placeholder { width:130px; min-height:110px; background:linear-gradient(135deg,#fff1b5,#c1dbe8); display:flex; align-items:center; justify-content:center; font-size:32px; flex-shrink:0; }
.spot-body { padding:16px; flex:1; }
.spot-body h3 { color:#634b49; font-size:16px; margin-bottom:6px; }
.spot-body p  { font-size:13px; color:#666; margin-bottom:10px; line-height:1.5; }
.spot-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }

.trail-card { background:white; border-radius:12px; box-shadow:0 5px 15px rgba(0,0,0,0.08); padding:20px; margin-bottom:14px; display:flex; justify-content:space-between; align-items:center; gap:14px; transition:transform 0.2s; }
.trail-card:hover { transform:translateY(-2px); }
.trail-left h3 { color:#634b49; font-size:16px; margin-bottom:6px; }
.trail-left p  { font-size:13px; color:#666; line-height:1.5; }
.trail-right   { display:flex; gap:10px; align-items:center; flex-shrink:0; }

.tags { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:10px; }
.badge { padding:3px 10px; border-radius:20px; font-size:12px; font-weight:bold; }
.badge-veg    { background:#e6f9e6; color:#2e7d32; }
.badge-nonveg { background:#fdecea; color:#b71c1c; }
.badge-both   { background:#fff8e1; color:#f57f17; }
.badge-area   { background:#e3f2fd; color:#1565c0; }
.badge-cost   { background:#f3e5f5; color:#6a1b9a; }

.btn { background:#fff1b5; color:#634b49; padding:8px 16px; border-radius:6px; text-decoration:none; font-weight:bold; font-size:13px; display:inline-block; }
.btn:hover { background:#c1dbe8; }

.fav-btn { background:#fff5f5; border:1px solid #e57373; border-radius:6px; padding:7px 12px; font-size:14px; cursor:pointer; color:#b71c1c; font-weight:bold; transition:all 0.2s; }
.fav-btn:hover { background:#fdecea; }

.empty-state { text-align:center; padding:60px 20px; color:#aaa; }
.empty-state .icon { font-size:48px; margin-bottom:16px; }
.empty-state p { font-size:15px; margin-bottom:20px; }

.toast { position:fixed; bottom:24px; left:50%; transform:translateX(-50%); background:#43302e; color:white; padding:10px 22px; border-radius:20px; font-size:13px; opacity:0; transition:opacity 0.3s; pointer-events:none; z-index:999; }
.toast.show { opacity:1; }

footer { background:#43302e; color:white; text-align:center; padding:20px; margin-top:40px; }
footer p { margin:0; color:white; }

@media(max-width:600px){
    .spot-card{flex-direction:column;} .spot-img,.spot-img-placeholder{width:100%;height:160px;}
    header{padding:15px 20px;} .trail-card{flex-direction:column;align-items:flex-start;}
}
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="page-header">
  <h2>❤️ My Favourites</h2>
  <span class="count-badge"><?= $totalFavs ?> saved</span>
</div>

<div class="container">

  <div class="tabs">
    <a href="?tab=spots"  class="tab-pill <?= (!isset($_GET['tab']) || $_GET['tab']==='spots')  ? 'active' : '' ?>">Food Spots (<?= count($favSpots) ?>)</a>
    <a href="?tab=trails" class="tab-pill <?= (isset($_GET['tab']) && $_GET['tab']==='trails') ? 'active' : '' ?>">Trails (<?= count($favTrails) ?>)</a>
  </div>

  <?php $activeTab = $_GET['tab'] ?? 'spots'; ?>

  <!-- SPOTS TAB -->
  <?php if ($activeTab === 'spots'): ?>
    <?php if (empty($favSpots)): ?>
      <div class="empty-state">
        <div class="icon">🍴</div>
        <p>No favourite spots saved yet.<br>Hit the ❤️ button on any food spot to save it here!</p>
        <a href="trails.php" class="btn">Explore Trails</a>
      </div>
    <?php else: ?>
      <?php foreach ($favSpots as $s): ?>
        <div class="spot-card" id="spot-<?= $s['id'] ?>">
          <?php if ($s['image_url']): ?>
            <img class="spot-img" src="<?= htmlspecialchars($s['image_url']) ?>" alt="<?= htmlspecialchars($s['name']) ?>" onerror="this.outerHTML='<div class=spot-img-placeholder>🍴</div>'">
          <?php else: ?><div class="spot-img-placeholder">🍴</div><?php endif; ?>
          <div class="spot-body">
            <h3><?= htmlspecialchars($s['name']) ?></h3>
            <p><?= htmlspecialchars($s['description']) ?></p>
            <div class="tags">
              <span class="badge <?= $typeClass[$s['type']] ?? 'badge-both' ?>"><?= $typeLabel[$s['type']] ?? $s['type'] ?></span>
              <span class="badge badge-area">📍 <?= htmlspecialchars($s['area']) ?></span>
              <span class="badge badge-cost">💰 <?= htmlspecialchars($s['price_range']) ?></span>
            </div>
            <div class="spot-actions">
              <a href="spot-details.php?id=<?= $s['id'] ?>" class="btn">View Spot →</a>
              <button class="fav-btn" onclick="removeFav('spot', <?= $s['id'] ?>, this)" title="Remove from favourites">❤️ Remove</button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

  <!-- TRAILS TAB -->
  <?php elseif ($activeTab === 'trails'): ?>
    <?php if (empty($favTrails)): ?>
      <div class="empty-state">
        <div class="icon">🗺️</div>
        <p>No favourite trails saved yet.<br>Hit the ❤️ button on any trail to save it here!</p>
        <a href="trails.php" class="btn">Explore Trails</a>
      </div>
    <?php else: ?>
      <?php foreach ($favTrails as $t): ?>
        <div class="trail-card" id="trail-<?= $t['id'] ?>">
          <div class="trail-left">
            <h3><?= htmlspecialchars($t['name']) ?></h3>
            <p><?= htmlspecialchars($t['description']) ?></p>
            <div class="tags" style="margin-top:8px;">
              <span class="badge badge-area">📍 <?= htmlspecialchars($t['area']) ?></span>
              <span class="badge badge-cost">💰 <?= htmlspecialchars($t['cost']) ?></span>
              <span class="badge" style="background:#e8f5e9;color:#2e7d32;">⏱ <?= htmlspecialchars($t['duration']) ?></span>
            </div>
          </div>
          <div class="trail-right">
            <a href="trail-details.php?id=<?= $t['id'] ?>" class="btn">View Trail</a>
            <button class="fav-btn" onclick="removeFav('trail', <?= $t['id'] ?>, this)" title="Remove from favourites">❤️ Remove</button>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  <?php endif; ?>

</div>

<div class="toast" id="toast"></div>
<footer><p>© 2026 Bite by Bite - Mumbai | Created by Shreya Sakala, Gayatri Bedekar & Palak Sanklecha</p></footer>

<script>
function showToast(msg){
    const t = document.getElementById("toast");
    t.innerText = msg; t.classList.add("show");
    setTimeout(() => t.classList.remove("show"), 2500);
}

function removeFav(type, id, btn){
    const body = new FormData();
    if(type === 'spot')  body.append('spot_id',  id);
    if(type === 'trail') body.append('trail_id', id);

    fetch('toggle_favourite.php', { method:'POST', body })
    .then(r => r.json())
    .then(data => {
        if(data.success && data.action === 'removed'){
            const card = document.getElementById(type + '-' + id);
            if(card) card.remove();
            showToast("Removed from favourites");
        }
    });
}
</script>
</body>
</html>
