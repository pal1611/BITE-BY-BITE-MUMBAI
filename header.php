<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = $_SESSION['user'] ?? null;
$displayName = $user['username'] ?? $user['name'] ?? 'Guest';
?>

<header>
  <h1>Bite by Bite - Mumbai</h1>
  <div class="user-menu" id="userMenu">
    <button class="user-menu-trigger" onclick="bbToggleDropdown()">
      Hello, <?= htmlspecialchars($displayName) ?> <span class="caret">▾</span>
    </button>
    <div class="dropdown" id="bbDropdown">
      <div class="dropdown-header">
        Signed in as
        <strong><?= htmlspecialchars($displayName) ?></strong>
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
      <?php if (!empty($user['role']) && $user['role'] === 'admin'): ?>
        <a href="admin.php">⚙️ Admin Panel</a>
      <?php endif; ?>
      <div class="divider"></div>
      <a class="logout-item" href="logout.php">🚪 Logout</a>
    </div>
  </div>
</header>

<?php include_once 'cookie-banner.php'; ?>

<script>
function bbToggleDropdown(){
    document.getElementById("bbDropdown").classList.toggle("open");
}
document.addEventListener("click", function(e){
    var m = document.getElementById("userMenu");
    if(m && !m.contains(e.target))
        document.getElementById("bbDropdown").classList.remove("open");
});
</script>