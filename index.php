<?php
// ============================================================
// index.php
// Entry point — shows login page or redirects to dashboard
// ============================================================
require_once 'includes/config.php';

// Already logged in → go straight to the app
if (!empty($_SESSION['user_id'])) {
    header('Location: app.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>InvenTrack — Login</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">

  <div class="auth-grid">
    <!-- Brand panel -->
    <div class="auth-brand">
      <div class="logo">Inven<span>Track</span></div>
      <p>Inventory Sales &amp; Tracker System</p>
      <ul class="auth-features">
        <li>Real-time inventory tracking</li>
        <li>Instant sales recording</li>
        <li>Low-stock alerts</li>
        <li>Revenue analytics &amp; charts</li>
        <li>Export reports as CSV</li>
        <li>Full audit trail</li>
      </ul>
    </div>

    <!-- Auth form panel -->
    <div class="auth-form-panel">
      <div class="auth-tabs">
        <button class="auth-tab active" data-tab="login"    onclick="switchAuthTab('login')">Login</button>
        <button class="auth-tab"        data-tab="register" onclick="switchAuthTab('register')">Register</button>
      </div>

      <!-- LOGIN FORM -->
      <div id="login-form-div">
        <div class="form-group">
          <label for="login-email">Email Address</label>
          <input type="email" id="login-email" class="form-control" placeholder="admin@inventory.com" autocomplete="email">
        </div>
        <div class="form-group">
          <label for="login-password">Password</label>
          <input type="password" id="login-password" class="form-control" placeholder="••••••••"
                 autocomplete="current-password" onkeydown="if(event.key==='Enter') login()">
        </div>
        <div id="login-error" class="text-danger text-sm mt-1"></div>
        <button id="login-btn" class="btn btn-primary btn-full mt-4" onclick="login()">Login</button>
        <p class="text-muted text-sm mt-2" style="text-align:center">
          Demo: <strong>admin@inventory.com</strong> / <strong>password</strong>
        </p>
      </div>

      <!-- REGISTER FORM -->
      <div id="register-form-div" class="hidden">
        <div class="form-group">
          <label for="reg-name">Full Name</label>
          <input type="text" id="reg-name" class="form-control" placeholder="Your name">
        </div>
        <div class="form-group">
          <label for="reg-email">Email Address</label>
          <input type="email" id="reg-email" class="form-control" placeholder="you@example.com">
        </div>
        <div class="form-group">
          <label for="reg-password">Password</label>
          <input type="password" id="reg-password" class="form-control" placeholder="Min 6 characters">
        </div>
        <div class="form-group">
          <label for="reg-confirm">Confirm Password</label>
          <input type="password" id="reg-confirm" class="form-control" placeholder="Repeat password">
        </div>
        <div id="reg-message" class="text-sm mt-1"></div>
        <button id="register-btn" class="btn btn-primary btn-full mt-4" onclick="register()">Create Account</button>
      </div>
    </div>
  </div>

  <div id="toast-container"></div>
  <script>window.APP_BASE_URL = <?= json_encode(APP_BASE_URL, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
  <script src="assets/js/app.js"></script>
</body>
</html>
