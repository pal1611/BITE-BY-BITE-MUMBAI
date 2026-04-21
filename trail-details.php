<?php
// trail-details.php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user     = $_SESSION['user'];
$trail_id = intval($_GET['id'] ?? 0);

if (!$trail_id) {
    header("Location: trails.php");
    exit();
}

// Fetch trail
$stmt = $conn->prepare("SELECT * FROM trails WHERE id = ?");
$stmt->bind_param("i", $trail_id);
$stmt->execute();
$trail = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$trail) {
    echo "Trail not found."; exit();
}

// Fetch food spots for this trail
$stmt = $conn->prepare("SELECT * FROM food_spots WHERE trail_id = ? ORDER BY name");
$stmt->bind_param("i", $trail_id);
$stmt->execute();
$spots = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle trail rating submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trail_rating'])) {
    $rating = intval($_POST['trail_rating']);
    if ($rating >= 1 && $rating <= 5) {
        $stmt = $conn->prepare("INSERT INTO trail_ratings (trail_id, user_id, rating) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE rating = ?");
        $stmt->bind_param("iiii", $trail_id, $user['id'], $rating, $rating);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: trail-details.php?id=$trail_id");
    exit();
}

// Get user's existing rating
$stmt = $conn->prepare("SELECT rating FROM trail_ratings WHERE trail_id = ? AND user_id = ?");
$stmt->bind_param("ii", $trail_id, $user['id']);
$stmt->execute();
$userRating = $stmt->get_result()->fetch_assoc()['rating'] ?? 0;
$stmt->close();

// Check if trail is already favourited
$favCheck = $conn->prepare("SELECT id FROM favourites WHERE user_id = ? AND trail_id = ?");
$favCheck->bind_param("ii", $user['id'], $trail_id);
$favCheck->execute();
$trailFaved = $favCheck->get_result()->num_rows > 0;
$favCheck->close();

$typeLabel = ['veg' => '🟢 Veg', 'nonveg' => '🔴 Non-Veg', 'both' => '🟡 Veg & Non-Veg'];
$typeClass = ['veg' => 'badge-veg', 'nonveg' => 'badge-nonveg', 'both' => 'badge-both'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($trail['name']) ?> - Bite by Bite</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#fff8f0; font-family:Arial,Helvetica,sans-serif; color:#333; }
header { background:#fff1b5; padding:15px 40px; display:flex; justify-content:space-between; align-items:center; position:relative; min-height:60px; }
header h1 { color:#634b49; font-size:24px; }
.user-menu{position:relative;}.user-menu-trigger{font-weight:bold;color:#634b49;font-size:14px;cursor:pointer;display:flex;align-items:center;gap:5px;background:none;border:none;padding:0;}.user-menu-trigger:hover{color:#43302e;}.dropdown{display:none;position:absolute;right:0;top:calc(100% + 10px);background:white;border-radius:12px;box-shadow:0 8px 28px rgba(0,0,0,0.13);min-width:210px;overflow:hidden;z-index:200;border:0.5px solid #e8ddd5;}.dropdown.open{display:block;}.dropdown-header{background:#fff1b5;padding:14px 18px;font-size:13px;color:#888;border-bottom:1px solid #e8ddd5;}.dropdown-header strong{display:block;font-size:15px;color:#634b49;font-weight:bold;}.dropdown a,.dropdown button{display:flex;align-items:center;gap:10px;padding:11px 18px;text-decoration:none;color:#333;font-size:14px;background:none;border:none;width:100%;text-align:left;cursor:pointer;transition:background 0.15s;}.dropdown a:hover,.dropdown button:hover{background:#c1dbe8;}.dropdown .divider{height:1px;background:#f0e8e0;margin:4px 0;}.dropdown .logout-item{color:#b71c1c;}.dropdown .logout-item:hover{background:#fdecea;}

.container { max-width:850px; margin:30px auto; padding:0 20px; }

.trail-info { background:white; border-radius:12px; padding:24px; box-shadow:0 5px 15px rgba(0,0,0,0.08); margin-bottom:24px; }
.trail-info h2 { color:#634b49; font-size:26px; margin-bottom:8px; }
.trail-info p  { color:#555; line-height:1.7; font-size:15px; margin-bottom:14px; }
.meta-row { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:16px; }
.meta-badge { background:#fff1b5; color:#634b49; padding:5px 14px; border-radius:20px; font-size:13px; font-weight:bold; border:1px solid #e0d070; }

.rating-form { margin-top:14px; }
.rating-form p { font-size:14px; color:#888; margin-bottom:6px; }
.star-row { display:flex; gap:4px; }
.star-btn { font-size:28px; background:none; border:none; cursor:pointer; color:#ccc; transition:color 0.1s; padding:0; }
.star-btn.active { color:#f5a623; }

.spots-title { font-size:20px; color:#634b49; margin-bottom:14px; padding-bottom:8px; border-bottom:2px solid #fff1b5; }
.spot-card { background:white; border-radius:12px; box-shadow:0 5px 15px rgba(0,0,0,0.08); margin-bottom:14px; display:flex; overflow:hidden; transition:transform 0.2s; }
.spot-card:hover { transform:translateY(-2px); }
.spot-img { width:130px; min-height:110px; object-fit:cover; flex-shrink:0; }
.spot-img-placeholder { width:130px; min-height:110px; background:linear-gradient(135deg,#fff1b5,#c1dbe8); display:flex; align-items:center; justify-content:center; font-size:32px; flex-shrink:0; }
.spot-body { padding:16px; flex:1; }
.spot-body h3 { color:#634b49; font-size:16px; margin-bottom:6px; }
.spot-body p  { font-size:13px; color:#666; margin-bottom:10px; line-height:1.5; }
.tags { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:10px; }
.badge { padding:3px 10px; border-radius:20px; font-size:12px; font-weight:bold; }
.badge-veg    { background:#e6f9e6; color:#2e7d32; }
.badge-nonveg { background:#fdecea; color:#b71c1c; }
.badge-both   { background:#fff8e1; color:#f57f17; }
.badge-cost   { background:#f3e5f5; color:#6a1b9a; }
.btn { background:#fff1b5; color:#634b49; padding:8px 18px; border-radius:6px; text-decoration:none; font-weight:bold; font-size:13px; display:inline-block; }
.btn:hover { background:#c1dbe8; }
.no-spots { color:#aaa; font-style:italic; font-size:14px; padding:20px 0; }

footer { background:#43302e; color:white; text-align:center; padding:20px; margin-top:40px; }
footer p { margin:0; }
.toast { position:fixed; bottom:24px; left:50%; transform:translateX(-50%); background:#43302e; color:white; padding:10px 22px; border-radius:20px; font-size:13px; opacity:0; transition:opacity 0.3s; pointer-events:none; z-index:999; }
.toast.show { opacity:1; }
@media(max-width:600px){ .spot-card{flex-direction:column;} .spot-img,.spot-img-placeholder{width:100%;height:160px;} header{padding:15px 20px;} }
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container">

  <div class="trail-info">
    <h2><?= htmlspecialchars($trail['name']) ?></h2>
    <div class="meta-row">
      <span class="meta-badge">📍 <?= htmlspecialchars($trail['area']) ?></span>
      <span class="meta-badge">💰 <?= htmlspecialchars($trail['cost']) ?></span>
      <span class="meta-badge">⏱ <?= htmlspecialchars($trail['duration']) ?></span>
    </div>
    <p><?= htmlspecialchars($trail['description']) ?></p>
    <div style="display:flex;gap:10px;margin-top:14px;flex-wrap:wrap;">
      <button id="trailFavBtn" onclick="toggleTrailFav()"
        style="display:flex;align-items:center;gap:6px;padding:9px 18px;border-radius:8px;border:1px solid <?= $trailFaved ? '#e57373' : '#ddd' ?>;background:<?= $trailFaved ? '#fff5f5' : 'white' ?>;cursor:pointer;font-size:13px;font-weight:bold;color:<?= $trailFaved ? '#b71c1c' : '#634b49' ?>;">
        <?= $trailFaved ? '❤️ Saved' : '🤍 Save Trail' ?>
      </button>
      <button onclick="shareTrail()"
        style="display:flex;align-items:center;gap:6px;padding:9px 18px;border-radius:8px;border:1px solid #ddd;background:white;cursor:pointer;font-size:13px;font-weight:bold;color:#634b49;">
        🔗 Share
      </button>
    </div>

    <div class="rating-form">
      <p>Rate this trail:</p>
      <form method="POST">
        <div class="star-row" id="starRow">
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <button type="submit" name="trail_rating" value="<?= $i ?>" class="star-btn <?= $i <= $userRating ? 'active' : '' ?>">★</button>
          <?php endfor; ?>
        </div>
      </form>
    </div>
  </div>

  <div class="spots-title">Food Spots on this Trail</div>

  <?php if (empty($spots)): ?>
    <p class="no-spots">No food spots added for this trail yet.</p>
  <?php else: ?>
    <?php foreach ($spots as $spot): ?>
      <div class="spot-card">
        <?php if ($spot['image_url']): ?>
          <img class="spot-img" src="<?= htmlspecialchars($spot['image_url']) ?>" alt="<?= htmlspecialchars($spot['name']) ?>" onerror="this.outerHTML='<div class=spot-img-placeholder>🍴</div>'">
        <?php else: ?>
          <div class="spot-img-placeholder">🍴</div>
        <?php endif; ?>
        <div class="spot-body">
          <h3><?= htmlspecialchars($spot['name']) ?></h3>
          <p><?= htmlspecialchars($spot['description']) ?></p>
          <div class="tags">
            <span class="badge <?= $typeClass[$spot['type']] ?? 'badge-both' ?>"><?= $typeLabel[$spot['type']] ?? $spot['type'] ?></span>
            <span class="badge badge-cost">💰 <?= htmlspecialchars($spot['price_range']) ?></span>
          </div>
          <a href="spot-details.php?id=<?= $spot['id'] ?>" class="btn">View Spot →</a>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</div>

<div class="toast" id="toast"></div>
<footer><p>© 2026 Bite by Bite - Mumbai | Created by Shreya Sakala, Gayatri Bedekar & Palak Sanklecha</p></footer>

<script>
  let trailFaved = <?= $trailFaved ? 'true' : 'false' ?>;

  function showToast(msg){
    const t = document.getElementById('toast');
    t.innerText = msg; t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2500);
  }

  function toggleTrailFav(){
    const body = new FormData();
    body.append('trail_id', <?= $trail_id ?>);
    fetch('toggle_favourite.php', { method:'POST', body })
    .then(r => r.json())
    .then(data => {
      if(!data.success) return;
      trailFaved = data.action === 'added';
      const btn = document.getElementById('trailFavBtn');
      btn.innerText     = trailFaved ? '❤️ Saved' : '🤍 Save Trail';
      btn.style.background  = trailFaved ? '#fff5f5' : 'white';
      btn.style.borderColor = trailFaved ? '#e57373' : '#ddd';
      btn.style.color       = trailFaved ? '#b71c1c' : '#634b49';
      showToast(trailFaved ? 'Trail saved to favourites ❤️' : 'Removed from favourites');
    });
  }

  function shareTrail(){
    navigator.clipboard.writeText(window.location.href)
      .then(() => showToast('Link copied to clipboard!'));
  }

  const stars = document.querySelectorAll('.star-btn');
  stars.forEach((btn, i) => {
    btn.addEventListener('mouseover', () => stars.forEach((s, j) => s.style.color = j <= i ? '#f5a623' : '#ccc'));
    btn.addEventListener('mouseout',  () => stars.forEach((s, j) => s.style.color = s.classList.contains('active') ? '#f5a623' : '#ccc'));
  });
</script>
</body>
</html>
