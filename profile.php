<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
$user    = $_SESSION['user'];
$user_id = $user['id'];

// Fetch full user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch user's reviews (with spot name)
$stmt = $conn->prepare("
    SELECT r.*, fs.name AS spot_name, fs.id AS spot_id
    FROM reviews r
    JOIN food_spots fs ON r.spot_id = fs.id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$myReviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch trail ratings
$stmt = $conn->prepare("
    SELECT tr.rating, t.name AS trail_name, t.id AS trail_id
    FROM trail_ratings tr
    JOIN trails t ON tr.trail_id = t.id
    WHERE tr.user_id = ?
    ORDER BY tr.rating DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$myRatings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch favourite spots
$stmt = $conn->prepare("
    SELECT fs.id, fs.name, fs.area, fs.type, fs.price_range, fs.image_url
    FROM favourites f
    JOIN food_spots fs ON f.spot_id = fs.id
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$favSpots = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch favourite trails
$stmt = $conn->prepare("
    SELECT t.id, t.name, t.area, t.cost, t.duration
    FROM favourites f
    JOIN trails t ON f.trail_id = t.id
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$favTrails = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch check-ins
$stmt = $conn->prepare("
    SELECT c.spot_id, fs.name AS spot_name, t.name AS trail_name, t.id AS trail_id
    FROM checkins c
    JOIN food_spots fs ON c.spot_id = fs.id
    JOIN trails t ON fs.trail_id = t.id
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$checkins    = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$checkedIds  = array_column($checkins, 'spot_id');
$stmt->close();

// Fetch all trails with their spot counts to compute badges
$stmt = $conn->prepare("
    SELECT t.id, t.name, COUNT(fs.id) AS total_spots
    FROM trails t
    JOIN food_spots fs ON fs.trail_id = t.id
    GROUP BY t.id
");
$stmt->execute();
$allTrailsForBadge = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Work out which trails are completed
$completedTrails = [];
foreach ($allTrailsForBadge as $t) {
    // Get spot IDs for this trail
    $res = $conn->prepare("SELECT id FROM food_spots WHERE trail_id = ?");
    $res->bind_param("i", $t['id']);
    $res->execute();
    $trailSpotIds = array_column($res->get_result()->fetch_all(MYSQLI_ASSOC), 'id');
    $res->close();
    if ($t['total_spots'] > 0 && count(array_intersect($checkedIds, $trailSpotIds)) === $t['total_spots']) {
        $completedTrails[] = $t['name'];
    }
}

$typeLabel = ['veg' => '🟢 Veg', 'nonveg' => '🔴 Non-Veg', 'both' => '🟡 Veg & Non-Veg'];
$typeClass = ['veg' => 'badge-veg', 'nonveg' => 'badge-nonveg', 'both' => 'badge-both'];
$activeTab = $_GET['tab'] ?? 'overview';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Profile - Bite by Bite</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#fff8f0; font-family:Arial,Helvetica,sans-serif; color:#333; }
header { background:#fff1b5; padding:15px 40px; display:flex; justify-content:space-between; align-items:center; position:relative; min-height:60px; }
header h1 { color:#634b49; font-size:24px; }
.user-menu{position:relative;}.user-menu-trigger{font-weight:bold;color:#634b49;font-size:14px;cursor:pointer;display:flex;align-items:center;gap:5px;background:none;border:none;padding:0;}.user-menu-trigger:hover{color:#43302e;}.dropdown{display:none;position:absolute;right:0;top:calc(100% + 10px);background:white;border-radius:12px;box-shadow:0 8px 28px rgba(0,0,0,0.13);min-width:210px;overflow:hidden;z-index:200;border:0.5px solid #e8ddd5;}.dropdown.open{display:block;}.dropdown-header{background:#fff1b5;padding:14px 18px;font-size:13px;color:#888;border-bottom:1px solid #e8ddd5;}.dropdown-header strong{display:block;font-size:15px;color:#634b49;font-weight:bold;}.dropdown a,.dropdown button{display:flex;align-items:center;gap:10px;padding:11px 18px;text-decoration:none;color:#333;font-size:14px;background:none;border:none;width:100%;text-align:left;cursor:pointer;transition:background 0.15s;}.dropdown a:hover,.dropdown button:hover{background:#c1dbe8;}.dropdown .divider{height:1px;background:#f0e8e0;margin:4px 0;}.dropdown .logout-item{color:#b71c1c;}.dropdown .logout-item:hover{background:#fdecea;}

/* PROFILE HERO */
.profile-hero { background:linear-gradient(135deg,#634b49,#8b6b68); color:white; padding:36px 40px; display:flex; align-items:center; gap:24px; }
.avatar { width:72px; height:72px; border-radius:50%; background:#fff1b5; display:flex; align-items:center; justify-content:center; font-size:28px; font-weight:bold; color:#634b49; flex-shrink:0; }
.profile-info h2 { font-size:22px; margin-bottom:4px; }
.profile-info p  { font-size:14px; opacity:0.85; }
.profile-role { background:#fff1b5; color:#634b49; padding:3px 12px; border-radius:20px; font-size:12px; font-weight:bold; display:inline-block; margin-top:8px; }

/* TABS */
.tab-bar { background:white; border-bottom:2px solid #fff1b5; display:flex; padding:0 40px; gap:4px; flex-wrap:wrap; }
.tab-btn { display:inline-block; padding:12px 18px; text-decoration:none; font-size:13px; font-weight:bold; color:#888; border-bottom:3px solid transparent; margin-bottom:-2px; }
.tab-btn.active { color:#634b49; border-bottom-color:#634b49; }
.tab-btn:hover  { color:#634b49; }

.main { max-width:900px; margin:28px auto; padding:0 22px; }

/* STATS GRID */
.stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:24px; }
.stat-card { background:white; border-radius:10px; padding:16px; text-align:center; box-shadow:0 4px 14px rgba(0,0,0,0.07); }
.stat-num { font-size:26px; font-weight:bold; color:#634b49; }
.stat-lbl { font-size:12px; color:#888; margin-top:3px; }

/* BADGES */
.badges-grid { display:flex; flex-wrap:wrap; gap:12px; margin-bottom:24px; }
.badge-card { background:white; border-radius:12px; padding:16px 20px; box-shadow:0 4px 14px rgba(0,0,0,0.07); display:flex; align-items:center; gap:12px; }
.badge-icon { font-size:32px; }
.badge-text h4 { color:#634b49; font-size:14px; margin-bottom:2px; }
.badge-text p  { font-size:12px; color:#888; }
.no-badge { font-size:13px; color:#aaa; font-style:italic; }

/* CARD SHARED */
.card { background:white; border-radius:12px; box-shadow:0 4px 14px rgba(0,0,0,0.07); padding:22px; margin-bottom:20px; }
.card-title { font-size:16px; font-weight:bold; color:#634b49; margin-bottom:16px; padding-bottom:10px; border-bottom:2px solid #fff1b5; }

/* REVIEW ROW */
.review-row { border:0.5px solid #e8ddd5; border-radius:8px; padding:14px; margin-bottom:10px; }
.review-top { display:flex; justify-content:space-between; align-items:center; margin-bottom:4px; }
.review-spot { font-weight:bold; font-size:14px; color:#634b49; }
.review-stars { color:#f5a623; font-size:15px; }
.review-date { font-size:12px; color:#aaa; margin-bottom:6px; }
.review-text { font-size:13px; color:#555; line-height:1.5; }
.review-photo { margin-top:8px; max-width:200px; border-radius:8px; }

/* SPOT / TRAIL ROWS */
.item-row { display:flex; align-items:center; gap:12px; padding:11px 14px; background:#fff8f0; border-radius:8px; border:0.5px solid #e8ddd5; margin-bottom:8px; }
.item-thumb { width:48px; height:48px; border-radius:7px; object-fit:cover; flex-shrink:0; }
.item-thumb-ph { width:48px; height:48px; border-radius:7px; background:linear-gradient(135deg,#fff1b5,#c1dbe8); display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
.item-info { flex:1; }
.item-name { font-weight:bold; font-size:14px; color:#634b49; }
.item-meta { font-size:12px; color:#888; margin-top:2px; }

.badge-pill { padding:3px 10px; border-radius:20px; font-size:11px; font-weight:bold; }
.badge-veg    { background:#e6f9e6; color:#2e7d32; }
.badge-nonveg { background:#fdecea; color:#b71c1c; }
.badge-both   { background:#fff8e1; color:#f57f17; }

.btn-sm { background:#fff1b5; color:#634b49; padding:6px 14px; border-radius:6px; text-decoration:none; font-weight:bold; font-size:12px; display:inline-block; }
.btn-sm:hover { background:#c1dbe8; }

.empty { color:#aaa; font-style:italic; font-size:13px; padding:12px 0; }

/* CHECKINS */
.checkin-row { display:flex; align-items:center; justify-content:space-between; padding:10px 14px; background:#fff8f0; border-radius:8px; border:0.5px solid #e8ddd5; margin-bottom:8px; }
.checkin-name { font-weight:bold; font-size:14px; color:#634b49; }
.checkin-trail { font-size:12px; color:#888; }

footer { background:#43302e; color:white; text-align:center; padding:20px; margin-top:40px; }
footer p { margin:0; color:white; }
@media(max-width:600px){ header{padding:15px 20px;} .stats-grid{grid-template-columns:1fr 1fr;} .profile-hero{flex-direction:column;text-align:center;} .tab-bar{padding:0 10px;} }
</style>
</head>
<body>

<?php include 'header.php'; ?>

<!-- Profile hero -->
<div class="profile-hero">
  <div class="avatar"><?= strtoupper(substr($profile['name'], 0, 1)) ?></div>
  <div class="profile-info">
    <h2><?= htmlspecialchars($profile['name']) ?></h2>
    <p><?= htmlspecialchars($profile['email']) ?> &nbsp;·&nbsp; <?= htmlspecialchars($profile['phone']) ?></p>
    <span class="profile-role"><?= ucfirst($profile['role']) ?></span>
  </div>
</div>

<!-- Tabs -->
<div class="tab-bar">
  <a href="?tab=overview"  class="tab-btn <?= $activeTab==='overview'  ?'active':'' ?>">Overview</a>
  <a href="?tab=reviews"   class="tab-btn <?= $activeTab==='reviews'   ?'active':'' ?>">My Reviews (<?= count($myReviews) ?>)</a>
  <a href="?tab=ratings"   class="tab-btn <?= $activeTab==='ratings'   ?'active':'' ?>">Trail Ratings (<?= count($myRatings) ?>)</a>
  <a href="?tab=favourites"class="tab-btn <?= $activeTab==='favourites'?'active':'' ?>">Saved (<?= count($favSpots)+count($favTrails) ?>)</a>
  <a href="?tab=checkins"  class="tab-btn <?= $activeTab==='checkins'  ?'active':'' ?>">Check-ins (<?= count($checkins) ?>)</a>
  <a href="?tab=badges"    class="tab-btn <?= $activeTab==='badges'    ?'active':'' ?>">Badges (<?= count($completedTrails) ?>)</a>
</div>

<div class="main">

<?php if ($activeTab === 'overview'): ?>
  <div class="stats-grid">
    <div class="stat-card"><div class="stat-num"><?= count($myReviews) ?></div><div class="stat-lbl">Reviews</div></div>
    <div class="stat-card"><div class="stat-num"><?= count($favSpots)+count($favTrails) ?></div><div class="stat-lbl">Saved</div></div>
    <div class="stat-card"><div class="stat-num"><?= count($checkins) ?></div><div class="stat-lbl">Check-ins</div></div>
    <div class="stat-card"><div class="stat-num"><?= count($completedTrails) ?></div><div class="stat-lbl">Badges</div></div>
  </div>
  <div style="text-align:center;margin-bottom:22px;">
    <a href="food-passport.php" style="display:inline-flex;align-items:center;gap:10px;background:linear-gradient(135deg,#43302e,#634b49);color:white;padding:14px 28px;border-radius:12px;text-decoration:none;font-weight:bold;font-size:15px;">
      📒 View My Food Passport
      <span style="font-size:12px;opacity:0.8;"><?= count($checkins) ?> stamps collected</span>
    </a>
  </div>

  <?php if (!empty($completedTrails)): ?>
  <div class="card">
    <div class="card-title">🏅 Earned Badges</div>
    <div class="badges-grid">
      <?php foreach ($completedTrails as $name): ?>
        <div class="badge-card">
          <span class="badge-icon">🏅</span>
          <div class="badge-text">
            <h4><?= htmlspecialchars($name) ?> Explorer</h4>
            <p>Completed all spots on this trail</p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-title">Recent reviews</div>
    <?php if (empty($myReviews)): ?><p class="empty">No reviews yet.</p>
    <?php else: foreach (array_slice($myReviews, 0, 3) as $r): ?>
      <div class="review-row">
        <div class="review-top">
          <span class="review-spot"><a href="spot-details.php?id=<?= $r['spot_id'] ?>" style="color:#634b49;text-decoration:none;"><?= htmlspecialchars($r['spot_name']) ?></a></span>
          <span class="review-stars"><?= str_repeat('★',$r['rating']).str_repeat('☆',5-$r['rating']) ?></span>
        </div>
        <div class="review-date"><?= date('d M Y', strtotime($r['created_at'])) ?></div>
        <div class="review-text"><?= htmlspecialchars($r['review_text']) ?></div>
      </div>
    <?php endforeach; endif; ?>
  </div>

<?php elseif ($activeTab === 'reviews'): ?>
  <div class="card">
    <div class="card-title">All my reviews</div>
    <?php if (empty($myReviews)): ?><p class="empty">You haven't written any reviews yet.</p>
    <?php else: foreach ($myReviews as $r): ?>
      <div class="review-row">
        <div class="review-top">
          <span class="review-spot"><a href="spot-details.php?id=<?= $r['spot_id'] ?>" style="color:#634b49;text-decoration:none;"><?= htmlspecialchars($r['spot_name']) ?></a></span>
          <span class="review-stars"><?= str_repeat('★',$r['rating']).str_repeat('☆',5-$r['rating']) ?></span>
        </div>
        <div class="review-date"><?= date('d M Y', strtotime($r['created_at'])) ?></div>
        <div class="review-text"><?= htmlspecialchars($r['review_text']) ?></div>
        <?php if (!empty($r['photo_url'])): ?>
          <img src="<?= htmlspecialchars($r['photo_url']) ?>" class="review-photo" alt="Review photo">
        <?php endif; ?>
      </div>
    <?php endforeach; endif; ?>
  </div>

<?php elseif ($activeTab === 'ratings'): ?>
  <div class="card">
    <div class="card-title">Trail ratings I've given</div>
    <?php if (empty($myRatings)): ?><p class="empty">You haven't rated any trails yet.</p>
    <?php else: foreach ($myRatings as $r): ?>
      <div class="item-row">
        <div class="item-info">
          <div class="item-name"><?= htmlspecialchars($r['trail_name']) ?></div>
        </div>
        <span style="color:#f5a623;font-size:18px;"><?= str_repeat('★',$r['rating']).str_repeat('☆',5-$r['rating']) ?></span>
        <a href="trail-details.php?id=<?= $r['trail_id'] ?>" class="btn-sm">View</a>
      </div>
    <?php endforeach; endif; ?>
  </div>

<?php elseif ($activeTab === 'favourites'): ?>
  <div class="card">
    <div class="card-title">Saved Food Spots (<?= count($favSpots) ?>)</div>
    <?php if (empty($favSpots)): ?><p class="empty">No favourite spots saved yet.</p>
    <?php else: foreach ($favSpots as $s): ?>
      <div class="item-row">
        <?php if ($s['image_url']): ?>
          <img class="item-thumb" src="<?= htmlspecialchars($s['image_url']) ?>" onerror="this.outerHTML='<div class=item-thumb-ph>🍴</div>'">
        <?php else: ?><div class="item-thumb-ph">🍴</div><?php endif; ?>
        <div class="item-info">
          <div class="item-name"><?= htmlspecialchars($s['name']) ?></div>
          <div class="item-meta">📍 <?= htmlspecialchars($s['area']) ?> · 💰 <?= htmlspecialchars($s['price_range']) ?></div>
        </div>
        <span class="badge-pill <?= $typeClass[$s['type']] ?? 'badge-both' ?>"><?= $typeLabel[$s['type']] ?? $s['type'] ?></span>
        <a href="spot-details.php?id=<?= $s['id'] ?>" class="btn-sm">View</a>
      </div>
    <?php endforeach; endif; ?>
  </div>
  <div class="card">
    <div class="card-title">Saved Trails (<?= count($favTrails) ?>)</div>
    <?php if (empty($favTrails)): ?><p class="empty">No favourite trails saved yet.</p>
    <?php else: foreach ($favTrails as $t): ?>
      <div class="item-row">
        <div class="item-info">
          <div class="item-name"><?= htmlspecialchars($t['name']) ?></div>
          <div class="item-meta">📍 <?= htmlspecialchars($t['area']) ?> · 💰 <?= htmlspecialchars($t['cost']) ?> · ⏱ <?= htmlspecialchars($t['duration']) ?></div>
        </div>
        <a href="trail-details.php?id=<?= $t['id'] ?>" class="btn-sm">View</a>
      </div>
    <?php endforeach; endif; ?>
  </div>

<?php elseif ($activeTab === 'checkins'): ?>
  <div class="card">
    <div class="card-title">Spots I've visited (<?= count($checkins) ?>)</div>
    <?php if (empty($checkins)): ?><p class="empty">No check-ins yet. Visit a food spot and tap Check In!</p>
    <?php else: foreach ($checkins as $c): ?>
      <div class="checkin-row">
        <div>
          <div class="checkin-name"><?= htmlspecialchars($c['spot_name']) ?></div>
          <div class="checkin-trail">on <?= htmlspecialchars($c['trail_name']) ?></div>
        </div>
        <span style="font-size:18px;">✅</span>
      </div>
    <?php endforeach; endif; ?>
  </div>

<?php elseif ($activeTab === 'badges'): ?>
  <div class="card">
    <div class="card-title">🏅 Earned Badges</div>
    <?php if (empty($completedTrails)): ?>
      <p class="empty">No badges earned yet. Complete all spots on a trail to earn a badge!</p>
      <div style="margin-top:16px;">
        <p style="font-size:13px;color:#634b49;font-weight:bold;margin-bottom:10px;">Your progress:</p>
        <?php foreach ($allTrailsForBadge as $t):
          $res2 = $conn->prepare("SELECT id FROM food_spots WHERE trail_id = ?");
          $res2->bind_param("i", $t['id']);
          $res2->execute();
          $trailSpotIds2 = array_column($res2->get_result()->fetch_all(MYSQLI_ASSOC), 'id');
          $res2->close();
          $done  = count(array_intersect($checkedIds, $trailSpotIds2));
          $total = $t['total_spots'];
          $pct   = $total > 0 ? round(($done / $total) * 100) : 0;
        ?>
        <div style="margin-bottom:12px;">
          <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
            <span><?= htmlspecialchars($t['name']) ?></span>
            <span style="color:#888;"><?= $done ?>/<?= $total ?> spots</span>
          </div>
          <div style="background:#f0e8e0;border-radius:20px;height:8px;overflow:hidden;">
            <div style="background:#634b49;width:<?= $pct ?>%;height:100%;border-radius:20px;transition:width 0.3s;"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="badges-grid">
        <?php foreach ($completedTrails as $name): ?>
          <div class="badge-card">
            <span class="badge-icon">🏅</span>
            <div class="badge-text">
              <h4><?= htmlspecialchars($name) ?> Explorer</h4>
              <p>Completed all spots on this trail</p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

</div>

<footer><p>© 2026 Bite by Bite - Mumbai | Created by Shreya Sakala, Gayatri Bedekar & Palak Sanklecha</p></footer>
</body>
</html>
