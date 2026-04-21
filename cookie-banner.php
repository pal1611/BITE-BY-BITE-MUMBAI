<?php if (!isset($_COOKIE['cookie_consent'])) { ?>
<div id="cookieBanner" style="
    position:fixed;bottom:0;left:0;right:0;z-index:9999;
    background:#43302e;color:white;
    padding:16px 24px;
    display:flex;align-items:center;justify-content:space-between;
    gap:16px;flex-wrap:wrap;
    box-shadow:0 -4px 20px rgba(0,0,0,0.2);
    font-family:Arial,Helvetica,sans-serif;font-size:14px;">
  <span style="flex:1;min-width:220px;">
    🍪 We use cookies to improve your experience on Bite by Bite.
  </span>
  <div style="display:flex;gap:10px;flex-wrap:wrap;">
    <a href="cookie-preferences.php?action=accept_all"
       style="background:#fff1b5;color:#634b49;padding:9px 20px;border-radius:8px;font-weight:bold;text-decoration:none;font-size:13px;">
      Accept All
    </a>
    <a href="cookie-preferences.php"
       style="background:rgba(255,255,255,0.15);color:white;padding:9px 20px;border-radius:8px;font-weight:bold;text-decoration:none;font-size:13px;border:1px solid rgba(255,255,255,0.3);">
      Manage Preferences
    </a>
    <a href="cookie-preferences.php?action=reject_all"
       style="background:transparent;color:rgba(255,255,255,0.6);padding:9px 10px;font-size:13px;text-decoration:underline;cursor:pointer;">
      Essential Only
    </a>
  </div>
</div>
<?php } ?>
