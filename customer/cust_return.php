<?php
// Start session to access active verification variables
session_start();

// Redirect to lookup verification page if no active reservation session exists
if (!isset($_SESSION['active_bookID'])) {
    header("Location: cust_returnverify.php");
    exit();
}

// Database Configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "keretasewa_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$bookID = intval($_SESSION['active_bookID']);

// Process Form Data upon Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Check if POST data was dropped due to file size limits
    if (empty($_POST)) {
        die("Error: Form data was dropped. The image file you uploaded is too large for the server's current PHP limits. Try a smaller image.");
    }

    $locationInput = $conn->real_escape_string(trim($_POST['car_location']));
    $returnDate = $conn->real_escape_string($_POST['return_date']);
    $returnTime = $conn->real_escape_string($_POST['return_time']);
    
    $carImage = "";
    if (isset($_POST['car_image_base64']) && !empty($_POST['car_image_base64'])) {
        $carImage = $conn->real_escape_string($_POST['car_image_base64']);
    }

    $lookup_sql = "SELECT custID, carID FROM booking WHERE bookID = $bookID LIMIT 1";
    $lookup_result = $conn->query($lookup_sql);
    
    if ($lookup_result && $lookup_result->num_rows > 0) {
        $booking_row = $lookup_result->fetch_assoc();
        $custID = intval($booking_row['custID']);
        $carID = intval($booking_row['carID']);
        
        // INSERT into the return_update table
        $insert_sql = "INSERT INTO return_update (bookID, custID, carID, carLoc, returnDate, returnTime, carCondition) 
                       VALUES ($bookID, $custID, $carID, '$locationInput', '$returnDate', '$returnTime', '$carCondition')";
                       
        if ($conn->query($insert_sql)) {
            // Update the main booking status
            $update_booking = "UPDATE booking SET bookStatus = 'Returned' WHERE bookID = $bookID";
            $conn->query($update_booking);
            
            // Clear active verification session variables
            unset($_SESSION['active_bookID']);
            unset($_SESSION['active_custName']);
            unset($_SESSION['active_carPlate']);
            unset($_SESSION['active_carModel']);
            
            // FORCED PHP REDIRECT (Reliably sends user to the payment page)
            header("Location: cust_payment.php?bookID=" . $bookID);
            exit();
        } else {
            // Show exact database error if SQL fails
            die("Database Error: " . $conn->error . " Please make sure your return_update table exists and columns match.");
        }
    } else {
        die("Error: Original booking reference could not be found.");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Return Detail</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Poppins', sans-serif; }
    body { background-color: #0b0e14; color: #ffffff; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
    .container { width: 100%; max-width: 1000px; background-color: #0f131c; border: 1px solid rgba(255, 255, 255, 0.03); border-radius: 12px; padding: 40px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4); }
    .header-section { margin-bottom: 35px; text-align: center; }
    .title { font-size: 28px; font-weight: 700; color: #ffffff; margin-bottom: 8px; }
    .subtitle { font-size: 14px; color: #8892b0; }
    .summary-card { background-color: rgba(124, 158, 255, 0.05); border: 1px solid rgba(124, 158, 255, 0.15); padding: 20px; border-radius: 10px; margin-bottom: 30px; display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .summary-item { display: flex; flex-direction: column; gap: 5px; }
    .summary-label { font-size: 11px; color: #7c9eff; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; }
    .summary-value { font-size: 15px; color: #ffffff; font-weight: 500; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; }
    .input-group { display: flex; flex-direction: column; gap: 8px; }
    .full-width { grid-column: span 2; }
    .form-label { font-size: 13px; font-weight: 600; color: #7c9eff; text-transform: uppercase; letter-spacing: 0.5px; }
    .input-control { width: 100%; background-color: #1a1f2e; border: 1px solid #2a334a; padding: 14px 18px; border-radius: 8px; color: #ffffff; font-size: 14px; outline: none; transition: all 0.3s ease; }
    .input-control:focus { border-color: #7c9eff; box-shadow: 0 0 0 3px rgba(124, 158, 255, 0.15); }
    .upload-box { border: 2px dashed #2a334a; background-color: #1a1f2e; padding: 30px 20px; border-radius: 10px; text-align: center; cursor: pointer; transition: all 0.3s ease; position: relative; }
    .upload-box:hover { border-color: #7c9eff; background-color: rgba(124, 158, 255, 0.05); }
    .upload-box.has-image { border-color: #10b981; background-color: rgba(16, 185, 129, 0.05); }
    .upload-content { display: flex; flex-direction: column; align-items: center; gap: 10px; pointer-events: none; }
    .upload-content svg { width: 36px; height: 36px; fill: #7c9eff; }
    .upload-text { color: #8892b0; font-size: 14px; }
    #imagePreview { display: none; max-width: 100%; max-height: 250px; margin-top: 15px; border-radius: 8px; }
    .input-error { border-color: #ef4444 !important; background-color: rgba(239, 68, 68, 0.05) !important; }
    .error-msg { color: #ef4444; font-size: 12px; margin-top: 2px; display: none; }
    @keyframes vibrate { 0%, 100% { transform: translateX(0); } 20% { transform: translateX(-4px); } 40% { transform: translateX(4px); } 60% { transform: translateX(-4px); } 80% { transform: translateX(4px); } }
    .vibrate { animation: vibrate 0.3s ease-in-out; }
    .footer-actions { margin-top: 35px; display: flex; gap: 15px; }
    .btn-submit { flex: 1; background: #7c9eff; color: #0b0e14; border: none; padding: 16px; border-radius: 8px; font-size: 16px; font-weight: 700; cursor: pointer; transition: all 0.3s ease; text-transform: uppercase; letter-spacing: 1px; }
    .btn-submit:hover { background: #a5bfff; transform: translateY(-2px); box-shadow: 0 8px 25px rgba(124, 158, 255, 0.3); }
    .btn-back { flex: 1; background: transparent; color: #7c9eff; border: 1px solid #2a334a; padding: 16px; border-radius: 8px; font-size: 16px; font-weight: 700; cursor: pointer; transition: all 0.3s ease; text-transform: uppercase; letter-spacing: 1px; text-align: center; text-decoration: none; }
    .btn-back:hover { background: rgba(124, 158, 255, 0.05); border-color: #7c9eff; transform: translateY(-2px); }
    @media (max-width: 600px) { .form-grid { grid-template-columns: 1fr; } .full-width { grid-column: span 1; } .container { padding: 25px; } .footer-actions { flex-direction: column-reverse; } }
  </style>
</head>
<body>
  <div class="container">
    <div class="header-section">
      <h2 class="title">Return Detail</h2>
      <p class="subtitle">Complete the form below to process your vehicle return.</p>
    </div>

    <div class="summary-card">
      <div class="summary-item">
        <span class="summary-label">Booking ID</span>
        <span class="summary-value">#BK-<?php echo $_SESSION['active_bookID']; ?></span>
      </div>
      <div class="summary-item">
        <span class="summary-label">Customer Name</span>
        <span class="summary-value"><?php echo htmlspecialchars($_SESSION['active_custName']); ?></span>
      </div>
      <div class="summary-item">
        <span class="summary-label">Vehicle</span>
        <span class="summary-value"><?php echo htmlspecialchars($_SESSION['active_carModel']); ?></span>
      </div>
      <div class="summary-item">
        <span class="summary-label">Plate Number</span>
        <span class="summary-value"><?php echo htmlspecialchars($_SESSION['active_carPlate']); ?></span>
      </div>
    </div>

    <form method="POST" action="" onsubmit="return validateAndProceed(event)">
      <div class="form-grid">
        <div class="input-group full-width">
          <label class="form-label">Drop-off Location</label>
          <input type="text" class="input-control" id="carLocationInput" name="car_location" placeholder="e.g. Block A Parking" oninput="clearLocationError()">
          <span class="error-msg" id="locationErrorMsg">*Please specify where the car is parked.</span>
        </div>
        
        <div class="input-group">
          <label class="form-label">Return Date</label>
          <input type="date" class="input-control" id="returnDate" name="return_date" required readonly style="opacity: 0.7; cursor: not-allowed; background-color: #141924;">
        </div>
        
        <div class="input-group">
          <label class="form-label">Return Time</label>
          <input type="time" class="input-control" id="returnTime" name="return_time" required readonly style="opacity: 0.7; cursor: not-allowed; background-color: #141924;">
        </div>
      </div>

      <div class="input-group full-width">
        <label class="form-label">Vehicle Photo Evidence</label>
        <input type="file" id="imageUploadInput" accept="image/*" style="display:none;" onchange="handleImageUpload(this)">
        <input type="hidden" id="carImageBase64" name="car_image_base64">
        
        <div class="upload-box" id="uploadBox" onclick="document.getElementById('imageUploadInput').click()">
          <div class="upload-content" id="uploadPlaceholder">
            <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zm-5.04-6.71l-2.75 3.54-1.96-2.36L6.5 17h11l-3.54-4.71z"/></svg>
            <span class="upload-text">Tap to upload a photo of the parked car</span>
          </div>
          <img id="imagePreview" alt="Car Preview">
        </div>
        <span class="error-msg" id="imageErrorMsg">*A photo of the vehicle is required.</span>
      </div>

      <div class="footer-actions">
        <a href="cust_returnverify.php" class="btn-back">Back</a>
        <button type="submit" class="btn-submit">Submit Return</button>
      </div>
    </form>
  </div>

  <script>
    let uploadedImageBase64 = null;

    function syncLiveDeviceTime() {
      const now = new Date();
      const year = now.getFullYear();
      const month = String(now.getMonth() + 1).padStart(2, '0');
      const day = String(now.getDate()).padStart(2, '0');
      const hours = String(now.getHours()).padStart(2, '0');
      const minutes = String(now.getMinutes()).padStart(2, '0');

      document.getElementById('returnDate').value = `${year}-${month}-${day}`;
      document.getElementById('returnTime').value = `${hours}:${minutes}`;
    }

    syncLiveDeviceTime();
    setInterval(syncLiveDeviceTime, 15000);

    function handleImageUpload(input) {
      if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
          uploadedImageBase64 = e.target.result;
          document.getElementById('carImageBase64').value = uploadedImageBase64;
          document.getElementById('imagePreview').src = uploadedImageBase64;
          document.getElementById('imagePreview').style.display = 'inline-block';
          document.getElementById('uploadPlaceholder').style.display = 'none';
          
          const box = document.getElementById('uploadBox');
          box.classList.add('has-image');
          box.classList.remove('input-error');
          document.getElementById('imageErrorMsg').style.display = 'none';
        }
        reader.readAsDataURL(input.files[0]);
      }
    }

    function triggerImageVibration() {
      const box = document.getElementById('uploadBox');
      const errorMsg = document.getElementById('imageErrorMsg');
      box.classList.add('input-error');
      errorMsg.style.display = "block";
      box.classList.remove('vibrate'); void box.offsetWidth; box.classList.add('vibrate');
    }

    function triggerLocationVibration() {
      const input = document.getElementById('carLocationInput');
      const errorMsg = document.getElementById('locationErrorMsg');
      input.classList.add('input-error');
      errorMsg.style.display = "block";
      input.classList.remove('vibrate'); void input.offsetWidth; input.classList.add('vibrate');
    }

    function clearLocationError() {
      const locationInput = document.getElementById('carLocationInput').value.trim();
      if (locationInput !== "") {
        document.getElementById('carLocationInput').classList.remove('input-error');
        document.getElementById('locationErrorMsg').style.display = "none";
      }
    }

    function validateAndProceed(event) {
      syncLiveDeviceTime();
      const locationInput = document.getElementById('carLocationInput').value.trim();
      let isFormValid = true;

      if (locationInput === "") { triggerLocationVibration(); isFormValid = false; }
      if (!uploadedImageBase64) { triggerImageVibration(); isFormValid = false; }

      // DO NOT PREVENT DEFAULT IF VALID. Let the browser submit to PHP normally.
      if (!isFormValid) {
        event.preventDefault(); 
        return false;
      }
      
      return true;
    }
  </script>
</body>
</html>