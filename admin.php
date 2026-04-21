<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
$user   = $_SESSION['user'];
$msg    = "";
$err    = "";
$action = $_POST['action'] ?? '';

// ─── TRAIL ACTIONS ────────────────────────────────────────────
if ($action === 'add_trail') {
    $name=trim($_POST['name']??''); $area=trim($_POST['area']??''); $desc=trim($_POST['desc']??'');
    $cost=trim($_POST['cost']??''); $duration=trim($_POST['duration']??'');
    $slug=strtolower(preg_replace('/[^a-z0-9]+/i','-',$name));
    if(!$name||!$area||!$desc){ $err="Name, area and description are required."; }
    else {
        $stmt=$conn->prepare("INSERT INTO trails (slug,name,area,description,cost,duration) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("ssssss",$slug,$name,$area,$desc,$cost,$duration);
        $stmt->execute() ? $msg="Trail added!" : $err="Failed — slug may already exist.";
        $stmt->close();
    }
}
if ($action === 'edit_trail') {
    $id=intval($_POST['id']); $name=trim($_POST['name']??''); $area=trim($_POST['area']??'');
    $desc=trim($_POST['desc']??''); $cost=trim($_POST['cost']??''); $duration=trim($_POST['duration']??'');
    if(!$name||!$area||!$desc){ $err="Name, area and description are required."; }
    else {
        $stmt=$conn->prepare("UPDATE trails SET name=?,area=?,description=?,cost=?,duration=? WHERE id=?");
        $stmt->bind_param("sssssi",$name,$area,$desc,$cost,$duration,$id);
        $stmt->execute() ? $msg="Trail updated!" : $err="Update failed.";
        $stmt->close();
    }
}
if ($action === 'delete_trail') {
    $id=intval($_POST['id']);
    $stmt=$conn->prepare("DELETE FROM trails WHERE id=?");
    $stmt->bind_param("i",$id); $stmt->execute(); $stmt->close();
    $msg="Trail deleted.";
}

// ─── SPOT ACTIONS ─────────────────────────────────────────────
if ($action === 'add_spot' || $action === 'edit_spot') {
    $trail_id=intval($_POST['trail_id']??0); $name=trim($_POST['name']??'');
    $area=trim($_POST['area']??''); $type=$_POST['type']??'both';
    $price=trim($_POST['price']??''); $desc=trim($_POST['desc']??'');
    $image=trim($_POST['image']??''); $dishes=trim($_POST['dishes']??'');
    $open=trim($_POST['open_time']??''); $close=trim($_POST['close_time']??'');
    $days=trim($_POST['days']??''); $map=trim($_POST['map_src']??'');
    $latRaw=trim($_POST['latitude']??''); $lngRaw=trim($_POST['longitude']??'');
    $lat = $latRaw !== '' ? floatval($latRaw) : null;
    $lng = $lngRaw !== '' ? floatval($lngRaw) : null;

    if(!$trail_id||!$name||!$area||!$desc){ $err="Trail, name, area and description are required."; }
    elseif($action==='add_spot'){
        $slug=strtolower(preg_replace('/[^a-z0-9]+/i','-',$name)).'-'.time();
        $stmt=$conn->prepare("INSERT INTO food_spots (slug,trail_id,name,area,description,type,price_range,image_url,open_time,close_time,days_open,map_src,latitude,longitude) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("sissssssssssdd",$slug,$trail_id,$name,$area,$desc,$type,$price,$image,$open,$close,$days,$map,$lat,$lng);
        if($stmt->execute()){
            $sid=$conn->insert_id;
            foreach(explode(',',$dishes) as $d){ $d=trim($d); if($d){ $ds=$conn->prepare("INSERT INTO dishes (spot_id,dish_name) VALUES (?,?)"); $ds->bind_param("is",$sid,$d); $ds->execute(); $ds->close(); } }
            $msg="Food spot added!";
        } else { $err="Failed to add spot."; }
        $stmt->close();
    } else {
        $id=intval($_POST['id']);
        $stmt=$conn->prepare("UPDATE food_spots SET trail_id=?,name=?,area=?,description=?,type=?,price_range=?,image_url=?,open_time=?,close_time=?,days_open=?,map_src=?,latitude=?,longitude=? WHERE id=?");
        $stmt->bind_param("issssssssssddi",$trail_id,$name,$area,$desc,$type,$price,$image,$open,$close,$days,$map,$lat,$lng,$id);
        if($stmt->execute()){
            $del=$conn->prepare("DELETE FROM dishes WHERE spot_id=?"); $del->bind_param("i",$id); $del->execute(); $del->close();
            foreach(explode(',',$dishes) as $d){ $d=trim($d); if($d){ $ds=$conn->prepare("INSERT INTO dishes (spot_id,dish_name) VALUES (?,?)"); $ds->bind_param("is",$id,$d); $ds->execute(); $ds->close(); } }
            $msg="Food spot updated!";
        } else { $err="Update failed."; }
        $stmt->close();
    }
}
if ($action === 'delete_spot') {
    $id=intval($_POST['id']);
    $stmt=$conn->prepare("DELETE FROM food_spots WHERE id=?");
    $stmt->bind_param("i",$id); $stmt->execute(); $stmt->close();
    $msg="Food spot deleted.";
}

// ─── USER ACTIONS ─────────────────────────────────────────────
if ($action === 'ban_user') {
    $id=intval($_POST['id']); $ban=intval($_POST['ban']);
    $stmt=$conn->prepare("UPDATE users SET is_banned=? WHERE id=? AND role!='admin'");
    $stmt->bind_param("ii",$ban,$id); $stmt->execute(); $stmt->close();
    $msg=$ban ? "User banned." : "User unbanned.";
}
if ($action === 'promote_user') {
    $id=intval($_POST['id']);
    $stmt=$conn->prepare("UPDATE users SET role='admin' WHERE id=?");
    $stmt->bind_param("i",$id); $stmt->execute(); $stmt->close();
    $msg="User promoted to admin.";
}
if ($action === 'delete_user') {
    $id=intval($_POST['id']);
    $stmt=$conn->prepare("DELETE FROM users WHERE id=? AND role!='admin'");
    $stmt->bind_param("i",$id); $stmt->execute(); $stmt->close();
    $msg="User deleted.";
}

// ─── SEASONAL ACTIONS ─────────────────────────────────────────
if ($action === 'add_seasonal') {
    $spot_id=intval($_POST['spot_id']); $dish=trim($_POST['dish_name']??'');
    $season=trim($_POST['season_name']??''); $sm=intval($_POST['start_month']); $em=intval($_POST['end_month']);
    if(!$spot_id||!$dish||!$season){ $err="All seasonal fields are required."; }
    else {
        $stmt=$conn->prepare("INSERT INTO seasonal_specials (spot_id,dish_name,season_name,start_month,end_month) VALUES (?,?,?,?,?)");
        $stmt->bind_param("issii",$spot_id,$dish,$season,$sm,$em);
        $stmt->execute() ? $msg="Seasonal special added!" : $err="Failed.";
        $stmt->close();
    }
}
if ($action === 'delete_seasonal') {
    $id=intval($_POST['id']);
    $stmt=$conn->prepare("DELETE FROM seasonal_specials WHERE id=?");
    $stmt->bind_param("i",$id); $stmt->execute(); $stmt->close();
    $msg="Seasonal special removed.";
}

// ─── REVIEW ACTIONS ───────────────────────────────────────────
if ($action === 'delete_review') {
    $id=intval($_POST['id']);
    $stmt=$conn->prepare("DELETE FROM reviews WHERE id=?");
    $stmt->bind_param("i",$id); $stmt->execute(); $stmt->close();
    $msg="Review deleted.";
}

// ─── FETCH DATA ───────────────────────────────────────────────
$trails    = $conn->query("SELECT * FROM trails ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$spots     = $conn->query("SELECT fs.*,t.name AS trail_name FROM food_spots fs JOIN trails t ON fs.trail_id=t.id ORDER BY fs.name")->fetch_all(MYSQLI_ASSOC);
$reviews   = $conn->query("SELECT r.*,u.name AS user_name,fs.name AS spot_name FROM reviews r JOIN users u ON r.user_id=u.id JOIN food_spots fs ON r.spot_id=fs.id ORDER BY r.created_at DESC")->fetch_all(MYSQLI_ASSOC);
$users     = $conn->query("SELECT * FROM users ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$seasonals = $conn->query("SELECT ss.*,fs.name AS spot_name FROM seasonal_specials ss JOIN food_spots fs ON ss.spot_id=fs.id ORDER BY ss.season_name")->fetch_all(MYSQLI_ASSOC);
$checkinCount = $conn->query("SELECT COUNT(*) AS c FROM checkins")->fetch_assoc()['c'] ?? 0;

// Analytics
$topSpot    = $conn->query("SELECT fs.name,COUNT(r.id) AS cnt FROM reviews r JOIN food_spots fs ON r.spot_id=fs.id GROUP BY r.spot_id ORDER BY cnt DESC LIMIT 1")->fetch_assoc();
$topTrail   = $conn->query("SELECT t.name,AVG(tr.rating) AS avg_r FROM trail_ratings tr JOIN trails t ON tr.trail_id=t.id GROUP BY tr.trail_id ORDER BY avg_r DESC LIMIT 1")->fetch_assoc();
$topUser    = $conn->query("SELECT u.name,u.email,COUNT(r.id) AS cnt FROM reviews r JOIN users u ON r.user_id=u.id GROUP BY r.user_id ORDER BY cnt DESC LIMIT 1")->fetch_assoc();
$reviewsPerMonth = $conn->query("SELECT DATE_FORMAT(created_at,'%b %Y') AS month,COUNT(*) AS cnt FROM reviews GROUP BY month ORDER BY MIN(created_at) DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC);
$spotReviews = $conn->query("SELECT fs.name,COUNT(r.id) AS cnt FROM reviews r JOIN food_spots fs ON r.spot_id=fs.id GROUP BY r.spot_id ORDER BY cnt DESC LIMIT 8")->fetch_all(MYSQLI_ASSOC);
$trailRatings= $conn->query("SELECT t.name,AVG(tr.rating) AS avg_r,COUNT(tr.id) AS cnt FROM trail_ratings tr JOIN trails t ON tr.trail_id=t.id GROUP BY tr.trail_id ORDER BY avg_r DESC")->fetch_all(MYSQLI_ASSOC);

$activeTab = $_GET['tab'] ?? 'dashboard';
$months=['January','February','March','April','May','June','July','August','September','October','November','December'];

function getSpotDishes($conn,$spot_id){
    $stmt=$conn->prepare("SELECT dish_name FROM dishes WHERE spot_id=?");
    $stmt->bind_param("i",$spot_id); $stmt->execute();
    $rows=$stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
    return implode(', ',array_column($rows,'dish_name'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Panel - Bite by Bite</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#fff8f0;font-family:Arial,Helvetica,sans-serif;color:#333;}
header{background:#fff1b5;padding:15px 40px;display:flex;justify-content:space-between;align-items:center;}
header h1{color:#634b49;font-size:22px;}
.header-right{display:flex;align-items:center;gap:14px;}
.admin-badge{background:#634b49;color:white;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:bold;}
.logout-btn{background:#fff1b5;color:#634b49;border:1px solid #634b49;padding:7px 16px;border-radius:6px;font-weight:bold;cursor:pointer;text-decoration:none;font-size:13px;}
.logout-btn:hover{background:#c1dbe8;}
.tab-bar{background:white;border-bottom:2px solid #fff1b5;display:flex;padding:0 40px;gap:2px;flex-wrap:wrap;justify-content:center;}
.tab-btn{display:inline-block;padding:13px 16px;text-decoration:none;font-size:13px;font-weight:bold;color:#888;border-bottom:3px solid transparent;margin-bottom:-2px;}
.tab-btn.active{color:#634b49;border-bottom-color:#634b49;}
.tab-btn:hover{color:#634b49;}
.main{max-width:1100px;margin:26px auto;padding:0 22px;}
.card{background:white;border-radius:12px;box-shadow:0 4px 14px rgba(0,0,0,0.07);padding:22px;margin-bottom:22px;}
.card-title{font-size:16px;font-weight:bold;color:#634b49;margin-bottom:16px;padding-bottom:10px;border-bottom:2px solid #fff1b5;}
.stats-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:22px;}
.stat-card{background:white;border-radius:10px;padding:16px;text-align:center;box-shadow:0 4px 14px rgba(0,0,0,0.07);}
.stat-num{font-size:26px;font-weight:bold;color:#634b49;}
.stat-lbl{font-size:11px;color:#888;margin-top:3px;}
.analytics-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:22px;}
.analytics-card{background:white;border-radius:12px;padding:18px;box-shadow:0 4px 14px rgba(0,0,0,0.07);}
.analytics-card h4{font-size:13px;color:#888;margin-bottom:8px;}
.analytics-card .val{font-size:16px;font-weight:bold;color:#634b49;}
.analytics-card .sub{font-size:12px;color:#aaa;margin-top:3px;}
.bar-row{display:flex;align-items:center;gap:8px;margin-bottom:7px;font-size:12px;}
.bar-label{width:130px;color:#888;text-align:right;flex-shrink:0;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;}
.bar-fill{height:18px;background:#fff1b5;border-radius:4px;min-width:4px;}
.bar-count{color:#634b49;font-weight:bold;min-width:28px;}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;}
.form-grid.one{grid-template-columns:1fr;}
.form-grid.three{grid-template-columns:1fr 1fr 1fr;}
.field{display:flex;flex-direction:column;gap:4px;}
.field label{font-size:13px;font-weight:bold;color:#634b49;}
.field input,.field select,.field textarea{padding:9px 12px;border:1px solid #ddd;border-radius:7px;font-size:14px;font-family:Arial,Helvetica,sans-serif;}
.field input:focus,.field select:focus,.field textarea:focus{outline:none;border-color:#634b49;}
.field textarea{resize:vertical;min-height:66px;}
.btn-primary{background:#fff1b5;color:#634b49;border:none;padding:10px 22px;border-radius:7px;font-weight:bold;cursor:pointer;font-size:14px;}
.btn-primary:hover{background:#c1dbe8;}
.btn-danger{background:#fdecea;color:#b71c1c;border:none;padding:5px 12px;border-radius:6px;font-size:12px;cursor:pointer;font-weight:bold;}
.btn-danger:hover{background:#f5c6c2;}
.btn-warn{background:#fff8e1;color:#f57f17;border:none;padding:5px 12px;border-radius:6px;font-size:12px;cursor:pointer;font-weight:bold;}
.btn-warn:hover{background:#ffe0b2;}
.btn-info{background:#e3f2fd;color:#1565c0;border:none;padding:5px 12px;border-radius:6px;font-size:12px;cursor:pointer;font-weight:bold;}
.btn-info:hover{background:#bbdefb;}
.btn-success{background:#e6f9e6;color:#2e7d32;border:none;padding:5px 12px;border-radius:6px;font-size:12px;cursor:pointer;font-weight:bold;}
.btn-success:hover{background:#c8e6c9;}
.item-row{display:flex;align-items:center;justify-content:space-between;padding:11px 14px;background:#fff8f0;border-radius:8px;border:0.5px solid #e8ddd5;margin-bottom:8px;gap:10px;}
.item-name{font-weight:bold;font-size:14px;color:#634b49;}
.item-meta{font-size:12px;color:#888;margin-top:2px;}
.spot-row{display:flex;align-items:center;gap:12px;padding:11px 14px;background:#fff8f0;border-radius:8px;border:0.5px solid #e8ddd5;margin-bottom:8px;}
.spot-thumb{width:48px;height:48px;border-radius:7px;object-fit:cover;flex-shrink:0;}
.spot-thumb-ph{width:48px;height:48px;border-radius:7px;background:linear-gradient(135deg,#fff1b5,#c1dbe8);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;}
.user-row{display:flex;align-items:center;justify-content:space-between;padding:12px 14px;background:#fff8f0;border-radius:8px;border:0.5px solid #e8ddd5;margin-bottom:8px;gap:10px;flex-wrap:wrap;}
.user-avatar{width:36px;height:36px;border-radius:50%;background:#fff1b5;display:flex;align-items:center;justify-content:center;font-weight:bold;color:#634b49;font-size:15px;flex-shrink:0;}
.user-info{flex:1;}
.user-name{font-weight:bold;font-size:14px;color:#634b49;}
.user-meta{font-size:12px;color:#888;}
.user-actions{display:flex;gap:6px;flex-wrap:wrap;}
.badge-pill{padding:3px 9px;border-radius:20px;font-size:11px;font-weight:bold;}
.badge-admin{background:#e8eaf6;color:#3949ab;}
.badge-user{background:#f3e5f5;color:#7b1fa2;}
.badge-banned{background:#fdecea;color:#b71c1c;}
.review-row{padding:13px 14px;background:#fff8f0;border-radius:8px;border:0.5px solid #e8ddd5;margin-bottom:8px;}
.review-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;}
.reviewer{font-weight:bold;font-size:14px;color:#634b49;}
.r-stars{color:#f5a623;}
.r-spot{font-size:12px;color:#888;margin-bottom:4px;}
.r-text{font-size:13px;color:#555;line-height:1.5;}
.seasonal-row{display:flex;align-items:center;justify-content:space-between;padding:11px 14px;background:#fff8f0;border-radius:8px;border:0.5px solid #e8ddd5;margin-bottom:8px;gap:10px;}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:1000;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal{background:white;border-radius:14px;padding:28px;max-width:620px;width:92%;max-height:88vh;overflow-y:auto;}
.modal h3{color:#634b49;font-size:18px;margin-bottom:18px;}
.modal-footer{display:flex;gap:10px;margin-top:16px;justify-content:flex-end;}
.btn-cancel{background:#f0e8e0;color:#634b49;border:none;padding:10px 20px;border-radius:7px;font-weight:bold;cursor:pointer;font-size:14px;}
.msg{padding:10px 14px;border-radius:7px;font-size:13px;margin-bottom:14px;}
.msg-ok{background:#e6f9e6;color:#2e7d32;}
.msg-err{background:#fdecea;color:#b71c1c;}
.empty{color:#aaa;font-style:italic;font-size:13px;padding:10px 0;}
footer{background:#43302e;color:white;text-align:center;padding:20px;margin-top:40px;}
footer p{margin:0;color:white;}
@media(max-width:700px){.form-grid,.form-grid.three{grid-template-columns:1fr;}.stats-grid{grid-template-columns:1fr 1fr 1fr;}.analytics-grid{grid-template-columns:1fr;}header{padding:15px 20px;}.tab-bar{padding:0 10px;}.main{padding:0 14px;}}
</style>
</head>
<body>

<header>
  <h1>Bite by Bite — Admin</h1>
  <div style="display:flex;align-items:center;gap:12px;">
    <span class="admin-badge">⚙️ Admin Panel</span>
    <div class="user-menu" id="userMenu">
      <button class="user-menu-trigger" onclick="bbAdminToggle()" style="font-weight:bold;color:#634b49;font-size:14px;cursor:pointer;display:flex;align-items:center;gap:5px;background:none;border:none;padding:0;font-family:inherit;">
        Hello, <?= htmlspecialchars(!empty($user['username']) ? $user['username'] : $user['name']) ?> <span style="font-size:11px;">▾</span>
      </button>
      <div class="dropdown" id="adminDropdown" style="display:none;position:absolute;right:0;top:calc(100% + 10px);background:white;border-radius:12px;box-shadow:0 8px 28px rgba(0,0,0,0.13);min-width:220px;overflow:hidden;z-index:200;border:0.5px solid #e8ddd5;">
        <div style="background:#fff1b5;padding:14px 18px;font-size:13px;color:#888;border-bottom:1px solid #e8ddd5;">
          Signed in as<strong style="display:block;font-size:15px;color:#634b49;font-weight:bold;margin-top:2px;"><?= htmlspecialchars(!empty($user['username']) ? $user['username'] : $user['name']) ?></strong>
        </div>
        <a href="index.php" style="display:flex;align-items:center;gap:10px;padding:11px 18px;text-decoration:none;color:#333;font-size:14px;transition:background 0.15s;">🏠 View Site</a>
        <a href="trails.php" style="display:flex;align-items:center;gap:10px;padding:11px 18px;text-decoration:none;color:#333;font-size:14px;transition:background 0.15s;">🗺️ Trails</a>
        <div style="height:1px;background:#f0e8e0;margin:4px 0;"></div>
        <a href="logout.php" style="display:flex;align-items:center;gap:10px;padding:11px 18px;text-decoration:none;color:#b71c1c;font-size:14px;transition:background 0.15s;">🚪 Logout</a>
      </div>
    </div>
  </div>
</header>
<?php include 'cookie-banner.php'; ?>
<script>
function bbAdminToggle(){
    var d=document.getElementById('adminDropdown');
    d.style.display=d.style.display==='block'?'none':'block';
}
document.querySelectorAll('#adminDropdown a').forEach(function(a){
    a.addEventListener('mouseover',function(){this.style.background='#c1dbe8';});
    a.addEventListener('mouseout',function(){this.style.background='';});
});
document.addEventListener('click',function(e){
    var m=document.getElementById('userMenu');
    if(m&&!m.contains(e.target))document.getElementById('adminDropdown').style.display='none';
});
</script>

<div class="tab-bar">
  <a href="?tab=dashboard" class="tab-btn <?= $activeTab==='dashboard'?'active':'' ?>">Dashboard</a>
  <a href="?tab=analytics" class="tab-btn <?= $activeTab==='analytics'?'active':'' ?>">Analytics</a>
  <a href="?tab=trails"    class="tab-btn <?= $activeTab==='trails'   ?'active':'' ?>">Trails</a>
  <a href="?tab=spots"     class="tab-btn <?= $activeTab==='spots'    ?'active':'' ?>">Food Spots</a>
  <a href="?tab=users"     class="tab-btn <?= $activeTab==='users'    ?'active':'' ?>">Users</a>
  <a href="?tab=reviews"   class="tab-btn <?= $activeTab==='reviews'  ?'active':'' ?>">Reviews</a>
  <a href="?tab=seasonal"  class="tab-btn <?= $activeTab==='seasonal' ?'active':'' ?>">Seasonal</a>
</div>

<div class="main">
<?php if($msg): ?><div class="msg msg-ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if($err): ?><div class="msg msg-err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<?php if($activeTab==='dashboard'): ?>
  <div class="stats-grid">
    <div class="stat-card"><div class="stat-num"><?= count($trails) ?></div><div class="stat-lbl">Trails</div></div>
    <div class="stat-card"><div class="stat-num"><?= count($spots) ?></div><div class="stat-lbl">Spots</div></div>
    <div class="stat-card"><div class="stat-num"><?= count($reviews) ?></div><div class="stat-lbl">Reviews</div></div>
    <div class="stat-card"><div class="stat-num"><?= count($users) ?></div><div class="stat-lbl">Users</div></div>
    <div class="stat-card"><div class="stat-num"><?= $checkinCount ?></div><div class="stat-lbl">Check-ins</div></div>
  </div>
  <div class="analytics-grid">
    <div class="analytics-card"><h4>Most reviewed spot</h4><?php if($topSpot): ?><div class="val"><?= htmlspecialchars($topSpot['name']) ?></div><div class="sub"><?= $topSpot['cnt'] ?> reviews</div><?php else: ?><div class="val" style="color:#aaa">No data</div><?php endif; ?></div>
    <div class="analytics-card"><h4>Highest rated trail</h4><?php if($topTrail): ?><div class="val"><?= htmlspecialchars($topTrail['name']) ?></div><div class="sub">avg <?= round($topTrail['avg_r'],1) ?> ★</div><?php else: ?><div class="val" style="color:#aaa">No data</div><?php endif; ?></div>
    <div class="analytics-card"><h4>Most active user</h4><?php if($topUser): ?><div class="val"><?= htmlspecialchars($topUser['name']) ?></div><div class="sub"><?= $topUser['cnt'] ?> reviews</div><?php else: ?><div class="val" style="color:#aaa">No data</div><?php endif; ?></div>
  </div>
  <div class="card">
    <div class="card-title">Reviews per month</div>
    <?php if(empty($reviewsPerMonth)): ?><p class="empty">No reviews yet.</p>
    <?php else: $maxCnt=max(array_column($reviewsPerMonth,'cnt'))?:1; foreach($reviewsPerMonth as $rm): ?>
      <div class="bar-row"><span class="bar-label"><?= $rm['month'] ?></span><div class="bar-fill" style="width:<?= round(($rm['cnt']/$maxCnt)*240) ?>px"></div><span class="bar-count"><?= $rm['cnt'] ?></span></div>
    <?php endforeach; endif; ?>
  </div>

<?php elseif($activeTab==='analytics'): ?>
  <div class="analytics-grid">
    <div class="analytics-card"><h4>Most reviewed spot</h4><?php if($topSpot): ?><div class="val"><?= htmlspecialchars($topSpot['name']) ?></div><div class="sub"><?= $topSpot['cnt'] ?> reviews</div><?php else: ?><div class="val" style="color:#aaa">No data</div><?php endif; ?></div>
    <div class="analytics-card"><h4>Highest rated trail</h4><?php if($topTrail): ?><div class="val"><?= htmlspecialchars($topTrail['name']) ?></div><div class="sub">avg <?= round($topTrail['avg_r'],1) ?> ★</div><?php else: ?><div class="val" style="color:#aaa">No data</div><?php endif; ?></div>
    <div class="analytics-card"><h4>Most active user</h4><?php if($topUser): ?><div class="val"><?= htmlspecialchars($topUser['name']) ?></div><div class="sub"><?= $topUser['cnt'] ?> reviews</div><?php else: ?><div class="val" style="color:#aaa">No data</div><?php endif; ?></div>
  </div>
  <div class="card">
    <div class="card-title">Reviews by spot (top 8)</div>
    <?php if(empty($spotReviews)): ?><p class="empty">No reviews yet.</p>
    <?php else: $maxR=max(array_column($spotReviews,'cnt'))?:1; foreach($spotReviews as $sr): ?>
      <div class="bar-row"><span class="bar-label" title="<?= htmlspecialchars($sr['name']) ?>"><?= htmlspecialchars($sr['name']) ?></span><div class="bar-fill" style="width:<?= round(($sr['cnt']/$maxR)*300) ?>px"></div><span class="bar-count"><?= $sr['cnt'] ?></span></div>
    <?php endforeach; endif; ?>
  </div>
  <div class="card">
    <div class="card-title">Trail average ratings</div>
    <?php if(empty($trailRatings)): ?><p class="empty">No trail ratings yet.</p>
    <?php else: foreach($trailRatings as $tr): ?>
      <div class="bar-row"><span class="bar-label"><?= htmlspecialchars($tr['name']) ?></span><div class="bar-fill" style="width:<?= round((floatval($tr['avg_r'])/5)*300) ?>px;background:#f5a623;"></div><span class="bar-count"><?= round($tr['avg_r'],1) ?>★</span></div>
    <?php endforeach; endif; ?>
  </div>

<?php elseif($activeTab==='trails'): ?>
  <div class="card">
    <div class="card-title">Add new trail</div>
    <form method="POST">
      <input type="hidden" name="action" value="add_trail">
      <div class="form-grid">
        <div class="field"><label>Trail name</label><input type="text" name="name" required></div>
        <div class="field"><label>Area</label><input type="text" name="area" required></div>
      </div>
      <div class="form-grid">
        <div class="field"><label>Estimated cost</label><input type="text" name="cost" placeholder="₹100–₹400"></div>
        <div class="field"><label>Duration</label><input type="text" name="duration" placeholder="2–3 hrs"></div>
      </div>
      <div class="form-grid one"><div class="field"><label>Description</label><textarea name="desc" required></textarea></div></div>
      <button type="submit" class="btn-primary">Add Trail</button>
    </form>
  </div>
  <div class="card">
    <div class="card-title">All trails (<?= count($trails) ?>)</div>
    <?php if(empty($trails)): ?><p class="empty">No trails yet.</p>
    <?php else: foreach($trails as $t): ?>
      <div class="item-row">
        <div><div class="item-name"><?= htmlspecialchars($t['name']) ?></div><div class="item-meta"><?= htmlspecialchars($t['area']) ?> · <?= htmlspecialchars($t['cost']) ?> · <?= htmlspecialchars($t['duration']) ?></div></div>
        <div style="display:flex;gap:6px;">
          <button class="btn-info" onclick="openEditTrail(<?= htmlspecialchars(json_encode($t)) ?>)">Edit</button>
          <form method="POST" onsubmit="return confirm('Delete trail and all its spots?')" style="display:inline;">
            <input type="hidden" name="action" value="delete_trail"><input type="hidden" name="id" value="<?= $t['id'] ?>">
            <button type="submit" class="btn-danger">Delete</button>
          </form>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>

<?php elseif($activeTab==='spots'): ?>
  <div class="card">
    <div class="card-title">Add new food spot</div>
    <form method="POST">
      <input type="hidden" name="action" value="add_spot">
      <div class="form-grid">
        <div class="field"><label>Spot name</label><input type="text" name="name" required></div>
        <div class="field"><label>Trail</label><select name="trail_id" required><option value="">— Select —</option><?php foreach($trails as $t): ?><option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option><?php endforeach; ?></select></div>
      </div>
      <div class="form-grid three">
        <div class="field"><label>Area</label><input type="text" name="area" required></div>
        <div class="field"><label>Type</label><select name="type"><option value="veg">Veg</option><option value="nonveg">Non-Veg</option><option value="both" selected>Both</option></select></div>
        <div class="field"><label>Price range</label><input type="text" name="price"></div>
      </div>
      <div class="form-grid one"><div class="field"><label>Description</label><textarea name="desc" required></textarea></div></div>
      <div class="form-grid one"><div class="field"><label>Image URL</label><input type="text" name="image"></div></div>
      <div class="form-grid one"><div class="field"><label>Must-try dishes (comma separated)</label><input type="text" name="dishes"></div></div>
      <div class="form-grid three">
        <div class="field"><label>Opens</label><input type="text" name="open_time" placeholder="8:00 AM"></div>
        <div class="field"><label>Closes</label><input type="text" name="close_time" placeholder="10:30 PM"></div>
        <div class="field"><label>Days</label><input type="text" name="days" placeholder="Mon – Sun"></div>
      </div>
      <div class="form-grid">
        <div class="field"><label>Latitude (for Nearby Spots)</label><input type="text" name="latitude" placeholder="e.g. 19.0543"></div>
        <div class="field"><label>Longitude</label><input type="text" name="longitude" placeholder="e.g. 72.8361"></div>
      </div>
      <div class="form-grid one"><div class="field"><label>Google Maps embed URL</label><input type="text" name="map_src"></div></div>
      <button type="submit" class="btn-primary">Add Food Spot</button>
    </form>
  </div>
  <div class="card">
    <div class="card-title">All food spots (<?= count($spots) ?>)</div>
    <?php if(empty($spots)): ?><p class="empty">No spots yet.</p>
    <?php else: foreach($spots as $s): ?>
      <div class="spot-row">
        <?php if($s['image_url']): ?><img class="spot-thumb" src="<?= htmlspecialchars($s['image_url']) ?>" onerror="this.outerHTML='<div class=spot-thumb-ph>🍴</div>'"><?php else: ?><div class="spot-thumb-ph">🍴</div><?php endif; ?>
        <div style="flex:1;"><div style="font-weight:bold;font-size:14px;color:#634b49;"><?= htmlspecialchars($s['name']) ?></div><div style="font-size:12px;color:#888;"><?= htmlspecialchars($s['trail_name']) ?> · <?= htmlspecialchars($s['area']) ?> · <?= htmlspecialchars($s['price_range']) ?></div></div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
          <button class="btn-info" onclick="openEditSpot(<?= htmlspecialchars(json_encode(array_merge($s,['dishes'=>getSpotDishes($conn,$s['id'])]))) ?>,<?= htmlspecialchars(json_encode($trails)) ?>)">Edit</button>
          <form method="POST" onsubmit="return confirm('Delete?')" style="display:inline;">
            <input type="hidden" name="action" value="delete_spot"><input type="hidden" name="id" value="<?= $s['id'] ?>">
            <button type="submit" class="btn-danger">Delete</button>
          </form>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>

<?php elseif($activeTab==='users'): ?>
  <div class="card">
    <div class="card-title">All users (<?= count($users) ?>)</div>
    <?php foreach($users as $u): ?>
      <div class="user-row">
        <div class="user-avatar"><?= strtoupper(substr($u['name'],0,1)) ?></div>
        <div class="user-info">
          <div class="user-name"><?= htmlspecialchars($u['name']) ?>
            <span class="badge-pill <?= $u['role']==='admin'?'badge-admin':'badge-user' ?>" style="margin-left:6px;"><?= ucfirst($u['role']) ?></span>
            <?php if($u['is_banned']): ?><span class="badge-pill badge-banned" style="margin-left:4px;">Banned</span><?php endif; ?>
          </div>
          <div class="user-meta"><?= htmlspecialchars($u['email']) ?> · <?= htmlspecialchars($u['phone']) ?> · Joined <?= date('d M Y',strtotime($u['created_at'])) ?></div>
        </div>
        <?php if($u['id']!==$user['id']): ?>
        <div class="user-actions">
          <?php if($u['role']!=='admin'): ?>
            <form method="POST" style="display:inline;"><input type="hidden" name="action" value="promote_user"><input type="hidden" name="id" value="<?= $u['id'] ?>"><button type="submit" class="btn-success" onclick="return confirm('Promote to admin?')">Promote</button></form>
            <form method="POST" style="display:inline;"><input type="hidden" name="action" value="ban_user"><input type="hidden" name="id" value="<?= $u['id'] ?>"><input type="hidden" name="ban" value="<?= $u['is_banned']?0:1 ?>"><button type="submit" class="<?= $u['is_banned']?'btn-success':'btn-warn' ?>"><?= $u['is_banned']?'Unban':'Ban' ?></button></form>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete user?')"><input type="hidden" name="action" value="delete_user"><input type="hidden" name="id" value="<?= $u['id'] ?>"><button type="submit" class="btn-danger">Delete</button></form>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

<?php elseif($activeTab==='reviews'): ?>
  <div class="card">
    <div class="card-title">All reviews (<?= count($reviews) ?>)</div>
    <?php if(empty($reviews)): ?><p class="empty">No reviews yet.</p>
    <?php else: foreach($reviews as $r): ?>
      <div class="review-row">
        <div class="review-top">
          <span class="reviewer"><?= htmlspecialchars($r['user_name']) ?></span>
          <div style="display:flex;align-items:center;gap:10px;">
            <span class="r-stars"><?= str_repeat('★',$r['rating']).str_repeat('☆',5-$r['rating']) ?></span>
            <form method="POST" onsubmit="return confirm('Delete review?')" style="display:inline;"><input type="hidden" name="action" value="delete_review"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button type="submit" class="btn-danger">Delete</button></form>
          </div>
        </div>
        <div class="r-spot">On: <?= htmlspecialchars($r['spot_name']) ?> · <?= date('d M Y',strtotime($r['created_at'])) ?></div>
        <div class="r-text"><?= htmlspecialchars($r['review_text']) ?></div>
      </div>
    <?php endforeach; endif; ?>
  </div>

<?php elseif($activeTab==='seasonal'): ?>
  <div class="card">
    <div class="card-title">Add seasonal special</div>
    <form method="POST">
      <input type="hidden" name="action" value="add_seasonal">
      <div class="form-grid">
        <div class="field"><label>Food spot</label><select name="spot_id" required><option value="">— Select spot —</option><?php foreach($spots as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Dish name</label><input type="text" name="dish_name" placeholder="e.g. Ukdiche Modak" required></div>
      </div>
      <div class="form-grid three">
        <div class="field"><label>Season / festival</label><input type="text" name="season_name" placeholder="e.g. Ganesh Chaturthi" required></div>
        <div class="field"><label>Start month</label><select name="start_month" required><?php foreach($months as $i=>$m): ?><option value="<?= $i+1 ?>"><?= $m ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>End month</label><select name="end_month" required><?php foreach($months as $i=>$m): ?><option value="<?= $i+1 ?>"><?= $m ?></option><?php endforeach; ?></select></div>
      </div>
      <button type="submit" class="btn-primary">Add Seasonal Special</button>
    </form>
  </div>
  <div class="card">
    <div class="card-title">All seasonal specials (<?= count($seasonals) ?>)</div>
    <?php if(empty($seasonals)): ?><p class="empty">No seasonal specials yet.</p>
    <?php else: foreach($seasonals as $ss): ?>
      <div class="seasonal-row">
        <div><div style="font-weight:bold;font-size:14px;color:#634b49;">🌸 <?= htmlspecialchars($ss['dish_name']) ?></div><div style="font-size:12px;color:#888;"><?= htmlspecialchars($ss['spot_name']) ?> · <?= htmlspecialchars($ss['season_name']) ?> · <?= $months[$ss['start_month']-1] ?> – <?= $months[$ss['end_month']-1] ?></div></div>
        <form method="POST" onsubmit="return confirm('Remove?')"><input type="hidden" name="action" value="delete_seasonal"><input type="hidden" name="id" value="<?= $ss['id'] ?>"><button type="submit" class="btn-danger">Remove</button></form>
      </div>
    <?php endforeach; endif; ?>
  </div>
<?php endif; ?>
</div>

<!-- EDIT TRAIL MODAL -->
<div class="modal-overlay" id="editTrailModal">
  <div class="modal">
    <h3>Edit Trail</h3>
    <form method="POST">
      <input type="hidden" name="action" value="edit_trail">
      <input type="hidden" name="id" id="etId">
      <div class="form-grid"><div class="field"><label>Name</label><input type="text" name="name" id="etName" required></div><div class="field"><label>Area</label><input type="text" name="area" id="etArea" required></div></div>
      <div class="form-grid"><div class="field"><label>Cost</label><input type="text" name="cost" id="etCost"></div><div class="field"><label>Duration</label><input type="text" name="duration" id="etDuration"></div></div>
      <div class="form-grid one"><div class="field"><label>Description</label><textarea name="desc" id="etDesc" required></textarea></div></div>
      <div class="modal-footer"><button type="button" class="btn-cancel" onclick="closeModal('editTrailModal')">Cancel</button><button type="submit" class="btn-primary">Save</button></div>
    </form>
  </div>
</div>

<!-- EDIT SPOT MODAL -->
<div class="modal-overlay" id="editSpotModal">
  <div class="modal">
    <h3>Edit Food Spot</h3>
    <form method="POST">
      <input type="hidden" name="action" value="edit_spot">
      <input type="hidden" name="id" id="esId">
      <div class="form-grid"><div class="field"><label>Name</label><input type="text" name="name" id="esName" required></div><div class="field"><label>Trail</label><select name="trail_id" id="esTrail" required></select></div></div>
      <div class="form-grid three"><div class="field"><label>Area</label><input type="text" name="area" id="esArea" required></div><div class="field"><label>Type</label><select name="type" id="esType"><option value="veg">Veg</option><option value="nonveg">Non-Veg</option><option value="both">Both</option></select></div><div class="field"><label>Price</label><input type="text" name="price" id="esPrice"></div></div>
      <div class="form-grid one"><div class="field"><label>Description</label><textarea name="desc" id="esDesc" required></textarea></div></div>
      <div class="form-grid one"><div class="field"><label>Image URL</label><input type="text" name="image" id="esImage"></div></div>
      <div class="form-grid one"><div class="field"><label>Dishes (comma separated)</label><input type="text" name="dishes" id="esDishes"></div></div>
      <div class="form-grid three"><div class="field"><label>Opens</label><input type="text" name="open_time" id="esOpen"></div><div class="field"><label>Closes</label><input type="text" name="close_time" id="esClose"></div><div class="field"><label>Days</label><input type="text" name="days" id="esDays"></div></div>
      <div class="form-grid"><div class="field"><label>Latitude</label><input type="text" name="latitude" id="esLat"></div><div class="field"><label>Longitude</label><input type="text" name="longitude" id="esLng"></div></div>
      <div class="form-grid one"><div class="field"><label>Map URL</label><input type="text" name="map_src" id="esMap"></div></div>
      <div class="modal-footer"><button type="button" class="btn-cancel" onclick="closeModal('editSpotModal')">Cancel</button><button type="submit" class="btn-primary">Save</button></div>
    </form>
  </div>
</div>

<footer><p>© 2026 Bite by Bite - Mumbai | Created by Shreya Sakala, Gayatri Bedekar & Palak Sanklecha</p></footer>

<script>
function openEditTrail(t){
    document.getElementById('etId').value=t.id; document.getElementById('etName').value=t.name;
    document.getElementById('etArea').value=t.area; document.getElementById('etCost').value=t.cost||'';
    document.getElementById('etDuration').value=t.duration||''; document.getElementById('etDesc').value=t.description||'';
    document.getElementById('editTrailModal').classList.add('open');
}
function openEditSpot(s,trails){
    document.getElementById('esId').value=s.id; document.getElementById('esName').value=s.name;
    document.getElementById('esArea').value=s.area; document.getElementById('esType').value=s.type;
    document.getElementById('esPrice').value=s.price_range||''; document.getElementById('esDesc').value=s.description||'';
    document.getElementById('esImage').value=s.image_url||''; document.getElementById('esDishes').value=s.dishes||'';
    document.getElementById('esOpen').value=s.open_time||''; document.getElementById('esClose').value=s.close_time||'';
    document.getElementById('esDays').value=s.days_open||''; document.getElementById('esLat').value=s.latitude||'';
    document.getElementById('esLng').value=s.longitude||''; document.getElementById('esMap').value=s.map_src||'';
    const sel=document.getElementById('esTrail');
    sel.innerHTML=trails.map(t=>`<option value="${t.id}"${t.id==s.trail_id?' selected':''}>${t.name}</option>`).join('');
    document.getElementById('editSpotModal').classList.add('open');
}
function closeModal(id){ document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',e=>{if(e.target===m)m.classList.remove('open');}));
</script>
</body>
</html>
