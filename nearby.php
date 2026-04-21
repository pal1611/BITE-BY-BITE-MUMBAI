<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];

// Fetch all spots that have coordinates
$spots = $conn->query("
    SELECT fs.*, t.name AS trail_name, t.id AS trail_id
    FROM food_spots fs
    JOIN trails t ON fs.trail_id = t.id
    WHERE fs.latitude IS NOT NULL AND fs.longitude IS NOT NULL
")->fetch_all(MYSQLI_ASSOC);

$typeLabel = ['veg' => '🟢 Veg', 'nonveg' => '🔴 Non-Veg', 'both' => '🟡 Veg & Non-Veg'];
$typeClass = ['veg' => 'badge-veg', 'nonveg' => 'badge-nonveg', 'both' => 'badge-both'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Nearby Spots - Bite by Bite</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#fff8f0;font-family:Arial,Helvetica,sans-serif;color:#333;}
<?php include 'header_styles.php'; ?>

.nearby-hero{background:linear-gradient(135deg,#634b49,#8b6b68);padding:40px 20px;text-align:center;color:white;}
.nearby-hero h2{font-size:26px;margin-bottom:8px;}
.nearby-hero p{font-size:14px;opacity:0.85;margin-bottom:22px;}
.locate-btn{background:#fff1b5;color:#634b49;padding:12px 28px;border:none;border-radius:25px;font-weight:bold;font-size:15px;cursor:pointer;transition:background 0.2s;}
.locate-btn:hover{background:#c1dbe8;}
.locate-btn:disabled{opacity:0.6;cursor:not-allowed;}

.container{max-width:900px;margin:30px auto;padding:0 20px;}
.status-msg{text-align:center;padding:20px;color:#888;font-size:14px;font-style:italic;}

.radius-bar{display:flex;align-items:center;gap:12px;margin-bottom:20px;background:white;padding:14px 18px;border-radius:10px;box-shadow:0 4px 14px rgba(0,0,0,0.07);}
.radius-bar label{font-size:13px;font-weight:bold;color:#634b49;white-space:nowrap;}
.radius-bar input{flex:1;accent-color:#634b49;}
.radius-bar span{font-size:14px;font-weight:bold;color:#634b49;min-width:50px;}

.results-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;}
.results-header h3{font-size:16px;color:#634b49;font-weight:bold;}
.result-count{font-size:13px;color:#888;}

.spot-card{background:white;border-radius:12px;box-shadow:0 4px 14px rgba(0,0,0,0.07);margin-bottom:14px;display:flex;overflow:hidden;transition:transform 0.2s;}
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
.distance-badge{background:#e3f2fd;color:#1565c0;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:bold;}
.btn-sm{background:#fff1b5;color:#634b49;padding:6px 14px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:12px;}
.btn-sm:hover{background:#c1dbe8;}

.empty-state{text-align:center;padding:50px 20px;color:#aaa;}
.empty-state .icon{font-size:44px;margin-bottom:14px;}
.empty-state p{font-size:15px;}

footer{background:#43302e;color:white;text-align:center;padding:20px;margin-top:40px;}
footer p{margin:0;color:white;}
@media(max-width:600px){header{padding:15px 20px;}.spot-card{flex-direction:column;}.spot-img,.spot-img-ph{width:100%;height:130px;}}
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="nearby-hero">
  <h2>📍 Spots Near You</h2>
  <p>Allow location access and we'll show you the closest food spots</p>
  <button class="locate-btn" id="locateBtn" onclick="getLocation()">📍 Find Nearby Spots</button>
</div>

<div class="container">
  <div id="statusMsg" class="status-msg">Tap the button above to find food spots near you.</div>

  <div id="radiusWrap" style="display:none;">
    <div class="radius-bar">
      <label>Search radius</label>
      <input type="range" id="radiusSlider" min="1" max="20" value="5" oninput="updateRadius(this.value)">
      <span id="radiusDisplay">5 km</span>
    </div>
  </div>

  <div id="resultsHeader" style="display:none;" class="results-header">
    <h3>Nearby food spots</h3>
    <span class="result-count" id="resultCount"></span>
  </div>

  <div id="spotsList"></div>
</div>

<footer><p>© 2026 Bite by Bite - Mumbai | Created by Shreya Sakala, Gayatri Bedekar & Palak Sanklecha</p></footer>

<script>
// All spots with coordinates from PHP
const allSpots = <?= json_encode($spots) ?>;
const typeLabel = { veg:'🟢 Veg', nonveg:'🔴 Non-Veg', both:'🟡 Veg & Non-Veg' };
const typeClass = { veg:'badge-veg', nonveg:'badge-nonveg', both:'badge-both' };

let userLat = null, userLng = null;
let radiusKm = 5;

function getLocation(){
    const btn = document.getElementById('locateBtn');
    btn.disabled = true;
    btn.innerText = '⏳ Getting location...';
    document.getElementById('statusMsg').innerText = 'Requesting location access...';

    if(!navigator.geolocation){
        document.getElementById('statusMsg').innerText = 'Geolocation is not supported by your browser.';
        btn.disabled = false; btn.innerText = '📍 Find Nearby Spots';
        return;
    }

    navigator.geolocation.getCurrentPosition(
        pos => {
            userLat = pos.coords.latitude;
            userLng = pos.coords.longitude;
            btn.innerText = '🔄 Refresh';
            btn.disabled = false;
            document.getElementById('radiusWrap').style.display = 'block';
            document.getElementById('resultsHeader').style.display = 'flex';
            renderNearby();
        },
        err => {
            document.getElementById('statusMsg').innerText = 'Could not get your location. Please allow location access and try again.';
            btn.disabled = false; btn.innerText = '📍 Try Again';
        }
    );
}

function haversineKm(lat1, lng1, lat2, lng2){
    const R = 6371;
    const dLat = (lat2-lat1)*Math.PI/180;
    const dLng = (lng2-lng1)*Math.PI/180;
    const a = Math.sin(dLat/2)*Math.sin(dLat/2) +
              Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*
              Math.sin(dLng/2)*Math.sin(dLng/2);
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}

function updateRadius(val){
    radiusKm = parseInt(val);
    document.getElementById('radiusDisplay').innerText = val + ' km';
    if(userLat) renderNearby();
}

function renderNearby(){
    const statusEl  = document.getElementById('statusMsg');
    const listEl    = document.getElementById('spotsList');
    const countEl   = document.getElementById('resultCount');

    // Calculate distances
    const withDist = allSpots.map(s => ({
        ...s,
        distKm: haversineKm(userLat, userLng, parseFloat(s.latitude), parseFloat(s.longitude))
    })).filter(s => s.distKm <= radiusKm).sort((a,b) => a.distKm - b.distKm);

    countEl.innerText = withDist.length + ' spot' + (withDist.length!==1?'s':'') + ' found';

    if(withDist.length === 0){
        statusEl.style.display = 'block';
        statusEl.innerText = 'No food spots found within ' + radiusKm + ' km. Try increasing the radius.';
        listEl.innerHTML = '';
        return;
    }

    statusEl.style.display = 'none';
    listEl.innerHTML = withDist.map(s => `
        <div class="spot-card">
            ${s.image_url ? `<img class="spot-img" src="${s.image_url}" onerror="this.outerHTML='<div class=spot-img-ph>🍴</div>'">` : '<div class="spot-img-ph">🍴</div>'}
            <div class="spot-body">
                <h3>${s.name}</h3>
                <div class="spot-meta">📍 ${s.area} · on ${s.trail_name}</div>
                <div class="tags">
                    <span class="badge ${typeClass[s.type]||'badge-both'}">${typeLabel[s.type]||s.type}</span>
                    <span class="badge badge-cost">💰 ${s.price_range||'–'}</span>
                    <span class="distance-badge">📍 ${s.distKm < 1 ? Math.round(s.distKm*1000)+'m' : s.distKm.toFixed(1)+'km'} away</span>
                </div>
                <a href="spot-details.php?id=${s.id}" class="btn-sm">View Spot →</a>
            </div>
        </div>
    `).join('');
}
</script>
</body>
</html>
