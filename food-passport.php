<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user    = $_SESSION['user'];
$user_id = $user['id'];

// Fetch all spots grouped by trail, with check-in status
$stmt = $conn->prepare("
    SELECT fs.*, t.name AS trail_name, t.id AS trail_id, t.area AS trail_area,
           IF(c.id IS NOT NULL, 1, 0) AS visited
    FROM food_spots fs
    JOIN trails t ON fs.trail_id = t.id
    LEFT JOIN checkins c ON c.spot_id = fs.id AND c.user_id = ?
    ORDER BY t.name, fs.name
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$allSpots = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Group by trail
$byTrail = [];
foreach ($allSpots as $s) {
    $byTrail[$s['trail_id']]['name']  = $s['trail_name'];
    $byTrail[$s['trail_id']]['area']  = $s['trail_area'];
    $byTrail[$s['trail_id']]['spots'][] = $s;
}

// Stats
$totalSpots   = count($allSpots);
$visitedSpots = count(array_filter($allSpots, fn($s) => $s['visited']));
$totalTrails  = count($byTrail);
$completedTrails = 0;
foreach ($byTrail as $trail) {
    $done = count(array_filter($trail['spots'], fn($s) => $s['visited']));
    if ($done === count($trail['spots']) && count($trail['spots']) > 0) $completedTrails++;
}

// Badge tier
$pct = $totalSpots > 0 ? round(($visitedSpots / $totalSpots) * 100) : 0;
if ($pct === 100)     { $tier = 'Mumbai Food Legend';   $tierIcon = '👑'; $tierColor = '#f5a623'; }
elseif ($pct >= 75)   { $tier = 'Trail Connoisseur';    $tierIcon = '🏅'; $tierColor = '#c0a060'; }
elseif ($pct >= 50)   { $tier = 'Seasoned Explorer';    $tierIcon = '⭐'; $tierColor = '#634b49'; }
elseif ($pct >= 25)   { $tier = 'Hungry Adventurer';    $tierIcon = '🍴'; $tierColor = '#2e7d32'; }
else                  { $tier = 'Fresh off the Plate';  $tierIcon = '🌱'; $tierColor = '#888'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Food Passport - Bite by Bite</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#fff8f0;font-family:Arial,Helvetica,sans-serif;color:#333;}
<?php include 'header_styles.php'; ?>

/* PASSPORT COVER */
.passport-cover{
    background:linear-gradient(135deg,#43302e 0%,#634b49 50%,#8b6b68 100%);
    color:white; padding:40px 20px; text-align:center;
}
.passport-emblem{font-size:48px;margin-bottom:12px;}
.passport-title{font-size:11px;letter-spacing:4px;opacity:0.7;margin-bottom:6px;text-transform:uppercase;}
.passport-name{font-size:28px;font-weight:bold;margin-bottom:4px;}
.passport-subtitle{font-size:13px;opacity:0.75;margin-bottom:24px;}

/* TIER BADGE */
.tier-badge{
    display:inline-flex;align-items:center;gap:10px;
    background:rgba(255,255,255,0.15);border:2px solid rgba(255,255,255,0.3);
    border-radius:50px;padding:10px 24px;margin-bottom:24px;
}
.tier-icon{font-size:24px;}
.tier-text{text-align:left;}
.tier-label{font-size:11px;opacity:0.7;text-transform:uppercase;letter-spacing:2px;}
.tier-name{font-size:16px;font-weight:bold;}

/* PROGRESS */
.progress-wrap{max-width:400px;margin:0 auto;}
.progress-bar-bg{background:rgba(255,255,255,0.2);border-radius:20px;height:10px;overflow:hidden;margin-bottom:8px;}
.progress-bar-fill{height:100%;background:#fff1b5;border-radius:20px;transition:width 0.8s ease;}
.progress-label{font-size:13px;opacity:0.85;}

/* STATS STRIP */
.stats-strip{background:white;padding:20px;display:flex;justify-content:center;gap:0;border-bottom:2px solid #f0e8e0;}
.stat-item{text-align:center;padding:0 32px;border-right:1px solid #f0e8e0;}
.stat-item:last-child{border-right:none;}
.stat-num{font-size:28px;font-weight:bold;color:#634b49;}
.stat-lbl{font-size:12px;color:#888;margin-top:3px;}

.container{max-width:960px;margin:30px auto;padding:0 20px;}

/* TRAIL SECTION */
.trail-section{margin-bottom:32px;}
.trail-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;padding:14px 18px;background:white;border-radius:12px;box-shadow:0 4px 14px rgba(0,0,0,0.07);}
.trail-header-left h3{font-size:17px;color:#634b49;font-weight:bold;margin-bottom:3px;}
.trail-header-left p{font-size:12px;color:#888;}
.trail-progress-mini{text-align:right;}
.trail-progress-text{font-size:13px;font-weight:bold;color:#634b49;margin-bottom:4px;}
.trail-bar-bg{width:120px;height:6px;background:#f0e8e0;border-radius:20px;overflow:hidden;}
.trail-bar-fill{height:100%;background:#634b49;border-radius:20px;}
.complete-badge{background:#e6f9e6;color:#2e7d32;font-size:11px;font-weight:bold;padding:3px 10px;border-radius:20px;display:inline-block;margin-top:4px;}

/* STAMP GRID */
.stamp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:14px;}

.stamp{
    border-radius:12px;overflow:hidden;
    box-shadow:0 4px 14px rgba(0,0,0,0.08);
    position:relative;cursor:pointer;
    transition:transform 0.2s;background:white;
    text-decoration:none;color:inherit;display:block;
}
.stamp:hover{transform:translateY(-3px);}

.stamp-img{width:100%;height:110px;object-fit:cover;display:block;}
.stamp-img-ph{width:100%;height:110px;background:linear-gradient(135deg,#fff1b5,#c1dbe8);display:flex;align-items:center;justify-content:center;font-size:32px;}

.stamp-body{padding:10px 12px;}
.stamp-spot-name{font-size:13px;font-weight:bold;color:#634b49;line-height:1.3;margin-bottom:3px;}
.stamp-area{font-size:11px;color:#888;}

/* VISITED OVERLAY */
.stamp.visited::after{
    content:'✅';
    position:absolute;top:8px;right:8px;
    font-size:20px;
    background:rgba(255,255,255,0.9);
    border-radius:50%;width:32px;height:32px;
    display:flex;align-items:center;justify-content:center;
    font-size:16px;
    box-shadow:0 2px 6px rgba(0,0,0,0.15);
}
.stamp.visited .stamp-img{filter:none;}
.stamp.unvisited .stamp-img,.stamp.unvisited .stamp-img-ph{filter:grayscale(80%) opacity(0.6);}
.stamp.unvisited::after{
    content:'🔒';
    position:absolute;top:8px;right:8px;
    background:rgba(0,0,0,0.5);
    border-radius:50%;width:28px;height:28px;
    display:flex;align-items:center;justify-content:center;
    font-size:13px;
}

/* BADGES SECTION */
.badges-section{margin-bottom:32px;}
.badges-section h2{font-size:20px;color:#634b49;margin-bottom:16px;padding-bottom:8px;border-bottom:2px solid #fff1b5;}
.badges-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px;}
.badge-card{background:white;border-radius:12px;padding:18px;box-shadow:0 4px 14px rgba(0,0,0,0.07);display:flex;align-items:center;gap:14px;}
.badge-card.locked{opacity:0.4;filter:grayscale(1);}
.badge-icon-big{font-size:36px;flex-shrink:0;}
.badge-info h4{font-size:14px;color:#634b49;font-weight:bold;margin-bottom:3px;}
.badge-info p{font-size:12px;color:#888;line-height:1.4;}

footer{background:#43302e;color:white;text-align:center;padding:20px;margin-top:40px;}
footer p{margin:0;color:white;}
@media(max-width:600px){
    header{padding:15px 20px;}
    .stats-strip{flex-wrap:wrap;gap:16px;}
    .stat-item{border-right:none;}
    .stamp-grid{grid-template-columns:repeat(auto-fill,minmax(130px,1fr));}
}
</style>
</head>
<body>

<?php include 'header.php'; ?>

<!-- Passport Cover -->
<div class="passport-cover">
  <div class="passport-emblem">📒</div>
  <div class="passport-title">Bite by Bite Mumbai</div>
  <div class="passport-name"><?= htmlspecialchars($user['name']) ?></div>
  <div class="passport-subtitle">Food Explorer's Passport</div>

  <div class="tier-badge">
    <span class="tier-icon"><?= $tierIcon ?></span>
    <div class="tier-text">
      <div class="tier-label">Current Rank</div>
      <div class="tier-name"><?= $tier ?></div>
    </div>
  </div>

  <div class="progress-wrap">
    <div class="progress-bar-bg">
      <div class="progress-bar-fill" style="width:<?= $pct ?>%;"></div>
    </div>
    <div class="progress-label"><?= $visitedSpots ?> of <?= $totalSpots ?> spots visited &nbsp;·&nbsp; <?= $pct ?>% complete</div>
  </div>
</div>

<!-- Stats Strip -->
<div class="stats-strip">
  <div class="stat-item"><div class="stat-num"><?= $visitedSpots ?></div><div class="stat-lbl">Spots Visited</div></div>
  <div class="stat-item"><div class="stat-num"><?= $totalSpots - $visitedSpots ?></div><div class="stat-lbl">Yet to Visit</div></div>
  <div class="stat-item"><div class="stat-num"><?= $completedTrails ?></div><div class="stat-lbl">Trails Completed</div></div>
  <div class="stat-item"><div class="stat-num"><?= $totalTrails ?></div><div class="stat-lbl">Total Trails</div></div>
</div>

<div class="container">

  <!-- Badges -->
  <div class="badges-section">
    <h2>🏅 Badges</h2>
    <div class="badges-grid">
      <?php
      $badgeDefs = [
          ['icon'=>'🌱','name'=>'First Bite',        'desc'=>'Visit your first food spot',         'earned'=> $visitedSpots >= 1],
          ['icon'=>'🍴','name'=>'Hungry Adventurer',  'desc'=>'Visit 25% of all spots',             'earned'=> $pct >= 25],
          ['icon'=>'⭐','name'=>'Seasoned Explorer',  'desc'=>'Visit 50% of all spots',             'earned'=> $pct >= 50],
          ['icon'=>'🏅','name'=>'Trail Connoisseur',  'desc'=>'Visit 75% of all spots',             'earned'=> $pct >= 75],
          ['icon'=>'👑','name'=>'Mumbai Food Legend', 'desc'=>'Visit every single spot',             'earned'=> $pct === 100],
          ['icon'=>'🗺️','name'=>'Trail Blazer',       'desc'=>'Complete your first full trail',      'earned'=> $completedTrails >= 1],
          ['icon'=>'🎖️','name'=>'Trail Master',       'desc'=>'Complete 3 or more trails',           'earned'=> $completedTrails >= 3],
          ['icon'=>'🌟','name'=>'All Trails Done',    'desc'=>'Complete every trail in Mumbai',      'earned'=> $completedTrails >= $totalTrails && $totalTrails > 0],
      ];
      // Add per-trail completion badges
      foreach ($byTrail as $tid => $trail) {
          $done  = count(array_filter($trail['spots'], fn($s) => $s['visited']));
          $total = count($trail['spots']);
          if ($total > 0) {
              $badgeDefs[] = [
                  'icon'   => '📍',
                  'name'   => $trail['name'] . ' Complete',
                  'desc'   => 'Visited all spots on ' . $trail['name'],
                  'earned' => $done === $total,
              ];
          }
      }
      foreach ($badgeDefs as $b): ?>
        <div class="badge-card <?= $b['earned'] ? '' : 'locked' ?>">
          <span class="badge-icon-big"><?= $b['icon'] ?></span>
          <div class="badge-info">
            <h4><?= htmlspecialchars($b['name']) ?></h4>
            <p><?= htmlspecialchars($b['desc']) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Trail Stamps -->
  <?php foreach ($byTrail as $tid => $trail):
    $doneCount = count(array_filter($trail['spots'], fn($s) => $s['visited']));
    $totalCount = count($trail['spots']);
    $trailPct   = $totalCount > 0 ? round(($doneCount / $totalCount) * 100) : 0;
    $isComplete = $doneCount === $totalCount && $totalCount > 0;
  ?>
  <div class="trail-section">
    <div class="trail-header">
      <div class="trail-header-left">
        <h3><?= htmlspecialchars($trail['name']) ?></h3>
        <p>📍 <?= htmlspecialchars($trail['area']) ?></p>
      </div>
      <div class="trail-progress-mini">
        <div class="trail-progress-text"><?= $doneCount ?>/<?= $totalCount ?> spots</div>
        <div class="trail-bar-bg"><div class="trail-bar-fill" style="width:<?= $trailPct ?>%;"></div></div>
        <?php if ($isComplete): ?><span class="complete-badge">✅ Complete!</span><?php endif; ?>
      </div>
    </div>

    <div class="stamp-grid">
      <?php foreach ($trail['spots'] as $s): ?>
        <a href="spot-details.php?id=<?= $s['id'] ?>" class="stamp <?= $s['visited'] ? 'visited' : 'unvisited' ?>">
          <?php if ($s['image_url']): ?>
            <img class="stamp-img" src="<?= htmlspecialchars($s['image_url']) ?>" alt="<?= htmlspecialchars($s['name']) ?>" onerror="this.outerHTML='<div class=stamp-img-ph>🍴</div>'">
          <?php else: ?>
            <div class="stamp-img-ph">🍴</div>
          <?php endif; ?>
          <div class="stamp-body">
            <div class="stamp-spot-name"><?= htmlspecialchars($s['name']) ?></div>
            <div class="stamp-area">📍 <?= htmlspecialchars($s['area']) ?></div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>

</div>

<footer><p>© 2026 Bite by Bite - Mumbai | Created by Shreya Sakala, Gayatri Bedekar & Palak Sanklecha</p></footer>
</body>
</html>
