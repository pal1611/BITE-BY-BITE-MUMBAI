<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user'])) { header("Location: login.php"); exit(); }
$user = $_SESSION['user'];

$q       = trim($_GET['q'] ?? '');
$filter  = $_GET['filter'] ?? 'all'; // all | trails | spots | dishes
$trails  = [];
$spots   = [];
$dishes  = [];

if ($q !== '') {
    $like = "%$q%";

    if ($filter === 'all' || $filter === 'trails') {
        $stmt = $conn->prepare("
            SELECT * FROM trails
            WHERE name LIKE ? OR area LIKE ? OR description LIKE ?
            ORDER BY name LIMIT 20
        ");
        $stmt->bind_param("sss", $like, $like, $like);
        $stmt->execute();
        $trails = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

    if ($filter === 'all' || $filter === 'spots') {
        $stmt = $conn->prepare("
            SELECT fs.*, t.name AS trail_name
            FROM food_spots fs
            JOIN trails t ON fs.trail_id = t.id
            WHERE fs.name LIKE ? OR fs.area LIKE ? OR fs.description LIKE ?
            ORDER BY fs.name LIMIT 20
        ");
        $stmt->bind_param("sss", $like, $like, $like);
        $stmt->execute();
        $spots = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

    if ($filter === 'all' || $filter === 'dishes') {
        $stmt = $conn->prepare("
            SELECT d.dish_name, fs.id AS spot_id, fs.name AS spot_name,
                   fs.area, fs.image_url, fs.type, fs.price_range, t.name AS trail_name
            FROM dishes d
            JOIN food_spots fs ON d.spot_id = fs.id
            JOIN trails t ON fs.trail_id = t.id
            WHERE d.dish_name LIKE ?
            ORDER BY d.dish_name LIMIT 30
        ");
        $stmt->bind_param("s", $like);
        $stmt->execute();
        $dishes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

$totalResults = count($trails) + count($spots) + count($dishes);
$typeLabel    = ['veg' => '🟢 Veg', 'nonveg' => '🔴 Non-Veg', 'both' => '🟡 Both'];
$typeClass    = ['veg' => 'badge-veg', 'nonveg' => 'badge-nonveg', 'both' => 'badge-both'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Search - Bite by Bite</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#fff8f0;font-family:Arial,Helvetica,sans-serif;color:#333;min-height:100vh;display:flex;flex-direction:column;}
<?php include 'header_styles.php'; ?>

.search-hero{background:linear-gradient(135deg,#634b49,#8b6b68);padding:36px 20px;text-align:center;color:white;}
.search-hero h2{font-size:24px;margin-bottom:8px;}
.search-hero p{font-size:14px;opacity:0.85;margin-bottom:18px;}
.search-form{display:flex;gap:8px;max-width:560px;margin:0 auto;flex-wrap:wrap;justify-content:center;}
.search-form input[type=text]{flex:1;min-width:220px;padding:12px 20px;border-radius:25px;border:none;font-size:15px;font-family:inherit;}
.search-form input[type=text]:focus{outline:none;}
.search-form button{padding:12px 24px;background:#fff1b5;color:#634b49;border:none;border-radius:25px;font-weight:bold;font-size:14px;cursor:pointer;transition:background 0.2s;}
.search-form button:hover{background:#c1dbe8;}

.filter-bar{background:white;border-bottom:1px solid #f0e8e0;padding:12px 20px;display:flex;gap:8px;justify-content:center;flex-wrap:wrap;}
.filter-pill{padding:7px 18px;border-radius:20px;border:1px solid #ddd;background:white;color:#634b49;font-size:13px;font-weight:bold;cursor:pointer;text-decoration:none;transition:all 0.2s;}
.filter-pill:hover,.filter-pill.active{background:#634b49;color:white;border-color:#634b49;}

.main-content{flex:1;}
.container{max-width:900px;margin:28px auto;padding:0 20px;}

.section-title{font-size:17px;color:#634b49;font-weight:bold;margin:24px 0 14px;padding-bottom:8px;border-bottom:2px solid #fff1b5;display:flex;justify-content:space-between;align-items:center;}
.result-count-small{font-size:13px;color:#888;font-weight:normal;}

/* Trail result */
.trail-result{background:white;border-radius:12px;box-shadow:0 4px 14px rgba(0,0,0,0.07);padding:18px 20px;margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;gap:14px;transition:transform 0.2s;}
.trail-result:hover{transform:translateY(-2px);}
.trail-result-info h3{font-size:15px;color:#634b49;margin-bottom:4px;}
.trail-result-info p{font-size:13px;color:#888;}

/* Spot result */
.spot-result{background:white;border-radius:12px;box-shadow:0 4px 14px rgba(0,0,0,0.07);margin-bottom:12px;display:flex;overflow:hidden;transition:transform 0.2s;}
.spot-result:hover{transform:translateY(-2px);}
.spot-img{width:100px;min-height:90px;object-fit:cover;flex-shrink:0;}
.spot-img-ph{width:100px;min-height:90px;background:linear-gradient(135deg,#fff1b5,#c1dbe8);display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;}
.spot-result-body{padding:14px;flex:1;}
.spot-result-body h3{font-size:15px;color:#634b49;margin-bottom:4px;}
.spot-result-meta{font-size:12px;color:#888;margin-bottom:8px;}
.tags{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px;}
.badge{padding:3px 10px;border-radius:20px;font-size:11px;font-weight:bold;}
.badge-veg{background:#e6f9e6;color:#2e7d32;}
.badge-nonveg{background:#fdecea;color:#b71c1c;}
.badge-both{background:#fff8e1;color:#f57f17;}
.badge-cost{background:#f3e5f5;color:#6a1b9a;}

/* Dish result */
.dish-result{background:white;border-radius:12px;box-shadow:0 4px 14px rgba(0,0,0,0.07);padding:14px 18px;margin-bottom:10px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;transition:transform 0.2s;}
.dish-result:hover{transform:translateY(-2px);}
.dish-highlight{font-size:13px;font-weight:bold;color:#634b49;background:#fff1b5;padding:3px 12px;border-radius:20px;display:inline-block;margin-bottom:4px;}
.dish-result-info p{font-size:13px;color:#888;}

.btn{background:#fff1b5;color:#634b49;padding:8px 16px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:13px;display:inline-block;white-space:nowrap;transition:background 0.2s;}
.btn:hover{background:#c1dbe8;}
.btn-sm{background:#fff1b5;color:#634b49;padding:6px 13px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:12px;display:inline-block;transition:background 0.2s;}
.btn-sm:hover{background:#c1dbe8;}

.empty-state{text-align:center;padding:50px 20px;color:#aaa;}
.empty-state .icon{font-size:44px;margin-bottom:14px;}
.empty-state p{font-size:15px;}
.prompt-state{text-align:center;padding:60px 20px;color:#aaa;}
.prompt-state .icon{font-size:48px;margin-bottom:16px;}
.prompt-state p{font-size:15px;}

footer{background:#43302e;color:white;text-align:center;padding:20px;margin-top:auto;}
footer p{margin:0;color:white;}
@media(max-width:600px){header{padding:15px 20px;}.spot-result{flex-direction:column;}.spot-img,.spot-img-ph{width:100%;height:120px;}}
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="search-hero">
  <h2>🔍 Search Bite by Bite</h2>
  <p>Find trails, food spots, and dishes all in one place</p>
  <form class="search-form" method="GET" action="search.php">
    <input type="text" name="q" placeholder="Search trails, spots, dishes..."
           value="<?= htmlspecialchars($q) ?>" autofocus>
    <input type="hidden" name="filter" id="filterHidden" value="<?= htmlspecialchars($filter) ?>">
    <button type="submit">Search</button>
  </form>
</div>

<!-- Filter pills -->
<div class="filter-bar">
  <?php
  $pills = ['all' => 'All', 'trails' => '🗺️ Trails', 'spots' => '🍴 Spots', 'dishes' => '🍽️ Dishes'];
  foreach ($pills as $val => $lbl):
    $url = '?q='.urlencode($q).'&filter='.$val;
  ?>
    <a href="<?= $url ?>" class="filter-pill <?= $filter===$val?'active':'' ?>"><?= $lbl ?></a>
  <?php endforeach; ?>
</div>

<div class="main-content">
<div class="container">

<?php if ($q === ''): ?>
  <div class="prompt-state">
    <div class="icon">🔍</div>
    <p>Type something above to search across all trails, spots and dishes.</p>
  </div>

<?php elseif ($totalResults === 0): ?>
  <div class="empty-state">
    <div class="icon">😕</div>
    <p>No results found for "<strong><?= htmlspecialchars($q) ?></strong>".<br>Try a different keyword or filter.</p>
  </div>

<?php else: ?>

  <!-- TRAILS -->
  <?php if (!empty($trails)): ?>
    <div class="section-title">
      🗺️ Trails
      <span class="result-count-small"><?= count($trails) ?> found</span>
    </div>
    <?php foreach ($trails as $t): ?>
      <div class="trail-result">
        <div class="trail-result-info">
          <h3><?= htmlspecialchars($t['name']) ?></h3>
          <p>📍 <?= htmlspecialchars($t['area']) ?> &nbsp;·&nbsp; 💰 <?= htmlspecialchars($t['cost']) ?> &nbsp;·&nbsp; ⏱ <?= htmlspecialchars($t['duration']) ?></p>
        </div>
        <a href="trail-details.php?id=<?= $t['id'] ?>" class="btn">View Trail</a>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <!-- SPOTS -->
  <?php if (!empty($spots)): ?>
    <div class="section-title">
      🍴 Food Spots
      <span class="result-count-small"><?= count($spots) ?> found</span>
    </div>
    <?php foreach ($spots as $s): ?>
      <div class="spot-result">
        <?php if ($s['image_url']): ?>
          <img class="spot-img" src="<?= htmlspecialchars($s['image_url']) ?>" alt="<?= htmlspecialchars($s['name']) ?>"
               onerror="this.outerHTML='<div class=spot-img-ph>🍴</div>'">
        <?php else: ?><div class="spot-img-ph">🍴</div><?php endif; ?>
        <div class="spot-result-body">
          <h3><?= htmlspecialchars($s['name']) ?></h3>
          <div class="spot-result-meta">📍 <?= htmlspecialchars($s['area']) ?> &nbsp;·&nbsp; <?= htmlspecialchars($s['trail_name']) ?></div>
          <div class="tags">
            <span class="badge <?= $typeClass[$s['type']] ?? 'badge-both' ?>"><?= $typeLabel[$s['type']] ?? $s['type'] ?></span>
            <span class="badge badge-cost">💰 <?= htmlspecialchars($s['price_range']) ?></span>
          </div>
          <a href="spot-details.php?id=<?= $s['id'] ?>" class="btn-sm">View Spot →</a>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <!-- DISHES -->
  <?php if (!empty($dishes)): ?>
    <div class="section-title">
      🍽️ Dishes
      <span class="result-count-small"><?= count($dishes) ?> found</span>
    </div>
    <?php foreach ($dishes as $d): ?>
      <div class="dish-result">
        <div class="dish-result-info">
          <span class="dish-highlight">🍽️ <?= htmlspecialchars($d['dish_name']) ?></span>
          <p><?= htmlspecialchars($d['spot_name']) ?> &nbsp;·&nbsp; 📍 <?= htmlspecialchars($d['area']) ?> &nbsp;·&nbsp; <?= htmlspecialchars($d['trail_name']) ?></p>
        </div>
        <a href="spot-details.php?id=<?= $d['spot_id'] ?>" class="btn-sm">View Spot →</a>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

<?php endif; ?>

</div>
</div>

<footer>
  <p>© 2026 Bite by Bite - Mumbai | Created by Shreya Sakala, Gayatri Bedekar & Palak Sanklecha
  &nbsp;·&nbsp; <a href="cookie-preferences.php" style="color:#fff1b5;">Cookie Preferences</a></p>
</footer>
</body>
</html>
