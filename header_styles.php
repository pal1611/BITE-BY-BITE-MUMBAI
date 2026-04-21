<?php // header_styles.php — include inside <style> on every page ?>
/* ── SHARED HEADER & DROPDOWN STYLES ── */
header{background:#fff1b5;padding:15px 40px;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:100;box-shadow:0 2px 8px rgba(0,0,0,0.08);}
header h1{color:#634b49;font-size:24px;}
.user-menu{position:relative;}
.user-menu-trigger{font-weight:bold;color:#634b49;font-size:14px;cursor:pointer;display:flex;align-items:center;gap:5px;background:none;border:none;padding:0;font-family:inherit;}
.user-menu-trigger:hover{color:#43302e;}
.user-menu-trigger .caret{font-size:11px;}
.dropdown{display:none;position:absolute;right:0;top:calc(100% + 10px);background:white;border-radius:12px;box-shadow:0 8px 28px rgba(0,0,0,0.13);min-width:220px;overflow:hidden;z-index:200;border:0.5px solid #e8ddd5;}
.dropdown.open{display:block;}
.dropdown-header{background:#fff1b5;padding:14px 18px;font-size:13px;color:#888;border-bottom:1px solid #e8ddd5;}
.dropdown-header strong{display:block;font-size:15px;color:#634b49;font-weight:bold;margin-top:2px;}
.dropdown a,.dropdown button{display:flex;align-items:center;gap:10px;padding:11px 18px;text-decoration:none;color:#333;font-size:14px;background:none;border:none;width:100%;text-align:left;cursor:pointer;transition:background 0.15s;font-family:inherit;}
.dropdown a:hover,.dropdown button:hover{background:#c1dbe8;}
.dropdown .divider{height:1px;background:#f0e8e0;margin:4px 0;}
.dropdown .logout-item{color:#b71c1c;}
.dropdown .logout-item:hover{background:#fdecea;}
