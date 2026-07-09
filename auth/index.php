<?php
// Ensure the user is logged out when they land here
session_start();
session_unset();
session_destroy();

// Database Configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "keretasewa_db";

$conn = new mysqli($servername, $username, $password, $dbname);

// Verify Database Connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch ALL cars from the database
$sql = "SELECT * FROM car";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Abe Nuar Kereta Sewa</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
  
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
    }

    html { scroll-behavior: smooth; }

    body {
      background-color: #121212;
      color: #ffffff;
      min-height: 100vh;
      overflow-x: hidden;
    }

    section {
      padding: 100px 40px;
      max-width: 1400px;
      margin: 0 auto;
    }

    .section-title {
      font-size: 2.5rem;
      font-weight: 800;
      text-align: center;
      margin-bottom: 15px;
      letter-spacing: 1px;
    }

    .section-subtitle {
      text-align: center;
      color: #7b91f9;
      font-size: 1rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 2px;
      margin-bottom: 60px;
    }

    .title-underline {
      width: 60px;
      height: 4px;
      background-color: #7b91f9;
      margin: 10px auto 0 auto;
      border-radius: 2px;
    }

    /* ================= FIXED NAVIGATION HEADER ================= */
    .header-nav {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      background-color: rgba(18, 18, 18, 0.95);
      backdrop-filter: blur(10px);
      z-index: 1000;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .nav-container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 20px 40px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .nav-brand {
      color: #ffffff;
      font-size: 1.1rem;
      font-weight: 800;
      letter-spacing: 1px;
      text-transform: uppercase;
    }

    .nav-brand span { color: #7b91f9; }

    .navbar {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .navbar a {
      color: #cccccc;
      text-decoration: none;
      font-size: 0.95rem;
      font-weight: 500;
      padding: 8px 18px;
      border-radius: 30px;
      background-color: transparent;
      transition: color 0.3s ease, background-color 0.3s ease;
    }

    .navbar a:hover {
      color: #ffffff;
      background-color: rgba(123, 145, 249, 0.15);
    }

    /* ================= HOME / HERO SECTION ================= */
    .landing-container {
      display: grid;
      grid-template-columns: 1fr 1fr;
      min-height: 100vh;
      padding-top: 100px;
      align-items: center;
    }

    .hero-left {
      position: relative;
      padding: 40px 60px 40px 100px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .sidebar-socials {
      position: absolute;
      left: 35px;
      top: 0;
      bottom: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 24px;
    }

    .sidebar-socials::before,
    .sidebar-socials::after {
      content: '';
      width: 1px;
      background: rgba(255, 255, 255, 0.15);
      flex-grow: 1;
    }

    .sidebar-socials::before { margin-bottom: 15px; }
    .sidebar-socials::after  { margin-top: 15px; }

    .social-icon {
      width: 20px;
      height: 20px;
      fill: #7b91f9;
      opacity: 0.7;
      transition: opacity 0.3s ease, transform 0.3s ease;
    }

    .social-icon:hover {
      opacity: 1;
      transform: scale(1.1);
    }

    .hero-heading {
      font-size: 3.5rem;
      font-weight: 800;
      line-height: 1.2;
      margin-bottom: 20px;
      max-width: 500px;
    }

    .hero-subtext {
      color: #aaaaaa;
      font-size: 1rem;
      margin-bottom: 40px;
      max-width: 400px;
      line-height: 1.6;
    }

    .hero-action-buttons {
      display: flex;
      gap: 15px;
      flex-wrap: wrap;
    }

    .btn-book {
      display: inline-block;
      background-color: #7b91f9;
      color: #ffffff;
      padding: 14px 36px;
      border-radius: 50px;
      font-size: 1rem;
      font-weight: 700;
      text-decoration: none;
      width: fit-content;
      transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
      box-shadow: 0 4px 15px rgba(123, 145, 249, 0.3);
      cursor: pointer;
      border: none;
      text-align: center;
    }

    .btn-book:hover:not(:disabled) {
      background-color: #6c7fd8;
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(123, 145, 249, 0.5);
    }

    .btn-return {
      display: inline-block;
      background-color: transparent;
      color: #ffffff;
      padding: 12px 34px;
      border-radius: 50px;
      font-size: 1rem;
      font-weight: 700;
      text-decoration: none;
      width: fit-content;
      border: 2px solid #7b91f9;
      transition: background-color 0.3s ease, transform 0.2s ease;
      cursor: pointer;
    }

    .btn-return:hover {
      background-color: rgba(123, 145, 249, 0.15);
      transform: translateY(-3px);
    }

    .hero-right {
      display: flex;
      flex-direction: column;
      padding: 40px 60px;
      align-items: center;
      justify-content: center;
    }

    .logo-card-container {
      width: 100%;
      display: flex;
      justify-content: center;
    }

    .logo-card {
      width: 100%;
      max-width: 550px;
      aspect-ratio: 16 / 10;
      background: linear-gradient(135deg, #0a3d91 0%, #112d61 100%);
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 15px 40px rgba(10, 61, 145, 0.4),
                  inset 0 1px 1px rgba(255, 255, 255, 0.2);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .logo-card img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    /* ================= CARS SECTION ================= */
    .cars-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
      gap: 30px;
      margin-bottom: 40px;
    }

    .car-card {
      background-color: #1a1a1a;
      border: 1px solid rgba(255, 255, 255, 0.05);
      border-radius: 16px;
      overflow: hidden;
      transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
    }

    .car-card:hover {
      transform: translateY(-5px);
      border-color: rgba(123, 145, 249, 0.3);
      box-shadow: 0 10px 25px rgba(123, 145, 249, 0.1);
    }

    .car-img-box {
      width: 100%;
      aspect-ratio: 16 / 10;
      background-color: #242424;
      overflow: hidden;
    }

    .car-img-box img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .car-info { padding: 25px; }

    .car-name {
      font-size: 1.3rem;
      font-weight: 700;
      margin-bottom: 15px;
      color: #ffffff;
    }

    .car-specs {
      display: flex;
      flex-direction: column;
      gap: 8px;
      margin-bottom: 25px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      padding-bottom: 15px;
    }

    .spec-item {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 0.9rem;
      color: #aaaaaa;
    }

    .car-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .price-box .price-label {
      font-size: 0.75rem;
      color: #666666;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .price-box .price-value {
      font-size: 1.4rem;
      font-weight: 800;
      color: #7b91f9;
    }

    /* ================= ABOUT SECTION ================= */
    .about-wrapper {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 60px;
      align-items: center;
    }

    .about-text p {
      color: #aaaaaa;
      font-size: 1.05rem;
      line-height: 1.7;
      margin-bottom: 25px;
    }

    .about-features {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }

    .feat-box {
      background-color: #1a1a1a;
      padding: 25px;
      border-radius: 12px;
      border-left: 4px solid #7b91f9;
    }

    .feat-box h4 {
      font-size: 1.1rem;
      font-weight: 700;
      margin-bottom: 8px;
    }

    .feat-box p {
      font-size: 0.9rem;
      color: #888888;
      line-height: 1.5;
    }

    /* ================= CONTACT SECTION ================= */
    .contact-wrapper {
      max-width: 1100px;
      margin: 0 auto;
    }

    .contact-info-cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 25px;
    }

    .info-card {
      background-color: #1a1a1a;
      padding: 30px 25px;
      border-radius: 12px;
      display: flex;
      gap: 20px;
      align-items: center;
      border: 1px solid rgba(255, 255, 255, 0.02);
      min-height: 120px;
    }

    .info-icon-circle {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      background-color: rgba(123, 145, 249, 0.1);
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .info-icon-circle svg {
      width: 22px;
      height: 22px;
      fill: #7b91f9;
    }

    .info-details {
      flex-grow: 1;
      min-width: 0;
    }

    .info-details h4 {
      font-size: 0.9rem;
      font-weight: 600;
      color: #888888;
      text-transform: uppercase;
      margin-bottom: 6px;
      letter-spacing: 0.5px;
    }

    .info-details p {
      font-size: 1.1rem;
      font-weight: 700;
      color: #ffffff;
      line-height: 1.4;
      word-wrap: break-word;
      overflow-wrap: break-word;
    }

    .footer-credits {
      text-align: center;
      padding: 40px 20px;
      color: #555555;
      font-size: 0.9rem;
      border-top: 1px solid rgba(255, 255, 255, 0.05);
      margin-top: 60px;
    }

    /* ================= MODAL OVERLAY ================= */
    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.6);
      backdrop-filter: blur(5px);
      z-index: 2000;
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.3s ease;
    }

    .modal-overlay.active {
      opacity: 1;
      pointer-events: auto;
    }

    .modal-box {
      width: 90%;
      max-width: 500px;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 20px 40px rgba(0,0,0,0.5);
      transform: translateY(-20px);
      transition: transform 0.3s ease;
    }

    .modal-overlay.active .modal-box {
      transform: translateY(0);
    }

    /* --- Admin Modal --- */
    .admin-modal { background-color: #ffffff; }

    .admin-header {
      background-color: #192231;
      padding: 20px;
      text-align: center;
    }

    .admin-header h3 {
      color: #ffffff;
      font-size: 1.25rem;
      font-weight: 700;
      letter-spacing: 1px;
    }

    .admin-body { padding: 30px; }

    .admin-label {
      color: #2b3951;
      font-size: 1.1rem;
      font-weight: 600;
      margin-bottom: 12px;
      display: block;
    }

    .admin-input {
      width: 100%;
      padding: 14px 18px;
      border-radius: 8px;
      border: 1px solid #ced4da;
      font-size: 1.05rem;
      color: #333333;
      outline: none;
      margin-bottom: 8px;
      font-family: 'Poppins', sans-serif;
    }

    .admin-input::placeholder { color: #a3b1c6; }

    .admin-error {
      color: #dc3545;
      font-size: 0.85rem;
      margin-bottom: 15px;
      display: none;
      font-weight: 500;
    }

    .admin-btn {
      width: 100%;
      background-color: #e3a900;
      color: #192231;
      border: none;
      padding: 14px;
      font-size: 1.1rem;
      font-weight: 700;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.2s;
      font-family: 'Poppins', sans-serif;
    }

    .admin-btn:hover { background-color: #c99600; }

    /* --- View Bookings Modal --- */
    .view-bookings-modal {
      background-color: #ffffff;
      padding: 35px 30px;
      text-align: left;
    }

    .view-bookings-title {
      color: #111827;
      font-size: 1.5rem;
      font-weight: 800;
      margin-bottom: 10px;
    }

    .view-bookings-text {
      color: #4b5563;
      font-size: 0.95rem;
      margin-bottom: 18px;
      line-height: 1.5;
    }

    .view-bookings-input {
      width: 100%;
      padding: 13px 15px;
      border-radius: 8px;
      border: 1px solid #cbd5e1;
      font-size: 1rem;
      color: #111827;
      outline: none;
      margin-bottom: 8px;
      font-family: 'Poppins', sans-serif;
    }

    .view-bookings-input:focus {
      border-color: #7b91f9;
      box-shadow: 0 0 0 2px rgba(123, 145, 249, 0.2);
    }

    .view-bookings-error {
      color: #dc3545;
      font-size: 0.85rem;
      margin-bottom: 14px;
      display: none;
      font-weight: 500;
    }

    .view-bookings-btn {
      width: 100%;
      background-color: #7b91f9;
      color: #ffffff;
      border: none;
      padding: 13px 16px;
      font-size: 1rem;
      font-weight: 700;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.2s ease;
      font-family: 'Poppins', sans-serif;
    }

    .view-bookings-btn:hover { background-color: #6c7fd8; }

    /* --- Return Car Modal --- */
    .return-modal {
      background-color: #dcee61;
      padding: 35px 30px;
      text-align: center;
    }

    .return-title {
      color: #111b27;
      font-size: 1.9rem;
      font-weight: 700;
      margin-bottom: 30px;
      letter-spacing: -0.5px;
    }

    .return-actions {
      display: flex;
      justify-content: center;
      gap: 30px;
    }

    .btn-ret-confirm {
      min-width: 130px;
      background-color: #000000;
      color: #ffffff;
      border: none;
      padding: 12px 30px;
      font-size: 1rem;
      font-weight: 600;
      border-radius: 4px;
      cursor: pointer;
      text-transform: uppercase;
      font-family: 'Poppins', sans-serif;
      transition: background-color 0.2s ease, transform 0.15s ease;
    }

    .btn-ret-confirm:hover {
      background-color: #222222;
      transform: translateY(-2px);
    }

    /* ================= RESPONSIVE ================= */
    @media (max-width: 992px) {
      .landing-container, .about-wrapper {
        grid-template-columns: 1fr;
        gap: 40px;
      }
      .hero-left { padding: 40px 20px 40px 60px; }
      .sidebar-socials { left: 20px; }
      .nav-container {
        flex-direction: column;
        gap: 15px;
        padding: 15px 20px;
      }
      .landing-container { padding-top: 140px; }
    }

    @media (max-width: 650px) {
      .about-features { grid-template-columns: 1fr; }
      .hero-heading { font-size: 2.5rem; }
      .contact-info-cards { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

  <header class="header-nav">
    <div class="nav-container">
      <div class="nav-brand">ABE NUAR <span>KERETA SEWA</span></div>
      <nav class="navbar">
        <a href="#home">Home</a>
        <a href="#cars">Cars</a>
        <a href="#about">About</a>
        <a href="#contacts">Contacts</a>
        
        <?php if(isset($_SESSION['adminID'])): ?>
          <a href="../admin/admin_dashboard.php">Dashboard</a>
          <a href="logout.php">Logout</a>
        <?php else: ?>
          <a href="#" id="navAdminBtn">Admin</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <div id="home" class="landing-container">
    <div class="hero-left">
      <div class="sidebar-socials">
        <svg class="social-icon" viewBox="0 0 24 24"><path d="M9 8H7v3h2v9h3v-9h3l.5-3H12V6c0-.5.5-1 1-1h2V2h-3a4 4 0 0 0-4 4v2z"/></svg>
        <svg class="social-icon" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.051.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>
        <svg class="social-icon" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
        <svg class="social-icon" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.513 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.713-1.455L0 24zm6.59-3.472l.355.211c1.55.919 3.333 1.404 5.156 1.405 5.511 0 9.994-4.487 9.997-10 .002-2.671-1.031-5.181-2.908-7.061C17.315 3.203 14.81 2.169 12.14 2.169 6.63 2.169 2.148 6.65 2.146 12.16c0 1.849.482 3.655 1.398 5.222l.23.394-.99 3.621 3.708-.973z"/></svg>
      </div>

      <h1 class="hero-heading">Enjoy your life with our comfortable cars.</h1>
      <p class="hero-subtext">Abe Nuar Kereta Sewa is ready to serve the best experience in car rental.</p>

      <div class="hero-action-buttons">
        <a href="../customer/cust_booking.php" class="btn-book">Book Now</a>
        <button id="viewBookingsBtn" class="btn-return">View Bookings</button>
        <button id="openReturnModal" class="btn-return">Return Car</button>
      </div>
    </div>

    <div class="hero-right">
      <div class="logo-card-container">
        <div class="logo-card">
          <img src="../images/logo.png" alt="Abe Nuar Showcase">
        </div>
      </div>
    </div>
  </div>

  <section id="cars">
    <h2 class="section-title">Our Featured Cars<div class="title-underline"></div></h2>
    <p class="section-subtitle">Choose the perfect car for your journey</p>
    
    <div class="cars-grid">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): 
                $carID = $row['carID'];
                $carBrand = htmlspecialchars($row['carBrand']);
                $carModel = htmlspecialchars($row['carModel']);
                $carPlate = htmlspecialchars($row['carPlate']);
                $carPrice = number_format($row['carPrice'], 2);
                $carAvailability = htmlspecialchars($row['carAvailability']);
                
                // ============================================================
                // DYNAMIC SMART IMAGE PATH RESOLVER
                // ============================================================
                $rawImage = $row['carImage'];
                if (empty($rawImage)) {
                    // Fallback to default path relative to subfolder
                    $carImage = "../imgweb/default-car.jpg";
                } else {
                    // If the DB path already starts with 'uploads/', strip it out so we can fix it cleanly
                    if (strpos($rawImage, 'uploads/') === 0) {
                        $rawImage = substr($rawImage, 8); // keeps everything after 'uploads/'
                    }
                    // Prepend the correct parent directory escape path targeting root uploads/ folder
                    $carImage = "../admin/uploads/" . $rawImage;
                }
                
                $statusColor = (strtolower($carAvailability) == 'available') ? '#28a745' : '#dc3545';
            ?>
                <div class="car-card">
                    <div class="car-img-box">
                        <img src="<?php echo $carImage; ?>" alt="<?php echo $carBrand . ' ' . $carModel; ?>">
                    </div>
                    
                    <div class="car-info">
                        <div class="car-name"><?php echo $carBrand . ' ' . $carModel; ?></div>
                        
                        <div class="car-specs">
                          <div class="spec-item">
                            <span><strong>Plate Number:</strong> <?php echo $carPlate; ?></span>
                          </div>
                          <div class="spec-item">
                            <span style="color: <?php echo $statusColor; ?>; font-weight: 600;">
                              ● <?php echo ucfirst($carAvailability); ?>
                            </span>
                          </div>
                        </div>
                        
                        <div class="car-footer">
                            <div class="price-box">
                                <div class="price-label">Rate</div>
                                <div class="price-value">RM <?php echo $carPrice; ?>/hr</div>
                            </div>
                            
                            <?php if (strtolower($carAvailability) == 'available'): ?>
                                <a href="../customer/cust_booking.php?carID=<?php echo $carID; ?>" class="btn-book" style="padding: 10px 24px; font-size: 0.9rem; box-shadow: none;">Book Now</a>
                            <?php else: ?>
                                <button class="btn-book" style="padding: 10px 24px; font-size: 0.9rem; box-shadow: none; background-color: #555555; cursor: not-allowed;" disabled>Unavailable</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align: center; width: 100%; color: #aaaaaa;">No vehicles found in the fleet listing database.</p>
        <?php endif; ?>
    </div>
  </section>

  <section id="about" style="border-top: 1px solid rgba(255,255,255,0.03);">
    <h2 class="section-title">About Our Company<div class="title-underline"></div></h2>
    <p class="section-subtitle">Your reliable transportation partner</p>

    <div class="about-wrapper">
      <div class="about-text">
        <p>At Abe Nuar Kereta Sewa, we are committed to providing smooth, comfortable, and affordable car rental experiences. Whether you need a compact city car for daily commuting or a spacious vehicle for family travel, our fleet is fully serviced to guarantee safety and performance.</p>
        <p>With simple documentation, immediate vehicle collection processing, and crystal clear rates, we strive to make car hire completely stress-free.</p>
      </div>
      <div class="about-features">
        <div class="feat-box"><h4>Well Maintained Fleet</h4><p>Every single car passes dynamic safety verification checks regularly.</p></div>
        <div class="feat-box"><h4>Affordable Rates</h4><p>Budget-friendly pricing calculations per hour and flat day rates.</p></div>
        <div class="feat-box"><h4>Flexible Options</h4><p>Fast booking extension options and scalable time frames.</p></div>
        <div class="feat-box"><h4>24/7 Support</h4><p>Our help center remains active day and night for emergency issues.</p></div>
      </div>
    </div>
  </section>

  <section id="contacts" style="border-top: 1px solid rgba(255,255,255,0.03); padding-bottom: 40px;">
    <h2 class="section-title">Contact Us<div class="title-underline"></div></h2>
    <p class="section-subtitle">Reach out for bookings and inquiries</p>

    <div class="contact-wrapper">
      <div class="contact-info-cards">
        <div class="info-card">
          <div class="info-icon-circle">
            <svg viewBox="0 0 24 24"><path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 0 0-1.01.24l-2.2 2.2c-2.83-1.44-5.15-3.75-6.59-6.59l2.2-2.21c.28-.26.36-.65.25-1C9.1 6.42 8.9 5.23 8.9 4c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1 0 9.39 7.61 17 17 17 .55 0 1-.45 1-1v-3.58c0-.56-.45-1-1-1z"/></svg>
          </div>
          <div class="info-details"><h4>Call or WhatsApp</h4><p>+60 12-345 6789</p></div>
        </div>
        <div class="info-card">
          <div class="info-icon-circle">
            <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
          </div>
          <div class="info-details"><h4>Email Support</h4><p>support@abenuar.com</p></div>
        </div>
        <div class="info-card">
          <div class="info-icon-circle">
            <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
          </div>
          <div class="info-details"><h4>Main Office</h4><p>Machang, Kelantan, Malaysia</p></div>
        </div>
      </div>
    </div>

    <div class="footer-credits">
      &copy; 2026 Abe Nuar Kereta Sewa. All rights reserved.
    </div>
  </section>

  <div class="modal-overlay" id="modalOverlay">

    <div class="modal-box admin-modal" id="adminModal" style="display:none;">
      <div class="admin-header">
        <h3>ADMIN VERIFICATION PANEL</h3>
      </div>
      <div class="admin-body">
        <label class="admin-label">Enter identification number</label>
        <input type="text" class="admin-input" id="adminIDInput" placeholder="e.g., A1234567">
        <div class="admin-error" id="adminErrorMsg">Verification requires 4 numbers/characters and above.</div>
        <button class="admin-btn" id="adminSubmitBtn">Submit</button>
      </div>
    </div>

    <div class="modal-box view-bookings-modal" id="viewBookingsModal" style="display:none;">
      <h3 class="view-bookings-title">View Your Bookings</h3>
      <p class="view-bookings-text">Please enter your identification number to continue.</p>
      <input type="text" class="view-bookings-input" id="bookingIdInput" placeholder="e.g. A1234567">
      <div class="view-bookings-error" id="bookingIdError">Please enter at least 4 characters.</div>
      <button class="view-bookings-btn" id="bookingLookupBtn">Continue</button>
    </div>

    <div class="modal-box return-modal" id="returnModal" style="display:none;">
      <h3 class="return-title">Confirm Car Return?</h3>
      <div class="return-actions">
        <button class="btn-ret-confirm" id="btnRetYes">YES</button>
        <button class="btn-ret-confirm" id="btnRetNo">NO</button>
      </div>
    </div>

  </div>

  <script>
    /* ============================================================
       PAGE ROUTING TABLE
    ============================================================ */
    const ROUTES = {
      adminPage        : '../admin/admin_dashboard.php',
      viewBookings     : '../customer/cust_viewbook.php',
      // We route to the verification page FIRST so the system can check for "Pending" status and IC
      returnPage       : '../customer/cust_returnverify.php',
    };

    /* ============================================================
       MODAL POPUP ENGINE
    ============================================================ */
    const adminBtn = document.getElementById('navAdminBtn');
    const viewBookBtn = document.getElementById('viewBookingsBtn');
    const returnBtn = document.getElementById('openReturnModal');

    const modalOverlay = document.getElementById('modalOverlay');
    const adminModal = document.getElementById('adminModal');
    const viewBookingsModal = document.getElementById('viewBookingsModal');
    const returnModal = document.getElementById('returnModal');

    function openModal(modalElement) {
      adminModal.style.display = 'none';
      viewBookingsModal.style.display = 'none';
      returnModal.style.display = 'none';

      modalElement.style.display = 'block';
      modalOverlay.classList.add('active');
    }

    function closeModal() {
      modalOverlay.classList.remove('active');
    }

    if (adminBtn) adminBtn.addEventListener('click', (e) => { e.preventDefault(); openModal(adminModal); });
    if (viewBookBtn) viewBookBtn.addEventListener('click', () => { openModal(viewBookingsModal); });
    if (returnBtn) returnBtn.addEventListener('click', () => { openModal(returnModal); });

    window.addEventListener('click', (e) => {
        if (e.target.id === 'modalOverlay') {
            closeModal();
        }
    });

    /* ============================================================
       INNER MODAL BUTTON ACTIONS
    ============================================================ */
    document.getElementById('adminSubmitBtn').addEventListener('click', () => {
      window.location.href = ROUTES.adminPage;
    });

    document.getElementById('bookingLookupBtn').addEventListener('click', () => {
      const inputId = document.getElementById('bookingIdInput').value;
      if (inputId.length >= 4) {
        window.location.href = ROUTES.viewBookings + "?id=" + encodeURIComponent(inputId);
      } else {
        document.getElementById('bookingIdError').style.display = 'block';
      }
    });

    document.getElementById('btnRetYes').addEventListener('click', () => {
      window.location.href = ROUTES.returnPage;
    });
    
    document.getElementById('btnRetNo').addEventListener('click', () => {
      closeModal();
    });
  </script>

</body>
</html>