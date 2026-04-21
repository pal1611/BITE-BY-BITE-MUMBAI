<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user   = $_SESSION['user'];
$search = trim($_GET['q'] ?? '');
$results = [];

if ($search) {
    $stmt = $conn->prepare("
        SELECT d.dish_name, fs.id AS spot_id, fs.name AS spot_name,
               fs.area, fs.type, fs.price_range, fs.image_url,
               t.name AS trail_name, t.id AS trail_id
        FROM dishes d
        JOIN food_spots fs ON d.spot_id = fs.id
        JOIN trails t ON fs.trail_id = t.id
        WHERE d.dish_name LIKE ?
        ORDER BY d.dish_name, fs.name
    ");
    $like = "%$search%";
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Also fetch popular dishes for suggestions
$popular = $conn->query("
    SELECT dish_name, COUNT(*) as cnt
    FROM dishes
    GROUP BY dish_name
    ORDER BY cnt DESC
    LIMIT 12
")->fetch_all(MYSQLI_ASSOC);

$typeLabel = ['veg' => '🟢 Veg', 'nonveg' => '🔴 Non-Veg', 'both' => '🟡 Veg & Non-Veg'];
$typeClass = ['veg' => 'badge-veg', 'nonveg' => 'badge-nonveg', 'both' => 'badge-both'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Search Dishes - Bite by Bite</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#fff8f0; font-family:Arial,Helvetica,sans-serif; color:#333; }
<?php include 'header_styles.php'; ?>

.search-hero { background:linear-gradient(135deg,#634b49,#8b6b68); padding:40px 20px; text-align:center; color:white; }
.search-hero h2 { font-size:26px; margin-bottom:8px; }
.search-hero p  { font-size:14px; opacity:0.85; margin-bottom:20px; }
.search-form { display:inline-flex; gap:8px; align-items:center; max-width:500px; width:100%; }
.search-form input { flex:1; padding:12px 20px; border-radius:25px; border:none; font-size:15px; }
.search-form input:focus { outline:none; }
.search-form button { padding:12px 24px; background:#fff1b5; color:#634b49; border:none; border-radius:25px; font-weight:bold; cursor:pointer; font-size:14px; }
.search-form button:hover { background:#c1dbe8; }

.container { max-width:900px; margin:30px auto; padding:0 20px; }

.popular-wrap { margin-bottom:28px; }
.popular-wrap h3 { font-size:15px; color:#634b49; margin-bottom:12px; font-weight:bold; }
.dish-pills { display:flex; flex-wrap:wrap; gap:8px; }
.dish-pill { background:white; border:1px solid #ddd; padding:6px 16px; border-radius:20px; font-size:13px; color:#634b49; text-decoration:none; transition:all 0.2s; }
.dish-pill:hover { background:#fff1b5; border-color:#e0d070; }
.dish-pill.active { background:#634b49; color:white; border-color:#634b49; }

.results-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; }
.results-header h3 { font-size:16px; color:#634b49; font-weight:bold; }
.result-count { font-size:13px; color:#888; }

.result-card { background:white; border-radius:12px; box-shadow:0 4px 14px rgba(0,0,0,0.07); margin-bottom:14px; display:flex; overflow:hidden; transition:transform 0.2s; }
.result-card:hover { transform:translateY(-2px); }
.result-img { width:110px; min-height:100px; object-fit:cover; flex-shrink:0; }
.result-img-ph { width:110px; min-height:100px; background:linear-gradient(135deg,#fff1b5,#c1dbe8); display:flex; align-items:center; justify-content:center; font-size:28px; flex-shrink:0; }
.result-body { padding:14px; flex:1; }
.dish-highlight { font-size:12px; font-weight:bold; color:#634b49; background:#fff1b5; padding:2px 10px; border-radius:20px; display:inline-block; margin-bottom:6px; }
.result-body h3 { font-size:15px; color:#634b49; margin-bottom:4px; }
.result-meta { font-size:12px; color:#888; margin-bottom:8px; }
.tags { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:8px; }
.badge { padding:3px 10px; border-radius:20px; font-size:11px; font-weight:bold; }
.badge-veg    { background:#e6f9e6; color:#2e7d32; }
.badge-nonveg { background:#fdecea; color:#b71c1c; }
.badge-both   { background:#fff8e1; color:#f57f17; }
.badge-cost   { background:#f3e5f5; color:#6a1b9a; }
.btn-sm { background:#fff1b5; color:#634b49; padding:6px 14px; border-radius:6px; text-decoration:none; font-weight:bold; font-size:12px; }
.btn-sm:hover { background:#c1dbe8; }

.empty-state { text-align:center; padding:50px 20px; color:#aaa; }
.empty-state .icon { font-size:44px; margin-bottom:14px; }
.empty-state p { font-size:15px; }

footer { background:#43302e; color:white; text-align:center; padding:20px; margin-top:40px; }
footer p { margin:0; color:white; }
@media(max-width:600px){ header{padding:15px 20px;} .result-card{flex-direction:column;} .result-img,.result-img-ph{width:100%;height:140px;} }
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="search-hero">
  <h2>🍽️ Find a Dish</h2>
  <p>Search by dish name and discover which spots serve it</p>
  <form class="search-form" method="GET" action="dish-search.php">
    <input type="text" name="q" placeholder="e.g. Misal Pav, Pani Puri, Vada Pav..." value="<?= htmlspecialchars($search) ?>" autofocus>
    <button type="submit">Search</button>
  </form>
</div>

<div class="container">

  <!-- Popular dishes -->
  <?php if (!$search && !empty($popular)): ?>
  <div class="popular-wrap">
    <h3>Popular dishes</h3>
    <div class="dish-pills">
      <?php foreach ($popular as $d): ?>
        <a href="?q=<?= urlencode($d['dish_name']) ?>" class="dish-pill"><?= htmlspecialchars($d['dish_name']) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Results -->
  <?php if ($search): ?>
    <div class="results-header">
      <h3>Results for "<?= htmlspecialchars($search) ?>"</h3>
      <span class="result-count"><?= count($results) ?> spot<?= count($results) !== 1 ? 's' : '' ?> found</span>
    </div>

    <?php if (empty($results)): ?>
      <div class="empty-state">
        <div class="icon">🔍</div>
        <p>No spots found serving "<?= htmlspecialchars($search) ?>".<br>Try a different dish name.</p>
      </div>
    <?php else: ?>
      <?php foreach ($results as $r): ?>
        <div class="result-card">
          <?php if ($r['image_url']): ?>
            <img class="result-img" src="<?= htmlspecialchars($r['image_url']) ?>" alt="<?= htmlspecialchars($r['spot_name']) ?>" onerror="this.outerHTML='<div class=result-img-ph>🍴</div>'">
          <?php else: ?><div class="result-img-ph">🍴</div><?php endif; ?>
          <div class="result-body">
            <span class="dish-highlight">🍽️ <?= htmlspecialchars($r['dish_name']) ?></span>
            <h3><?= htmlspecialchars($r['spot_name']) ?></h3>
            <div class="result-meta">📍 <?= htmlspecialchars($r['area']) ?> &nbsp;·&nbsp; on <?= htmlspecialchars($r['trail_name']) ?></div>
            <div class="tags">
              <span class="badge <?= $typeClass[$r['type']] ?? 'badge-both' ?>"><?= $typeLabel[$r['type']] ?? $r['type'] ?></span>
              <span class="badge badge-cost">💰 <?= htmlspecialchars($r['price_range']) ?></span>
            </div>
            <a href="spot-details.php?id=<?= $r['spot_id'] ?>" class="btn-sm">View Spot →</a>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

  <?php elseif (empty($popular)): ?>
    <div class="empty-state">
      <div class="icon">🍴</div>
      <p>Type a dish name above to find which spots serve it.</p>
    </div>
  <?php endif; ?>

</div>

<footer><p>© 2026 Bite by Bite - Mumbai | Created by Shreya Sakala, Gayatri Bedekar & Palak Sanklecha</p></footer>
</body>
</html>
