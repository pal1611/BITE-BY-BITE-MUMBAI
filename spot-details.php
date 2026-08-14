<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user    = $_SESSION['user'];
$spot_id = intval($_GET['id'] ?? 0);

if (!$spot_id) { header("Location: trails.php"); exit(); }

// Fetch spot
$stmt = $conn->prepare("SELECT fs.*, t.name AS trail_name, t.id AS trail_id FROM food_spots fs JOIN trails t ON fs.trail_id = t.id WHERE fs.id = ?");
$stmt->bind_param("i", $spot_id);
$stmt->execute();
$spot = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$spot) { echo "Spot not found."; exit(); }

// Fetch dishes
$stmt = $conn->prepare("SELECT dish_name FROM dishes WHERE spot_id = ?");
$stmt->bind_param("i", $spot_id);
$stmt->execute();
$dishes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch seasonal specials for this spot
$currentMonth = (int) date('n');
$stmt = $conn->prepare("
    SELECT * FROM seasonal_specials
    WHERE spot_id = ?
    AND (
        (start_month <= end_month AND ? BETWEEN start_month AND end_month)
        OR
        (start_month > end_month AND (? >= start_month OR ? <= end_month))
    )
");
$stmt->bind_param("iiii", $spot_id, $currentMonth, $currentMonth, $currentMonth);
$stmt->execute();
$activeSeasonals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// All seasonals for this spot
$stmt = $conn->prepare("SELECT * FROM seasonal_specials WHERE spot_id = ? ORDER BY start_month");
$stmt->bind_param("i", $spot_id);
$stmt->execute();
$allSeasonals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$reviewError = ""; $reviewSuccess = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating'] ?? 0);
    $text   = trim($_POST['review_text'] ?? '');
    $photoUrl = null;

    // Handle photo upload — convert to base64
    if (!empty($_FILES['review_photo']['tmp_name'])) {
        $imgData  = file_get_contents($_FILES['review_photo']['tmp_name']);
        $mime     = mime_content_type($_FILES['review_photo']['tmp_name']);
        $photoUrl = 'data:' . $mime . ';base64,' . base64_encode($imgData);
    }

    if ($rating < 1 || $rating > 5) {
        $reviewError = "Please select a star rating.";
    } elseif (!$text) {
        $reviewError = "Please write something before posting.";
    } else {
        $stmt = $conn->prepare("INSERT INTO reviews (spot_id, user_id, rating, review_text, photo_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiss", $spot_id, $user['id'], $rating, $text, $photoUrl);
        $stmt->execute();
        $stmt->close();
        $reviewSuccess = "Your review was posted!";
    }
}

// Fetch reviews
$stmt = $conn->prepare("SELECT r.*, u.name AS user_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.spot_id = ? ORDER BY r.created_at DESC");
$stmt->bind_param("i", $spot_id);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$avgRating = count($reviews) > 0 ? round(array_sum(array_column($reviews, 'rating')) / count($reviews), 1) : 0;

// Open/closed status
function isOpenNow($open, $close) {
    if (!$open || !$close) return null;
    $now   = strtotime('now');
    $openT = strtotime(date('Y-m-d') . ' ' . $open);
    $closeT= strtotime(date('Y-m-d') . ' ' . $close);
    return $now >= $openT && $now < $closeT;
}
$openStatus = isOpenNow($spot['open_time'], $spot['close_time']);
$typeLabel  = ['veg' => '🟢 Veg', 'nonveg' => '🔴 Non-Veg', 'both' => '🟡 Veg & Non-Veg'];
$typeClass  = ['veg' => 'badge-veg', 'nonveg' => 'badge-nonveg', 'both' => 'badge-both'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($spot['name']) ?> - Bite by Bite</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#fff8f0; font-family:Arial,Helvetica,sans-serif; color:#333; }
<?php include 'header_styles.php'; ?>

.hero-img { width:100%; height:300px; object-fit:cover; display:block; }
.hero-placeholder { width:100%; height:300px; background:linear-gradient(135deg,#fff1b5,#c1dbe8); display:flex; align-items:center; justify-content:center; font-size:60px; }

.container { max-width:900px; margin:30px auto; padding:0 20px; }
.card { background:white; border-radius:12px; padding:22px; box-shadow:0 5px 15px rgba(0,0,0,0.08); margin-bottom:20px; }
.card-title { font-size:15px; font-weight:bold; color:#634b49; margin-bottom:14px; padding-bottom:8px; border-bottom:2px solid #fff1b5; }

.spot-name { font-size:26px; color:#634b49; margin-bottom:10px; }
.badges { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:12px; }
.badge { padding:5px 12px; border-radius:20px; font-size:13px; font-weight:bold; }
.badge-veg    { background:#e6f9e6; color:#2e7d32; }
.badge-nonveg { background:#fdecea; color:#b71c1c; }
.badge-both   { background:#fff8e1; color:#f57f17; }
.badge-area   { background:#e3f2fd; color:#1565c0; }
.badge-cost   { background:#f3e5f5; color:#6a1b9a; }
.spot-desc { font-size:15px; color:#555; line-height:1.7; }

.info-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px; }
.dish-list { list-style:none; }
.dish-list li { padding:6px 0; font-size:14px; border-bottom:0.5px solid #f0e8e0; display:flex; gap:8px; align-items:center; }
.dish-list li:last-child { border-bottom:none; }
.dish-dot { width:8px; height:8px; border-radius:50%; background:#634b49; flex-shrink:0; }
.timing-row { display:flex; justify-content:space-between; font-size:14px; padding:5px 0; border-bottom:0.5px solid #f0e8e0; }
.timing-row:last-child { border-bottom:none; }
.timing-label { color:#888; }
.timing-val   { font-weight:bold; color:#333; }
.open-now   { color:#2e7d32; font-weight:bold; }
.closed-now { color:#b71c1c; font-weight:bold; }

.map-iframe { width:100%; height:280px; border:none; border-radius:8px; }

.avg-box { display:flex; align-items:center; gap:14px; padding:14px; background:#fff8f0; border-radius:8px; margin-bottom:18px; }
.avg-num { font-size:36px; font-weight:bold; color:#634b49; }
.avg-stars { color:#f5a623; font-size:22px; }
.avg-count { font-size:13px; color:#888; }

.write-review h4 { font-size:14px; color:#634b49; margin-bottom:8px; }
.star-input span { font-size:28px; cursor:pointer; color:#ccc; margin-right:4px; transition:color 0.1s; }
textarea { width:100%; padding:10px 14px; margin-top:10px; border:1px solid #ddd; border-radius:8px; font-family:Arial,Helvetica,sans-serif; font-size:14px; resize:vertical; min-height:80px; }
textarea:focus { outline:none; border-color:#634b49; }
.submit-btn { margin-top:10px; padding:10px 24px; background:#fff1b5; color:#634b49; border:none; border-radius:8px; font-weight:bold; cursor:pointer; font-size:14px; }
.submit-btn:hover { background:#c1dbe8; }
.err { color:red; font-size:13px; margin-top:6px; }
.suc { color:#2e7d32; font-size:13px; margin-top:6px; }

.review-card { border:0.5px solid #e8ddd5; border-radius:8px; padding:14px; margin-bottom:12px; }
.review-top { display:flex; justify-content:space-between; margin-bottom:4px; }
.reviewer { font-weight:bold; color:#634b49; font-size:14px; }
.r-stars { color:#f5a623; font-size:15px; }
.r-date  { font-size:12px; color:#aaa; margin-bottom:6px; }
.r-text  { font-size:14px; color:#555; line-height:1.6; }
.no-reviews { color:#aaa; font-style:italic; font-size:14px; }

footer { background:#43302e; color:white; text-align:center; padding:20px; margin-top:40px; }
footer p { margin:0; }
@media(max-width:600px){ .info-grid{grid-template-columns:1fr;} header{padding:15px 20px;} }
</style>
</head>
<body>

<?php include 'header.php'; ?>

<?php
// Check if this spot is already favourited by the user
$favCheck = $conn->prepare("SELECT id FROM favourites WHERE user_id = ? AND spot_id = ?");
$favCheck->bind_param("ii", $user['id'], $spot_id);
$favCheck->execute();
$isFaved = $favCheck->get_result()->num_rows > 0;
$favCheck->close();

// Check if already checked in
$cinCheck = $conn->prepare("SELECT id FROM checkins WHERE user_id = ? AND spot_id = ?");
$cinCheck->bind_param("ii", $user['id'], $spot_id);
$cinCheck->execute();
$isCheckedIn = $cinCheck->get_result()->num_rows > 0;
$cinCheck->close();
?>
<?php if ($spot['image_url']): ?>
  <img class="hero-img" src="<?= htmlspecialchars($spot['image_url']) ?>" alt="<?= htmlspecialchars($spot['name']) ?>" onerror="this.outerHTML='<div class=hero-placeholder>🍴</div>'">
<?php else: ?>
  <div class="hero-placeholder">🍴</div>
<?php endif; ?>

<div class="container">

  <!-- Spot header -->
  <div class="card">
    <div class="spot-name"><?= htmlspecialchars($spot['name']) ?></div>
    <div class="badges">
      <span class="badge <?= $typeClass[$spot['type']] ?? 'badge-both' ?>"><?= $typeLabel[$spot['type']] ?? $spot['type'] ?></span>
      <span class="badge badge-area">📍 <?= htmlspecialchars($spot['area']) ?></span>
      <span class="badge badge-cost">💰 <?= htmlspecialchars($spot['price_range']) ?></span>
    </div>
    <div class="spot-desc"><?= htmlspecialchars($spot['description']) ?></div>
    <div style="display:flex;gap:10px;margin-top:16px;flex-wrap:wrap;">
      <button id="favBtn" onclick="toggleFav()"
        style="display:flex;align-items:center;gap:6px;padding:9px 18px;border-radius:8px;border:1px solid <?= $isFaved ? '#e57373' : '#ddd' ?>;background:<?= $isFaved ? '#fff5f5' : 'white' ?>;cursor:pointer;font-size:13px;font-weight:bold;color:<?= $isFaved ? '#b71c1c' : '#634b49' ?>;">
        <?= $isFaved ? '❤️ Saved' : '🤍 Save to Favourites' ?>
      </button>
      <button onclick="shareSpot()"
        style="display:flex;align-items:center;gap:6px;padding:9px 18px;border-radius:8px;border:1px solid #ddd;background:white;cursor:pointer;font-size:13px;font-weight:bold;color:#634b49;">
        🔗 Share
      </button>
      <button id="checkinBtn" onclick="toggleCheckin()"
        style="display:flex;align-items:center;gap:6px;padding:9px 18px;border-radius:8px;border:1px solid <?= $isCheckedIn ? '#2e7d32' : '#ddd' ?>;background:<?= $isCheckedIn ? '#e6f9e6' : 'white' ?>;cursor:pointer;font-size:13px;font-weight:bold;color:<?= $isCheckedIn ? '#2e7d32' : '#634b49' ?>;">
        <?= $isCheckedIn ? '✅ Visited' : '📍 Check In' ?>
      </button>
    </div>
  </div>

  <!-- Info grid -->
  <div class="info-grid">
    <div class="card">
      <div class="card-title">🍽️ Must-Try Dishes</div>
      <ul class="dish-list">
        <?php if (empty($dishes)): ?>
          <li style="color:#aaa;font-style:italic;">No dishes listed yet.</li>
        <?php else: ?>
          <?php foreach ($dishes as $d): ?>
            <li><span class="dish-dot"></span><?= htmlspecialchars($d['dish_name']) ?></li>
          <?php endforeach; ?>
        <?php endif; ?>
      </ul>
    </div>
    <div class="card">
      <div class="card-title">🕐 Timings</div>
      <div class="timing-row"><span class="timing-label">Days</span><span class="timing-val"><?= htmlspecialchars($spot['days_open'] ?: '–') ?></span></div>
      <div class="timing-row"><span class="timing-label">Opens</span><span class="timing-val"><?= htmlspecialchars($spot['open_time'] ?: '–') ?></span></div>
      <div class="timing-row"><span class="timing-label">Closes</span><span class="timing-val"><?= htmlspecialchars($spot['close_time'] ?: '–') ?></span></div>
      <div class="timing-row">
        <span class="timing-label">Status</span>
        <?php if ($openStatus === null): ?>
          <span class="timing-val">–</span>
        <?php elseif ($openStatus): ?>
          <span class="open-now">● Open Now</span>
        <?php else: ?>
          <span class="closed-now">● Closed Now</span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Location & Route -->
  <div class="card" id="locationCard">
    <div class="card-title">📍 Location & Route</div>

    <?php if ($spot['latitude'] && $spot['longitude']): ?>
      <!-- Travel info strip -->
      <div id="travelStrip" style="display:none;background:#e3f2fd;border-radius:8px;padding:12px 16px;margin-bottom:14px;display:none;align-items:center;gap:16px;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:8px;">
          <span style="font-size:18px;">🚶</span>
          <div><div style="font-size:11px;color:#888;">Walking</div><div style="font-weight:bold;color:#1565c0;" id="walkTime">–</div></div>
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
          <span style="font-size:18px;">🚗</span>
          <div><div style="font-size:11px;color:#888;">Driving</div><div style="font-weight:bold;color:#1565c0;" id="driveTime">–</div></div>
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
          <span style="font-size:18px;">📏</span>
          <div><div style="font-size:11px;color:#888;">Distance</div><div style="font-weight:bold;color:#1565c0;" id="distanceVal">–</div></div>
        </div>
        <a id="mapsLink" href="#" target="_blank"
           style="margin-left:auto;background:#fff1b5;color:#634b49;padding:8px 16px;border-radius:8px;font-weight:bold;font-size:13px;text-decoration:none;">
          Open in Google Maps →
        </a>
      </div>

      <!-- Route map iframe (shown after location is obtained) -->
      <div id="routeMapWrap" style="display:none;margin-bottom:14px;">
        <iframe id="routeMapIframe" style="width:100%;height:320px;border:none;border-radius:8px;" allowfullscreen loading="lazy"></iframe>
      </div>

      <!-- Static embed map (shown before/alongside route) -->
      <?php if ($spot['map_src']): ?>
        <iframe class="map-iframe" id="staticMap" src="<?= htmlspecialchars($spot['map_src']) ?>" allowfullscreen loading="lazy"></iframe>
      <?php else: ?>
        <!-- OpenStreetMap fallback using coordinates -->
        <iframe class="map-iframe" id="staticMap"
          src="https://www.openstreetmap.org/export/embed.html?bbox=<?= $spot['longitude']-0.01 ?>,<?= $spot['latitude']-0.01 ?>,<?= $spot['longitude']+0.01 ?>,<?= $spot['latitude']+0.01 ?>&layer=mapnik&marker=<?= $spot['latitude'] ?>,<?= $spot['longitude'] ?>"
          allowfullscreen loading="lazy">
        </iframe>
      <?php endif; ?>

      <!-- Get Route button -->
      <div style="margin-top:12px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <button id="routeBtn" onclick="getRoute()"
          style="background:#fff1b5;color:#634b49;border:none;padding:10px 22px;border-radius:8px;font-weight:bold;font-size:14px;cursor:pointer;display:flex;align-items:center;gap:8px;transition:background 0.2s;" onmouseover="this.style.background='#c1dbe8'" onmouseout="this.style.background='#fff1b5'">
          📍 Get Route from My Location
        </button>
        <span id="routeStatus" style="font-size:13px;color:#888;"></span>
      </div>

    <?php else: ?>
      <?php if ($spot['map_src']): ?>
        <iframe class="map-iframe" src="<?= htmlspecialchars($spot['map_src']) ?>" allowfullscreen loading="lazy"></iframe>
      <?php else: ?>
        <p style="color:#aaa;font-size:14px;">Location coordinates not set for this spot. Admin can add them in the admin panel.</p>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <!-- Seasonal Specials -->
  <?php if (!empty($allSeasonals)): ?>
  <div class="card">
    <div class="card-title">🌸 Seasonal Specials</div>
    <?php if (!empty($activeSeasonals)): ?>
      <div style="background:#e6f9e6;border-radius:8px;padding:12px 16px;margin-bottom:14px;display:flex;align-items:center;gap:10px;">
        <span style="font-size:20px;">🎉</span>
        <div>
          <div style="font-weight:bold;font-size:14px;color:#2e7d32;">Available now!</div>
          <div style="font-size:13px;color:#388e3c;"><?= implode(', ', array_map(fn($s) => htmlspecialchars($s['dish_name']).' ('.htmlspecialchars($s['season_name']).')', $activeSeasonals)) ?></div>
        </div>
      </div>
    <?php endif; ?>
    <?php
    $months=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    foreach ($allSeasonals as $ss): ?>
      <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:#fff8f0;border-radius:8px;border:0.5px solid #e8ddd5;margin-bottom:8px;">
        <div>
          <div style="font-weight:bold;font-size:14px;color:#634b49;">🍽️ <?= htmlspecialchars($ss['dish_name']) ?></div>
          <div style="font-size:12px;color:#888;"><?= htmlspecialchars($ss['season_name']) ?> · <?= $months[$ss['start_month']-1] ?> – <?= $months[$ss['end_month']-1] ?></div>
        </div>
        <?php
        $isActive = ($currentMonth >= $ss['start_month'] && $currentMonth <= $ss['end_month']) ||
                    ($ss['start_month'] > $ss['end_month'] && ($currentMonth >= $ss['start_month'] || $currentMonth <= $ss['end_month']));
        ?>
        <span style="font-size:11px;font-weight:bold;padding:3px 10px;border-radius:20px;background:<?= $isActive?'#e6f9e6':'#f0e8e0' ?>;color:<?= $isActive?'#2e7d32':'#888' ?>;">
          <?= $isActive ? '✅ Available now' : '🕐 Seasonal' ?>
        </span>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Reviews -->
  <div class="card">
    <div class="card-title">⭐ Reviews</div>

    <!-- Average -->
    <div class="avg-box">
      <?php if (count($reviews) > 0): ?>
        <span class="avg-num"><?= $avgRating ?></span>
        <div>
          <div class="avg-stars"><?= str_repeat('★', round($avgRating)) . str_repeat('☆', 5 - round($avgRating)) ?></div>
          <div class="avg-count"><?= count($reviews) ?> review<?= count($reviews) > 1 ? 's' : '' ?></div>
        </div>
      <?php else: ?>
        <span style="color:#aaa;font-style:italic;">No ratings yet — be the first!</span>
      <?php endif; ?>
    </div>

    <!-- Write review -->
    <div class="write-review" style="margin-bottom:24px;">
      <h4>Write a Review</h4>
      <form method="POST" enctype="multipart/form-data">
        <div class="star-input" id="starInput">
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <span>★</span>
          <?php endfor; ?>
        </div>
        <input type="hidden" name="rating" id="ratingInput" value="0">
        <textarea name="review_text" placeholder="Share your experience..."></textarea>
        <div style="margin-top:10px;">
          <label style="font-size:13px;color:#634b49;font-weight:bold;display:block;margin-bottom:6px;">📷 Attach a photo (optional)</label>
          <input type="file" name="review_photo" accept="image/*" style="font-size:13px;"
                 onchange="previewPhoto(this)">
          <img id="photoPreview" style="display:none;margin-top:8px;max-width:180px;border-radius:8px;">
        </div>
        <?php if ($reviewError): ?><div class="err"><?= htmlspecialchars($reviewError) ?></div><?php endif; ?>
        <?php if ($reviewSuccess): ?><div class="suc"><?= htmlspecialchars($reviewSuccess) ?></div><?php endif; ?>
        <button type="submit" class="submit-btn" style="margin-top:12px;">Post Review</button>
      </form>
    </div>

    <!-- Reviews list -->
    <?php if (empty($reviews)): ?>
      <p class="no-reviews">No reviews yet. Share your experience!</p>
    <?php else: ?>
      <?php foreach ($reviews as $r): ?>
        <div class="review-card">
          <div class="review-top">
            <span class="reviewer"><?= htmlspecialchars($r['user_name']) ?></span>
            <span class="r-stars"><?= str_repeat('★', $r['rating']) . str_repeat('☆', 5 - $r['rating']) ?></span>
          </div>
          <div class="r-date"><?= date('d M Y', strtotime($r['created_at'])) ?></div>
          <div class="r-text"><?= htmlspecialchars($r['review_text']) ?></div>
          <?php if (!empty($r['photo_url'])): ?>
            <img src="<?= htmlspecialchars($r['photo_url']) ?>" style="margin-top:8px;max-width:200px;border-radius:8px;display:block;" alt="Review photo">
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>

<footer><p>© 2026 Bite by Bite - Mumbai | Created by Shreya Sakala, Gayatri Bedekar & Palak Sanklecha</p></footer>

<script>
// ─── ROUTE FEATURE ───────────────────────────────────────────
const SPOT_LAT = <?= $spot['latitude'] ?? 'null' ?>;
const SPOT_LNG = <?= $spot['longitude'] ?? 'null' ?>;
const SPOT_NAME = "<?= addslashes($spot['name']) ?>";

function getRoute(){
    if(!SPOT_LAT || !SPOT_LNG){
        document.getElementById('routeStatus').innerText = 'No coordinates set for this spot.';
        return;
    }
    const btn = document.getElementById('routeBtn');
    const status = document.getElementById('routeStatus');
    btn.disabled = true;
    btn.innerText = '⏳ Getting your location...';
    status.innerText = '';

    if(!navigator.geolocation){
        status.innerText = 'Geolocation not supported by your browser.';
        btn.disabled = false; btn.innerText = '📍 Get Route from My Location';
        return;
    }

    navigator.geolocation.getCurrentPosition(
        pos => {
            const userLat = pos.coords.latitude;
            const userLng = pos.coords.longitude;
            btn.innerText = '✅ Route Loaded';

            // Show route on map using OpenStreetMap + OSRM (no API key needed)
            const routeUrl = `https://www.openstreetmap.org/directions?engine=osrm_car&route=${userLat},${userLng};${SPOT_LAT},${SPOT_LNG}`;

            // Show route iframe
            const iframe = document.getElementById('routeMapIframe');
            iframe.src = `https://www.openstreetmap.org/export/embed.html?bbox=${Math.min(userLng,SPOT_LNG)-0.02},${Math.min(userLat,SPOT_LAT)-0.02},${Math.max(userLng,SPOT_LNG)+0.02},${Math.max(userLat,SPOT_LAT)+0.02}&layer=mapnik&marker=${SPOT_LAT},${SPOT_LNG}`;
            document.getElementById('routeMapWrap').style.display = 'block';
            document.getElementById('staticMap') && (document.getElementById('staticMap').style.display = 'none');

            // Calculate distance (Haversine)
            const R = 6371;
            const dLat = (SPOT_LAT - userLat) * Math.PI / 180;
            const dLng = (SPOT_LNG - userLng) * Math.PI / 180;
            const a = Math.sin(dLat/2)*Math.sin(dLat/2) +
                      Math.cos(userLat*Math.PI/180)*Math.cos(SPOT_LAT*Math.PI/180)*
                      Math.sin(dLng/2)*Math.sin(dLng/2);
            const distKm = R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

            // Estimate times (average walking 5km/h, driving 25km/h in Mumbai)
            const walkMins  = Math.round((distKm / 5) * 60);
            const driveMins = Math.round((distKm / 25) * 60);

            function fmtTime(mins){
                if(mins < 60) return mins + ' min';
                return Math.floor(mins/60) + 'h ' + (mins%60) + 'min';
            }

            document.getElementById('walkTime').innerText  = fmtTime(walkMins);
            document.getElementById('driveTime').innerText = fmtTime(driveMins);
            document.getElementById('distanceVal').innerText = distKm < 1
                ? Math.round(distKm * 1000) + ' m'
                : distKm.toFixed(1) + ' km';

            // Google Maps directions deep link
            document.getElementById('mapsLink').href =
                `https://www.google.com/maps/dir/${userLat},${userLng}/${SPOT_LAT},${SPOT_LNG}`;

            // Show travel strip
            document.getElementById('travelStrip').style.display = 'flex';

            showToast(`📍 ${distKm.toFixed(1)}km away · ~${fmtTime(driveMins)} by car`);
        },
        err => {
            status.innerText = 'Could not get your location. Please allow location access.';
            btn.disabled = false; btn.innerText = '📍 Get Route from My Location';
        }
    );
}

function toggleCheckin(){
    const body = new FormData();
    body.append('spot_id', <?= $spot_id ?>);
    fetch('checkin.php', { method:'POST', body })
    .then(r => r.json())
    .then(data => {
        if(!data.success) return;
        checkedIn = data.action === 'added';
        const btn = document.getElementById('checkinBtn');
        btn.innerText     = checkedIn ? '✅ Visited' : '📍 Check In';
        btn.style.background  = checkedIn ? '#e6f9e6' : 'white';
        btn.style.borderColor = checkedIn ? '#2e7d32' : '#ddd';
        btn.style.color       = checkedIn ? '#2e7d32' : '#634b49';
        if(checkedIn && data.trail_complete)
            showToast('🏅 Trail complete! You earned a badge!');
        else
            showToast(checkedIn ? 'Checked in! ✅' : 'Check-in removed');
    });
}

function previewPhoto(input){
    const preview = document.getElementById('photoPreview');
    if(input.files && input.files[0]){
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    }
}

function showToast(msg){
    const t = document.getElementById('toast');
    t.innerText = msg; t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2500);
}

function toggleFav(){
    const body = new FormData();
    body.append('spot_id', <?= $spot_id ?>);
    fetch('toggle_favourite.php', { method:'POST', body })
    .then(r => r.json())
    .then(data => {
        if(!data.success) return;
        faved = data.action === 'added';
        const btn = document.getElementById('favBtn');
        btn.innerText = faved ? '❤️ Saved' : '🤍 Save to Favourites';
        btn.style.background    = faved ? '#fff5f5' : 'white';
        btn.style.borderColor   = faved ? '#e57373' : '#ddd';
        btn.style.color         = faved ? '#b71c1c' : '#634b49';
        showToast(faved ? 'Saved to favourites ❤️' : 'Removed from favourites');
    });
}

function shareSpot(){
    navigator.clipboard.writeText(window.location.href)
        .then(() => showToast('Link copied to clipboard!'));
}

// ── STAR RATING ──
let selectedRating = 0;

function setRating(r) {
    selectedRating = r;
    document.getElementById('ratingInput').value = r;
    document.querySelectorAll('#starInput span').forEach((s, i) => {
        s.style.color = i < r ? '#f5a623' : '#ccc';
    });
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('#starInput span').forEach((s, i) => {
        s.addEventListener('mouseover', function() {
            document.querySelectorAll('#starInput span').forEach((x, j) => {
                x.style.color = j <= i ? '#f5a623' : '#ccc';
            });
        });
        s.addEventListener('mouseout', function() {
            document.querySelectorAll('#starInput span').forEach((x, j) => {
                x.style.color = j < selectedRating ? '#f5a623' : '#ccc';
            });
        });
        s.addEventListener('click', function() {
            setRating(i + 1);
        });
    });
});
</script>
</body>
</html>
