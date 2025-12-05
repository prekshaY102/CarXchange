<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Settings - <?= htmlspecialchars($user['name']) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

</head>
<body>
  <?php include './includes/header.php'; ?> <!-- navbar -->

  <div class="container my-5">
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-6">
        <div class="card shadow-lg border-0 rounded-4">
          <div class="card-body p-5">

            <h3 class="fw-bold mb-4 text-warning accent-border">App Preferences</h3>

            <!-- Dark Mode Toggle -->
            <div class="mb-4">
              <h5 class="fw-bold">Theme</h5>
              <button id="toggleTheme" class="btn btn-outline-warning">
                <i class="bi bi-moon-stars-fill me-2"></i> Toggle Dark Mode
              </button>
            </div>

            <hr>

            <!-- Font Size -->
            <div class="mb-4">
              <h5 class="fw-bold">Font Size</h5>
              <div class="btn-group">
                <button id="increaseFont" class="btn btn-outline-warning">A+</button>
                <button id="decreaseFont" class="btn btn-outline-warning">A-</button>
              </div>
            </div>

            <hr>

            <!-- Theme Color -->
            <div class="mb-4">
              <h5 class="fw-bold">Accent Color</h5>
              <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-warning theme-btn" data-color="warning">Yellow</button>
                <button class="btn btn-primary theme-btn" data-color="primary">Blue</button>
                <button class="btn btn-success theme-btn" data-color="success">Green</button>
                <button class="btn btn-danger theme-btn" data-color="danger">Red</button>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>

  <?php include './includes/footer.php'; ?> <!-- footer -->

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
  // ===== APPLY SAVED SETTINGS ON LOAD =====
  window.addEventListener("DOMContentLoaded", function () {
    if (localStorage.getItem("darkMode") === "true") {
      document.body.classList.add("dark-mode");
    }
    if (localStorage.getItem("largeText") === "true") {
      document.body.classList.add("large-text");
    }
    let themeColor = localStorage.getItem("themeColor");
    if (themeColor) applyThemeColor(themeColor);
  });

  // ===== DARK MODE TOGGLE =====
  document.getElementById("toggleTheme")?.addEventListener("click", function () {
    document.body.classList.toggle("dark-mode");
    localStorage.setItem("darkMode", document.body.classList.contains("dark-mode"));
  });

  // ===== FONT SIZE CONTROLS =====
  document.getElementById("increaseFont")?.addEventListener("click", function () {
    document.body.classList.add("large-text");
    localStorage.setItem("largeText", "true");
  });
  document.getElementById("decreaseFont")?.addEventListener("click", function () {
    document.body.classList.remove("large-text");
    localStorage.setItem("largeText", "false");
  });

  // ===== THEME COLOR BUTTONS =====
  document.querySelectorAll(".theme-btn").forEach(btn => {
    btn.addEventListener("click", function () {
      let color = this.dataset.color;
      localStorage.setItem("themeColor", color);
      applyThemeColor(color);
    });
  });

  // ===== APPLY THEME COLOR =====
  function applyThemeColor(color) {
    document.querySelectorAll(".btn").forEach(b => {
      b.className = b.className.replace(/btn-(warning|primary|success|danger)/g, "btn-" + color);
    });
  }
  </script>
</body>
</html>
