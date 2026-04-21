<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];
$displayName = !empty($user['username']) ? $user['username'] : $user['name'];

// Popular trails (ranked by review count)
$popularTrails = $conn->query("
    SELECT t.*, COUNT(r.id) as review_count
    FROM trails t
    LEFT JOIN food_spots fs ON fs.trail_id = t.id
    LEFT JOIN reviews r ON r.spot_id = fs.id
    GROUP BY t.id
    ORDER BY review_count DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Time-based suggestion
// Set IST timezone
date_default_timezone_set('Asia/Kolkata');
$hour = (int) date('H');
if      ($hour >= 6  && $hour < 11) $suggestion = ['icon'=>'🌅','title'=>'Good morning! Start your day right.','desc'=>'The Morning Breakfast Trail is perfect right now.','slug'=>'morning'];
elseif  ($hour >= 11 && $hour < 15) $suggestion = ['icon'=>'☀️','title'=>'Lunchtime in Mumbai!','desc'=>'The Dadar Trail has the best midday Maharashtrian food.','slug'=>'dadar'];
elseif  ($hour >= 15 && $hour < 18) $suggestion = ['icon'=>'🧋','title'=>'Afternoon snack time?','desc'=>'Juhu Beach chaat is perfect for a relaxed evening snack.','slug'=>'juhu'];
elseif  ($hour >= 18 && $hour < 21) $suggestion = ['icon'=>'🌆','title'=>'Evening out in Bandra?','desc'=>'Bandra comes alive at dusk — cafés, desserts and more.','slug'=>'bandra'];
else                                  $suggestion = ['icon'=>'🌙','title'=>'Night owl? Mumbai never sleeps.','desc'=>'The Night Street Food Trail is made for late-night explorers.','slug'=>'night'];

// Find trail ID from slug
$suggTrail = null;
foreach ($conn->query("SELECT * FROM trails")->fetch_all(MYSQLI_ASSOC) as $t) {
    if ($t['slug'] === $suggestion['slug']) { $suggTrail = $t; break; }
}

// Live stats
$totalTrails  = $conn->query("SELECT COUNT(*) as c FROM trails")->fetch_assoc()['c'];
$totalSpots   = $conn->query("SELECT COUNT(*) as c FROM food_spots")->fetch_assoc()['c'];
$totalReviews = $conn->query("SELECT COUNT(*) as c FROM reviews")->fetch_assoc()['c'];
$totalUsers   = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bite by Bite - Mumbai</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:Arial,Helvetica,sans-serif; }
body { background:#fff8f0; color:#333; }

/* ── HEADER ── */
header {
    background:#fff1b5; padding:15px 40px;
    display:flex; justify-content:space-between; align-items:center;
    position:sticky; top:0; z-index:100;
    box-shadow:0 2px 8px rgba(0,0,0,0.08);
}
header h1 { color:#634b49; font-size:24px; }

/* Dashboard dropdown — plain brown text, right-aligned */
.user-menu { position:relative; }
.user-menu-trigger {
    font-weight:bold; color:#634b49; font-size:14px;
    cursor:pointer; display:flex; align-items:center; gap:5px;
    background:none; border:none; padding:0;
}
.user-menu-trigger:hover { color:#43302e; }
.user-menu-trigger .caret { font-size:11px; }

.dropdown {
    display:none; position:absolute; right:0; top:calc(100% + 10px);
    background:white; border-radius:12px;
    box-shadow:0 8px 28px rgba(0,0,0,0.13);
    min-width:210px; overflow:hidden; z-index:200;
    border:0.5px solid #e8ddd5;
}
.dropdown.open { display:block; }
.dropdown-header {
    background:#fff1b5; padding:14px 18px;
    font-size:13px; color:#888; font-weight:normal;
    border-bottom:1px solid #e8ddd5;
}
.dropdown-header strong { display:block; font-size:15px; color:#634b49; font-weight:bold; }
.dropdown a, .dropdown button {
    display:flex; align-items:center; gap:10px;
    padding:11px 18px; text-decoration:none; color:#333;
    font-size:14px; background:none; border:none;
    width:100%; text-align:left; cursor:pointer; transition:background 0.15s;
}
.dropdown a:hover, .dropdown button:hover { background:#c1dbe8; }
.dropdown .divider { height:1px; background:#f0e8e0; margin:4px 0; }
.dropdown .logout-item { color:#b71c1c; }
.dropdown .logout-item:hover { background:#fdecea; }

/* ── HERO ── */
.hero {
    background: linear-gradient(rgba(0,0,0,0.5),rgba(0,0,0,0.5)),
        url("https://static.standard.co.uk/2024/10/07/13/27/TOL_Kricket-RebeccaHopePhotography-3970.jpg?trim=361,0,362,0&quality=75&auto=webp&width=960");
    background-size:cover; background-position:center;
    color:white; text-align:center;
    padding:110px 20px; position:relative; overflow:hidden;
}
.hero h2 { font-size:42px; margin-bottom:15px; position:relative; z-index:2; }
.hero p   { font-size:18px; margin-bottom:28px; opacity:0.9; position:relative; z-index:2; }
.hero-btns { display:flex; gap:14px; justify-content:center; flex-wrap:wrap; position:relative; z-index:2; }

.btn { background:#fff1b5; color:#634b49; padding:13px 28px; border:none; border-radius:8px; cursor:pointer; text-decoration:none; font-weight:bold; font-size:15px; display:inline-block; transition:background 0.2s; }
.btn:hover { background:#c1dbe8; }

/* Surprise Me — original outline style on hero */
.btn-surprise { background:rgba(255,255,255,0.15); color:white; padding:13px 28px; border:2px solid rgba(255,255,255,0.6); border-radius:8px; cursor:pointer; text-decoration:none; font-weight:bold; font-size:15px; display:inline-block; transition:background 0.2s; }
.btn-surprise:hover { background:rgba(255,255,255,0.28); }

/* ── COFFEE ANIMATION ── */
.coffee-scene { position:absolute; bottom:0; right:60px; width:120px; height:180px; z-index:1; pointer-events:none; }
.food-float { position:absolute; z-index:1; pointer-events:none; font-size:28px; opacity:0.55; animation:floatDrift 6s ease-in-out infinite; }
@keyframes floatDrift { 0%,100%{transform:translateY(0px) rotate(0deg);} 50%{transform:translateY(-14px) rotate(6deg);} }
.food-float:nth-child(2){animation-delay:1.2s;animation-duration:7s;}
.food-float:nth-child(3){animation-delay:2.4s;animation-duration:5.5s;}
.food-float:nth-child(4){animation-delay:0.6s;animation-duration:8s;}
@keyframes steamRise1{0%{transform:translateY(0) scaleX(1);opacity:0.7;}50%{transform:translateY(-30px) scaleX(1.3);opacity:0.4;}100%{transform:translateY(-60px) scaleX(0.8);opacity:0;}}
@keyframes steamRise2{0%{transform:translateY(0) scaleX(1);opacity:0.6;}50%{transform:translateY(-25px) scaleX(0.8);opacity:0.35;}100%{transform:translateY(-55px) scaleX(1.2);opacity:0;}}
@keyframes steamRise3{0%{transform:translateY(0) scaleX(1);opacity:0.5;}50%{transform:translateY(-35px) scaleX(1.1);opacity:0.3;}100%{transform:translateY(-65px) scaleX(0.9);opacity:0;}}
@keyframes spillSpread{0%{transform:scaleX(0.3) scaleY(0.5);opacity:0;}30%{opacity:0.85;}70%{transform:scaleX(1.1) scaleY(1);opacity:0.7;}100%{transform:scaleX(1) scaleY(1);opacity:0.65;}}
@keyframes spillDrip{0%{transform:scaleY(0);opacity:0;}40%{opacity:0.8;}100%{transform:scaleY(1);opacity:0.6;}}
@keyframes cupTilt{0%,100%{transform:rotate(0deg);}20%{transform:rotate(-18deg) translateX(-6px);}60%{transform:rotate(-15deg) translateX(-4px);}}
@media(prefers-reduced-motion:no-preference){
  .steam-1{animation:steamRise1 2.2s ease-in infinite;}
  .steam-2{animation:steamRise2 2.5s ease-in infinite 0.4s;}
  .steam-3{animation:steamRise3 2.0s ease-in infinite 0.9s;}
  .spill-blob{animation:spillSpread 1.2s cubic-bezier(.22,.68,0,1.2) forwards;}
  .spill-drip{animation:spillDrip 1.4s cubic-bezier(.22,.68,0,1.2) forwards 0.3s;}
  .cup-group{animation:cupTilt 1.8s cubic-bezier(.22,.68,0,1.2) forwards;}
}

/* ── TIME BANNER ── */
.banner-wrap { max-width:1100px; margin:28px auto 0; padding:0 20px; }
.banner-card { background:linear-gradient(135deg,#634b49,#8b6b68); color:white; border-radius:12px; padding:18px 24px; display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
.banner-icon { font-size:32px; flex-shrink:0; }
.banner-text h3 { font-size:16px; margin-bottom:4px; }
.banner-text p  { font-size:13px; opacity:0.85; }
.banner-btn { background:#fff1b5; color:#634b49; padding:8px 18px; border-radius:20px; font-weight:bold; font-size:13px; text-decoration:none; white-space:nowrap; margin-left:auto; flex-shrink:0; }
.banner-btn:hover { background:#c1dbe8; }

/* ── ABOUT ── */
.about { padding:60px 20px; text-align:center; max-width:900px; margin:auto; }
.about h2 { margin-bottom:20px; color:#634b49; font-size:26px; }
.about p  { line-height:1.7; color:#555; font-size:15px; }

/* ── TRAILS ── */
.trails { padding:50px 20px; background:#e6f7ff; }
.trails h2    { text-align:center; margin-bottom:10px; color:#634b49; font-size:24px; }
.trails-sub   { text-align:center; color:#888; font-size:14px; margin-bottom:30px; }
.trail-container { display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:20px; max-width:1100px; margin:auto; }
.trail-card   { background:white; padding:22px; border-radius:12px; box-shadow:0 5px 15px rgba(0,0,0,0.08); text-align:center; transition:transform 0.2s; }
.trail-card:hover { transform:translateY(-2px); }
.trail-card h3 { margin-bottom:6px; color:#634b49; font-size:17px; }
.trail-meta    { font-size:12px; color:#888; margin-bottom:10px; }
.trail-card p  { font-size:14px; margin-bottom:14px; color:#555; line-height:1.6; }
.trails-footer { text-align:center; margin-top:28px; }

/* ── STATS BAR ── */
.stats-bar { background:#43302e; color:white; padding:30px 20px; }
.stats-inner { display:flex; justify-content:center; gap:60px; max-width:800px; margin:auto; flex-wrap:wrap; }
.stat-item { text-align:center; }
.stat-num  { font-size:32px; font-weight:bold; color:#fff1b5; }
.stat-lbl  { font-size:13px; opacity:0.8; margin-top:4px; }

footer { background:#43302e; color:white; text-align:center; padding:20px; border-top:1px solid rgba(255,255,255,0.1); }
footer p { margin:0; color:white; }

@media(max-width:600px){
    header{padding:15px 20px;} .hero h2{font-size:28px;}
    .stats-inner{gap:30px;} .banner-card{flex-direction:column;text-align:center;}
    .banner-btn{margin-left:0;} .coffee-scene{display:none;}
}
</style>
</head>
<body>

<header>
  <h1>Bite by Bite - Mumbai</h1>

  <div class="user-menu" id="userMenu">
    <button class="user-menu-trigger" onclick="toggleDropdown()">
      Hello, <?= htmlspecialchars($displayName) ?>
      <span class="caret">▾</span>
    </button>
    <div class="dropdown" id="dropdown">
      <div class="dropdown-header">
        Signed in as<strong><?= htmlspecialchars($displayName) ?></strong>
      </div>
      <a href="index.php">🏠 Home</a>
      <a href="trails.php">🗺️ Trails</a>
      <a href="favourites.php">❤️ My Favourites</a>
      <a href="food-passport.php">📒 Food Passport</a>
      <a href="nearby.php">📍 Nearby Spots</a>
      <a href="dish-search.php">🍽️ Find a Dish</a>
      <a href="budget-planner.php">💰 Budget Planner</a>
      <div class="divider"></div>
      <a href="profile.php">👤 My Profile</a>
      <?php if ($user['role'] === 'admin'): ?>
        <a href="admin.php">⚙️ Admin Panel</a>
      <?php endif; ?>
      <div class="divider"></div>
      <a class="logout-item" href="logout.php">🚪 Logout</a>
    </div>
  </div>
</header>
<?php include 'cookie-banner.php'; ?>

<!-- ── HERO ── -->
<section class="hero">

  <!-- Floating food icons -->
  <span class="food-float" style="left:5%;top:18%;" aria-hidden="true">🍜</span>
  <span class="food-float" style="left:12%;top:55%;" aria-hidden="true">🥙</span>
  <span class="food-float" style="left:3%;top:72%;" aria-hidden="true">🍡</span>
  <span class="food-float" style="right:14%;top:20%;" aria-hidden="true">🥞</span>

  <!-- Coffee cup animation -->
  <div class="coffee-scene" aria-hidden="true">
    <svg viewBox="0 0 120 180" xmlns="http://www.w3.org/2000/svg" width="120" height="180">
      <ellipse class="spill-blob" cx="38" cy="168" rx="44" ry="10" fill="#3d1f0a" opacity="0.65" style="transform-origin:38px 168px;"/>
      <rect class="spill-drip" x="68" y="128" width="6" height="28" rx="3" fill="#5c2e0e" opacity="0.7" style="transform-origin:71px 128px;"/>
      <g class="cup-group" style="transform-origin:60px 130px;">
        <ellipse cx="60" cy="150" rx="38" ry="8" fill="#d4a96a"/>
        <ellipse cx="60" cy="148" rx="38" ry="8" fill="#e8c48a"/>
        <path d="M30 110 Q28 148 42 150 L78 150 Q92 148 90 110 Z" fill="#f5e6d0" stroke="#c9a87a" stroke-width="1.5"/>
        <ellipse cx="60" cy="110" rx="30" ry="7" fill="#eedcb8" stroke="#c9a87a" stroke-width="1"/>
        <ellipse cx="60" cy="111" rx="26" ry="5.5" fill="#5c2e0e"/>
        <path d="M54 111 Q60 108 66 111" fill="none" stroke="#8B4513" stroke-width="1.2" stroke-linecap="round" opacity="0.6"/>
        <path d="M90 118 Q108 118 108 130 Q108 142 90 142" fill="none" stroke="#d4a96a" stroke-width="5" stroke-linecap="round"/>
        <path d="M30 120 Q14 130 10 150 Q18 155 30 148 Q26 138 32 125 Z" fill="#5c2e0e" opacity="0.75"/>
      </g>
      <path class="steam-1" d="M48 108 Q44 96 50 86 Q56 76 50 66" fill="none" stroke="rgba(255,255,255,0.7)" stroke-width="3" stroke-linecap="round" style="transform-origin:49px 87px;"/>
      <path class="steam-2" d="M60 106 Q56 93 62 82 Q68 71 62 60" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="2.5" stroke-linecap="round" style="transform-origin:61px 83px;"/>
      <path class="steam-3" d="M72 109 Q68 96 74 85 Q80 74 74 63" fill="none" stroke="rgba(255,255,255,0.55)" stroke-width="2" stroke-linecap="round" style="transform-origin:73px 86px;"/>
    </svg>
  </div>

  <h2>Explore Mumbai's Food Culture</h2>
  <p>Discover iconic street food, cafés, and hidden gems — one bite at a time.</p>
  <div class="hero-btns">
    <a href="trails.php" class="btn">Start Exploring</a>
    <a href="surprise.php" class="btn-surprise">🎲 Surprise Me</a>
  </div>
</section>

<!-- ── TIME BANNER ── -->
<?php if ($suggTrail): ?>
<div class="banner-wrap">
  <div class="banner-card">
    <span class="banner-icon"><?= $suggestion['icon'] ?></span>
    <div class="banner-text">
      <h3><?= htmlspecialchars($suggestion['title']) ?></h3>
      <p><?= htmlspecialchars($suggestion['desc']) ?></p>
    </div>
    <a href="trail-details.php?id=<?= $suggTrail['id'] ?>" class="banner-btn">View Trail</a>
  </div>
</div>
<?php endif; ?>

<!-- ── ABOUT ── -->
<section class="about">
  <h2>About Bite by Bite</h2>
  <p>Bite by Bite – Mumbai is a web-based food itinerary platform that helps users explore Mumbai's diverse food culture through curated area-wise and time-based food trails. From street food in Dadar to seaside cafés in Bandra, plan your complete food journey across the city.</p>
</section>

<!-- ── POPULAR TRAILS ── -->
<section class="trails">
  <h2>Popular Food Trails</h2>
  <p class="trails-sub">Curated trails loved by our community</p>
  <div class="trail-container">
    <?php foreach ($popularTrails as $trail): ?>
      <div class="trail-card">
        <h3><?= htmlspecialchars($trail['name']) ?></h3>
        <div class="trail-meta">📍 <?= htmlspecialchars($trail['area']) ?> &nbsp;·&nbsp; ⭐ <?= $trail['review_count'] ?> reviews</div>
        <p><?= htmlspecialchars($trail['description']) ?></p>
        <a href="trail-details.php?id=<?= $trail['id'] ?>" class="btn">View Trail</a>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="trails-footer">
    <a href="trails.php" class="btn">See All Trails</a>
  </div>
</section>

<!-- ── STATS BAR ── -->
<div class="stats-bar">
  <div class="stats-inner">
    <div class="stat-item"><div class="stat-num"><?= $totalTrails ?></div><div class="stat-lbl">Food Trails</div></div>
    <div class="stat-item"><div class="stat-num"><?= $totalSpots ?></div><div class="stat-lbl">Food Spots</div></div>
    <div class="stat-item"><div class="stat-num"><?= $totalReviews ?></div><div class="stat-lbl">Reviews</div></div>
    <div class="stat-item"><div class="stat-num"><?= $totalUsers ?></div><div class="stat-lbl">Explorers</div></div>
  </div>
</div>

<footer>
  <p>© 2026 Bite by Bite - Mumbai | Created by Shreya Sakala, Gayatri Bedekar & Palak Sanklecha
  &nbsp;·&nbsp; <a href="cookie-preferences.php" style="color:#fff1b5;">Cookie Preferences</a></p>
</footer>

<script>
function toggleDropdown(){
    document.getElementById("dropdown").classList.toggle("open");
}
document.addEventListener("click", function(e){
    const menu = document.getElementById("userMenu");
    if(menu && !menu.contains(e.target))
        document.getElementById("dropdown").classList.remove("open");
});
</script>

</body>
</html>
