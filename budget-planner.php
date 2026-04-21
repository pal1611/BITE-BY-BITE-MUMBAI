<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user'])) { header("Location: login.php"); exit(); }
$user   = $_SESSION['user'];
$budget = intval($_GET['budget'] ?? 0);
$type   = $_GET['type']   ?? '';

function parseMax($range) {
    preg_match_all('/\d+/', $range ?? '', $m);
    return !empty($m[0]) ? max($m[0]) : 9999;
}

$trailResults = [];
$spotResults  = [];

if ($budget > 0) {
    // Trails — filter in PHP after fetching all
    $trails = $conn->query("SELECT * FROM trails ORDER BY name")->fetch_all(MYSQLI_ASSOC);
    foreach ($trails as $t) {
        if (parseMax($t['cost']) <= $budget)
            $trailResults[] = $t;
    }

    // Spots — filter by type in SQL if selected, then by price in PHP
    if ($type && in_array($type, ['veg','nonveg','both'])) {
        $stmt = $conn->prepare("
            SELECT fs.*, t.name AS trail_name, t.id AS trail_id
            FROM food_spots fs
            JOIN trails t ON fs.trail_id = t.id
            WHERE fs.type = ?
            ORDER BY fs.name
        ");
        $stmt->bind_param("s", $type);
    } else {
        $stmt = $conn->prepare("
            SELECT fs.*, t.name AS trail_name, t.id AS trail_id
            FROM food_spots fs
            JOIN trails t ON fs.trail_id = t.id
            ORDER BY fs.name
        ");
    }
    $stmt->execute();
    $allSpots = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($allSpots as $s) {
        if (parseMax($s['price_range']) <= $budget)
            $spotResults[] = $s;
    }
}

$typeLabel = ['veg'=>'🟢 Veg','nonveg'=>'🔴 Non-Veg','both'=>'🟡 Veg & Non-Veg'];
$typeClass = ['veg'=>'badge-veg','nonveg'=>'badge-nonveg','both'=>'badge-both'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Budget Planner - Bite by Bite</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#fff8f0;font-family:Arial,Helvetica,sans-serif;color:#333;min-height:100vh;display:flex;flex-direction:column;}
<?php include 'header_styles.php'; ?>

.planner-hero{background:linear-gradient(135deg,#634b49,#8b6b68);padding:40px 20px;text-align:center;color:white;}
.planner-hero h2{font-size:26px;margin-bottom:8px;}
.planner-hero p{font-size:14px;opacity:0.85;margin-bottom:24px;}
.budget-form{background:rgba(255,255,255,0.15);border-radius:16px;padding:24px;max-width:560px;margin:0 auto;}
.budget-row{display:flex;align-items:center;gap:12px;flex-wrap:wrap;justify-content:center;margin-bottom:16px;}
.budget-row label{font-size:14px;font-weight:bold;color:white;min-width:60px;text-align:right;}
.budget-slider{flex:1;min-width:200px;accent-color:#fff1b5;}
.budget-display{font-size:22px;font-weight:bold;color:#fff1b5;min-width:80px;text-align:left;}
.type-group{display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin-bottom:16px;}
.type-btn{padding:8px 18px;border-radius:20px;border:2px solid rgba(255,255,255,0.4);background:transparent;color:white;font-size:13px;font-weight:bold;cursor:pointer;transition:all 0.2s;}
.type-btn:hover,.type-btn.selected{background:rgba(255,255,255,0.2);border-color:white;}
.search-btn{display:block;margin:0 auto;padding:12px 32px;background:#fff1b5;color:#634b49;border:none;border-radius:25px;font-weight:bold;font-size:15px;cursor:pointer;transition:background 0.2s;}
.search-btn:hover{background:#c1dbe8;}

.main-content{flex:1;}
.container{max-width:900px;margin:28px auto;padding:0 20px;}
.section-title{font-size:18px;color:#634b49;font-weight:bold;margin-bottom:14px;padding-bottom:8px;border-bottom:2px solid #fff1b5;display:flex;justify-content:space-between;align-items:center;}
.result-count{font-size:13px;color:#888;font-weight:normal;}

.trail-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;margin-bottom:28px;}
.trail-card{background:white;padding:20px;border-radius:12px;box-shadow:0 4px 14px rgba(0,0,0,0.07);}
.trail-card h3{color:#634b49;font-size:15px;margin-bottom:6px;}
.trail-card p{font-size:13px;color:#666;margin-bottom:10px;line-height:1.5;}
.trail-meta-row{font-size:12px;color:#888;margin-bottom:10px;}
.card-bottom{display:flex;justify-content:space-between;align-items:center;}

.spot-card{background:white;border-radius:12px;box-shadow:0 4px 14px rgba(0,0,0,0.07);margin-bottom:12px;display:flex;overflow:hidden;transition:transform 0.2s;}
.spot-card:hover{transform:translateY(-2px);}
.spot-img{width:110px;min-height:100px;object-fit:cover;flex-shrink:0;}
.spot-img-ph{width:110px;min-height:100px;background:linear-gradient(135deg,#fff1b5,#c1dbe8);display:flex;align-items:center;justify-content:center;font-size:26px;flex-shrink:0;}
.spot-body{padding:14px;flex:1;}
.spot-body h3{color:#634b49;font-size:15px;margin-bottom:4px;}
.spot-meta{font-size:12px;color:#888;margin-bottom:8px;}
.tags{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px;}
.badge{padding:3px 10px;border-radius:20px;font-size:11px;font-weight:bold;}
.badge-veg{background:#e6f9e6;color:#2e7d32;}
.badge-nonveg{background:#fdecea;color:#b71c1c;}
.badge-both{background:#fff8e1;color:#f57f17;}
.badge-cost{background:#f3e5f5;color:#6a1b9a;}

.btn{background:#fff1b5;color:#634b49;padding:8px 18px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:13px;display:inline-block;transition:background 0.2s;}
.btn:hover{background:#c1dbe8;}
.btn-sm{background:#fff1b5;color:#634b49;padding:6px 14px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:12px;display:inline-block;transition:background 0.2s;}
.btn-sm:hover{background:#c1dbe8;}

.empty{color:#aaa;font-style:italic;font-size:14px;padding:16px 0;}
.prompt-state{text-align:center;padding:50px 20px;color:#aaa;}
.prompt-state .icon{font-size:44px;margin-bottom:14px;}

footer{background:#43302e;color:white;text-align:center;padding:20px;margin-top:auto;}
footer p{margin:0;color:white;}
@media(max-width:600px){header{padding:15px 20px;}.spot-card{flex-direction:column;}.spot-img,.spot-img-ph{width:100%;height:130px;}}
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="planner-hero">
  <h2>💰 Budget Planner</h2>
  <p>Set your budget and we'll show you trails and spots that fit</p>

  <form class="budget-form" method="GET" action="budget-planner.php">
    <div class="budget-row">
      <label>Budget</label>
      <input type="range" name="budget" class="budget-slider" id="budgetSlider"
             min="50" max="1000" step="50"
             value="<?= $budget ?: 300 ?>"
             oninput="document.getElementById('bdisplay').innerText='₹'+this.value;document.getElementById('bhidden').value=this.value;">
      <span class="budget-display" id="bdisplay">₹<?= $budget ?: 300 ?></span>
      <input type="hidden" name="budget" id="bhidden" value="<?= $budget ?: 300 ?>">
    </div>

    <div class="budget-row" style="flex-direction:column;gap:8px;">
      <label style="text-align:center;">Food type</label>
      <div class="type-group">
        <?php foreach ([''=>'All','veg'=>'🟢 Veg','nonveg'=>'🔴 Non-Veg','both'=>'🟡 Both'] as $val=>$lbl): ?>
          <button type="button" class="type-btn <?= $type===$val?'selected':'' ?>"
                  onclick="selectType('<?= $val ?>',this)">
            <?= $lbl ?>
          </button>
        <?php endforeach; ?>
      </div>
      <input type="hidden" name="type" id="typeInput" value="<?= htmlspecialchars($type) ?>">
    </div>

    <button type="submit" class="search-btn">Find Options</button>
  </form>
</div>

<div class="main-content">
<div class="container">

<?php if ($budget > 0): ?>

  <!-- TRAILS -->
  <div class="section-title">
    Trails within ₹<?= $budget ?>
    <span class="result-count"><?= count($trailResults) ?> found</span>
  </div>

  <?php if (empty($trailResults)): ?>
    <p class="empty">No trails found within ₹<?= $budget ?>. Try increasing your budget.</p>
  <?php else: ?>
    <div class="trail-grid">
      <?php foreach ($trailResults as $t): ?>
        <div class="trail-card">
          <h3><?= htmlspecialchars($t['name']) ?></h3>
          <div class="trail-meta-row">📍 <?= htmlspecialchars($t['area']) ?> &nbsp;·&nbsp; ⏱ <?= htmlspecialchars($t['duration'] ?? '–') ?></div>
          <p><?= htmlspecialchars($t['description']) ?></p>
          <div class="card-bottom">
            <span style="font-weight:bold;color:#6a1b9a;font-size:14px;">💰 <?= htmlspecialchars($t['cost']) ?></span>
            <a href="trail-details.php?id=<?= $t['id'] ?>" class="btn">View Trail</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- SPOTS -->
  <div class="section-title" style="margin-top:10px;">
    Food Spots within ₹<?= $budget ?><?= $type ? ' &nbsp;·&nbsp; '.htmlspecialchars($typeLabel[$type]??'') : '' ?>
    <span class="result-count"><?= count($spotResults) ?> found</span>
  </div>

  <?php if (empty($spotResults)): ?>
    <p class="empty">No spots found within this budget<?= $type ? ' and type filter' : '' ?>. Try adjusting your filters.</p>
  <?php else: ?>
    <?php foreach ($spotResults as $s): ?>
      <div class="spot-card">
        <?php if ($s['image_url']): ?>
          <img class="spot-img" src="<?= htmlspecialchars($s['image_url']) ?>" alt="<?= htmlspecialchars($s['name']) ?>" onerror="this.outerHTML='<div class=spot-img-ph>🍴</div>'">
        <?php else: ?><div class="spot-img-ph">🍴</div><?php endif; ?>
        <div class="spot-body">
          <h3><?= htmlspecialchars($s['name']) ?></h3>
          <div class="spot-meta">📍 <?= htmlspecialchars($s['area']) ?> &nbsp;·&nbsp; <?= htmlspecialchars($s['trail_name']) ?></div>
          <div class="tags">
            <span class="badge <?= $typeClass[$s['type']] ?? 'badge-both' ?>"><?= $typeLabel[$s['type']] ?? $s['type'] ?></span>
            <span class="badge badge-cost">💰 <?= htmlspecialchars($s['price_range']) ?></span>
          </div>
          <a href="spot-details.php?id=<?= $s['id'] ?>" class="btn-sm">View Spot →</a>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

<?php else: ?>
  <div class="prompt-state">
    <div class="icon">💰</div>
    <p>Set your budget above and tap <strong>Find Options</strong> to see matching trails and spots.</p>
  </div>
<?php endif; ?>

</div><!-- container -->
</div><!-- main-content -->

<footer>
  <p>© 2026 Bite by Bite - Mumbai | Created by Shreya Sakala, Gayatri Bedekar & Palak Sanklecha
  &nbsp;·&nbsp; <a href="cookie-preferences.php" style="color:#fff1b5;">Cookie Preferences</a></p>
</footer>

<script>
function selectType(val, btn){
    document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    document.getElementById('typeInput').value = val;
}
// Keep slider and hidden input in sync
const slider = document.getElementById('budgetSlider');
if(slider){
    slider.addEventListener('input', function(){
        document.getElementById('bhidden').value = this.value;
        document.getElementById('bdisplay').innerText = '₹' + this.value;
    });
}
</script>
</body>
</html>
