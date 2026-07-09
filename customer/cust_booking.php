<?php
// Start session to store customer details or display notifications
session_start();

// Database Configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "keretasewa_db";

// Connect to MySQL Server
$conn = new mysqli($servername, $username, $password, $dbname);

// Verify Database Connection Integrity
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all available vehicles from the database to populate the dropdown selection option
$cars_list = [];
$list_query = "SELECT carID, carBrand, carModel, carPrice FROM car";
$list_result = $conn->query($list_query);
if ($list_result && $list_result->num_rows > 0) {
    while ($row = $list_result->fetch_assoc()) {
        $cars_list[] = $row;
    }
}

// Extract Car ID from the URL query parameters (sent from vehicle selection context, defaults to 1)
$carID = isset($_GET['carID']) ? intval($_GET['carID']) : 1; 

// Fetch current vehicle pricing rate and specs from the database for active total calculations
$carBrand = "Vehicle";
$carModel = "";
$carPrice = 0.00;

$car_query = "SELECT carBrand, carModel, carPrice FROM car WHERE carID = $carID LIMIT 1";
$car_result = $conn->query($car_query);
if ($car_result && $car_result->num_rows > 0) {
    $car_row = $car_result->fetch_assoc();
    $carBrand = $car_row['carBrand'];
    $carModel = $car_row['carModel'];
    $carPrice = floatval($car_row['carPrice']);
}

// Form Processing Block
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect selected carID from post context to support active vehicle switching
    $carID = isset($_POST['car_id']) ? intval($_POST['car_id']) : $carID;
    
    // Fetch current vehicle pricing rate from the database for active total calculations
    $carPrice = 0.00;
    $car_price_query = "SELECT carPrice FROM car WHERE carID = $carID LIMIT 1";
    $price_result = $conn->query($car_price_query);
    if ($price_result && $price_result->num_rows > 0) {
        $price_row = $price_result->fetch_assoc();
        $carPrice = floatval($price_row['carPrice']);
    }

    // Collect and sanitize form user elements
    $cust_type = $conn->real_escape_string($_POST['cust_type']); // 'Student' or 'Public'
    $name = $conn->real_escape_string(trim($_POST['name']));
    $ic = $conn->real_escape_string(trim($_POST['id_card_number']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $phone = $conn->real_escape_string(trim($_POST['contact_no']));
    $pickup_area = $conn->real_escape_string(trim($_POST['pickup_area']));
    $address = $conn->real_escape_string(trim($_POST['address_details']));
    
    // Format Dates from frontend Flatpickr 'd/m/Y' structure to standard MySQL 'Y-m-d' compatibility
    $start_date_raw = $_POST['start_date'];
    $end_date_raw = $_POST['end_date'];
    
    $start_date_obj = DateTime::createFromFormat('d/m/Y', $start_date_raw);
    $end_date_obj = DateTime::createFromFormat('d/m/Y', $end_date_raw);
    
    $pickupDate = $start_date_obj ? $start_date_obj->format('Y-m-d') : date('Y-m-d');
    $returnDate = $end_date_obj ? $end_date_obj->format('Y-m-d') : date('Y-m-d');
    
    $pickupTime = $conn->real_escape_string($_POST['pickup_time']);
    $returnTime = $conn->real_escape_string($_POST['delivery_time']);
    
    // Server-Side Rental Day & Cost Calculation for Database Alignment
    $rentDays = 1;
    if ($start_date_obj && $end_date_obj) {
        $diff = $start_date_obj->diff($end_date_obj);
        $rentDays = $diff->days === 0 ? 1 : $diff->days;
    }
    $calcTotal = $carPrice * $rentDays;
    $rentTotal = "RM " . number_format($calcTotal, 2);

    // Setup secure uploads target directories
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $driver_license_path = '';
    $student_card_path = '';
    
    // Process Driver's License file system update stream
    if (isset($_FILES['driver_license']) && $_FILES['driver_license']['error'] === UPLOAD_ERR_OK) {
        $driver_ext = pathinfo($_FILES['driver_license']['name'], PATHINFO_EXTENSION);
        $driver_filename = 'license_' . $ic . '_' . time() . '.' . $driver_ext;
        $driver_license_path = $upload_dir . $driver_filename;
        move_uploaded_file($_FILES['driver_license']['tmp_name'], $driver_license_path);
    }
    
    // Process Student Card file updates only if client role matches academic constraints
    if ($cust_type === 'Student' && isset($_FILES['student_card']) && $_FILES['student_card']['error'] === UPLOAD_ERR_OK) {
        $student_ext = pathinfo($_FILES['student_card']['name'], PATHINFO_EXTENSION);
        $student_filename = 'student_' . $ic . '_' . time() . '.' . $student_ext;
        $student_card_path = $upload_dir . $student_filename;
        move_uploaded_file($_FILES['student_card']['tmp_name'], $student_card_path);
    }

    // Check if customer structural trace records already exist by checking unique Identification Codes (IC)
    $check_cust = "SELECT custID FROM customer WHERE custIC = '$ic' LIMIT 1";
    $cust_result = $conn->query($check_cust);
    
    if ($cust_result && $cust_result->num_rows > 0) {
        $cust_row = $cust_result->fetch_assoc();
        $custID = $cust_row['custID'];
        
        // Update existing profile details with new elements while preserving missing images
        $update_cust = "UPDATE customer SET custName = '$name', custEmail = '$email', contactno = '$phone', custType = '$cust_type'";
        if (!empty($driver_license_path)) {
            $update_cust .= ", custLicense = '$driver_license_path'";
        }
        if (!empty($student_card_path)) {
            $update_cust .= ", custStudentCard = '$student_card_path'";
        }
        $update_cust .= " WHERE custID = $custID";
        $conn->query($update_cust);
    } else {
        // Fallback defaults matching native structural requirements if not passed directly
        $final_license = !empty($driver_license_path) ? $driver_license_path : 'imgweb/default-license.jpg';
        $final_student = !empty($student_card_path) ? $student_card_path : 'imgweb/default-studentcard.jpg';

        // Insert new systematic profile row
        $insert_cust = "INSERT INTO customer (custName, custIC, custEmail, contactno, custType, custLicense, custStudentCard) 
                        VALUES ('$name', '$ic', '$email', '$phone', '$cust_type', '$final_license', '$final_student')";
        if ($conn->query($insert_cust)) {
            $custID = $conn->insert_id;
        } else {
            die("Error saving customer profile dataset: " . $conn->error);
        }
    }
    
    // Append active transaction dataset log mapping into master booking tables 
    $insert_booking = "INSERT INTO booking (custID, carID, pickupDate, pickupTime, returnDate, returnTime, pickupLoc, rentTotal, address, bookStatus) 
                      VALUES ($custID, $carID, '$pickupDate', '$pickupTime', '$returnDate', '$returnTime', '$pickup_area', '$rentTotal', '$address', 'Pending')";
                      
    if ($conn->query($insert_booking)) {
        echo "<script>
                alert('✅ Success! All information is complete and the form has been successfully submitted.');
                window.location.href = '../auth/index.php';
              </script>";
        exit();
    } else {
        echo "<script>alert('❌ Error processing structural record entry: " . $conn->real_escape_string($conn->error) . "');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Info Form - Unified Portal</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #121212;
            color: #ffffff;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 40px 20px;
        }

        .booking-container {
            background-color: #1a1a1a;
            width: 100%;
            max-width: 1100px;
            padding: 40px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.5);
        }

        .header-section {
            margin-bottom: 35px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding-bottom: 20px;
        }

        .header-section h2 {
            font-size: 28px;
            color: #ffffff;
            font-weight: 800;
            letter-spacing: 0.5px;
        }

        .header-section p {
            color: #aaaaaa;
            font-size: 14px;
            margin-top: 4px;
        }

        .section-divider {
            margin: 30px 0 25px 0;
            border-top: 1px dashed rgba(255, 255, 255, 0.1);
            padding-top: 20px;
        }

        /* GRID SYSTEM */
        .form-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-bottom: 24px;
        }

        .form-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-bottom: 24px;
        }

        /* Date Range Combo Inputs */
        .date-range-group {
            display: flex;
            background-color: #ffffff;
            border: 1px solid #ced4da;
            border-radius: 8px;
            overflow: hidden;
            height: 48px;
        }

        .date-range-group input {
            border: none !important;
            border-radius: 0 !important;
            text-align: center;
        }

        .date-range-group input:first-child {
            border-right: 1px solid #ced4da !important;
        }

        .full-width {
            grid-column: span 3;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .form-group label {
            font-size: 12px;
            font-weight: 700;
            color: #7b91f9;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            background-color: #ffffff;
            color: #333333;
            height: 48px;
        }

        .form-group input[readonly] {
            background-color: #e9ecef;
            color: #555555;
            font-weight: 600;
            cursor: not-allowed;
        }

        .time-warning {
            font-size: 11px;
            color: #ff5630;
            font-weight: 600;
            margin-top: 6px;
            display: none;
            position: absolute;
            bottom: -18px;
            left: 0;
        }

        /* Upload Grids */
        .upload-side-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .upload-card {
            background-color: #242424;
            padding: 24px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            width: 100%;
        }

        .upload-inner-box {
            background-color: #1a1a1a;
            border-radius: 8px;
            padding: 35px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        .blue-upload-btn {
            background-color: rgba(123, 145, 249, 0.15);
            color: #7b91f9;
            border: 1px solid rgba(123, 145, 249, 0.3);
            padding: 12px 24px;
            font-weight: 600;
            font-size: 13px;
            border-radius: 30px;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
        }

        .blue-upload-btn:hover {
            background-color: #7b91f9;
            color: #ffffff;
        }

        .preview-box {
            border: 2px dashed rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 15px;
            width: 100%;
            text-align: center;
            background-color: rgba(0, 0, 0, 0.2);
            min-height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #aaa;
            font-size: 13px;
        }

        .preview-box.uploaded {
            border-color: #36b37e;
            background-color: rgba(54, 179, 126, 0.1);
            color: #36b37e;
            font-weight: 600;
        }

        /* Footer Navigation Buttons */
        .footer-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 40px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 25px;
        }

        .right-buttons {
            display: flex;
            gap: 15px;
        }

        .main-btn {
            display: inline-block;
            background-color: #7b91f9;
            color: #ffffff;
            padding: 12px 36px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .main-btn:hover {
            background-color: #6c7fd8;
            transform: translateY(-2px);
        }

        .main-btn-outline {
            background-color: transparent;
            color: #ffffff;
            border: 2px solid #555555;
            border-radius: 50px;
        }

        .main-btn-outline:hover {
            background-color: rgba(255, 255, 255, 0.05);
            border-color: #ffffff;
        }

        .btn-clear {
            background-color: rgba(255, 86, 48, 0.1);
            color: #ff5630;
            border: 1px solid rgba(255, 86, 48, 0.3);
        }

        .btn-clear:hover {
            background-color: #ff5630;
            color: #ffffff;
        }

        @media (max-width: 768px) {
            .form-grid-3, .form-grid-2, .upload-side-grid { grid-template-columns: 1fr !important; }
            .full-width { grid-column: span 1; }
            .footer-navigation { flex-direction: column; gap: 15px; align-items: stretch; }
            .right-buttons { flex-direction: column; gap: 15px; }
        }
    </style>
</head>
<body>

<div class="booking-container">
    <form id="bookingForm" action="" method="POST" enctype="multipart/form-data" onsubmit="return validateForm(event)">
        
        <div class="header-section">
            <h2>Car Rental Booking Matrix</h2>
            <p>Active Portal Interface - Complete Your Registration Safely</p>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label>Select Vehicle *</label>
                <select id="car_id" name="car_id" onchange="updateCarSelection()">
                    <?php foreach ($cars_list as $car): ?>
                        <option value="<?php echo $car['carID']; ?>" data-price="<?php echo $car['carPrice']; ?>" <?php echo ($car['carID'] == $carID) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($car['carBrand'] . ' ' . $car['carModel'] . ' - RM ' . number_format($car['carPrice'], 2) . '/Hour'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Customer Type *</label>
                <select id="cust_type" name="cust_type" onchange="toggleCustomerType()">
                    <option value="Public">Public / General</option>
                    <option value="Student">Student</option>
                </select>
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label>Name *</label>
                <input type="text" id="main_name" name="name" placeholder="Your full name">
            </div>
            <div class="form-group">
                <label>Identification Card Number (IC) *</label>
                <input type="text" id="id_card_number" name="id_card_number" placeholder="Example: 010203045566">
            </div>
        </div>

        <div class="upload-side-grid" id="upload_matrix_grid" style="grid-template-columns: 1fr;">
            <div class="upload-card" id="driver_license_wrapper">
                <div class="upload-inner-box">
                    <input type="file" id="driver_license" name="driver_license" accept="image/*" style="display:none" onchange="updateUploadStatus(this, 'driver_preview', 'driver_text')">
                    <button type="button" class="blue-upload-btn" onclick="document.getElementById('driver_license').click()">Click to upload Driver's License Picture *</button>
                    <div class="preview-box" id="driver_preview">
                        <span id="driver_text">No file chosen yet</span>
                    </div>
                </div>
            </div>

            <div class="upload-card" id="student_card_wrapper" style="display: none;">
                <div class="upload-inner-box">
                    <input type="file" id="student_card" name="student_card" accept="image/*" style="display:none" onchange="updateUploadStatus(this, 'student_preview', 'student_text')">
                    <button type="button" class="blue-upload-btn" onclick="document.getElementById('student_card').click()">Click to upload Student Card Picture *</button>
                    <div class="preview-box" id="student_preview">
                        <span id="student_text">No file chosen yet</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-divider"></div>

        <div class="form-grid-3">
            <div class="form-group">
                <label>Email *</label>
                <input type="text" id="main_email" name="email" placeholder="client@domain.com">
            </div>
            <div class="form-group">
                <label>Contact No *</label>
                <input type="text" id="contact_no" name="contact_no" placeholder="Example: 0123456789">
            </div>
            <div></div> 
        </div>

        <div class="form-grid-3">
            <div class="form-group">
                <label>Rent Start & End Dates *</label>
                <div class="date-range-group">
                    <input type="text" id="start_date" name="start_date" placeholder="Start Date" style="width:50%;">
                    <input type="text" id="end_date" name="end_date" placeholder="End Date" style="width:50%;">
                </div>
            </div>
            <div class="form-group">
                <label>Pickup Time *</label>
                <input type="time" id="pickup_time" name="pickup_time" onchange="calculateRentDays()">
            </div>
            <div class="form-group">
                <label>Delivery / Return Time *</label>
                <input type="time" id="delivery_time" name="delivery_time" onchange="calculateRentDays()">
                <span class="time-warning" id="time_warning_msg">⚠️ End date adjusted to the next day.</span>
            </div>
        </div>

        <div class="form-grid-3">
            <div class="form-group">
                <label>Total Rent Days</label>
                <input type="text" id="total_days" name="total_days" placeholder="Auto calculated" readonly>
            </div>
            <div class="form-group">
                <label>Live Estimated Billing Summary</label>
                <input type="text" id="estimated_billing" placeholder="RM 0.00" readonly style="background-color: #e8f5e9; color: #2e7d32; font-weight: 700; border-color: #a5d6a7;">
            </div>
            <div class="form-group">
                <label>Select Pickup Area *</label>
                <select id="pickup_area" name="pickup_area">
                    <option value="" disabled selected>Select Destination Area</option>
                    <option value="Shop">Shop</option>
                    <option value="Kolej TDM">Kolej TDM</option>
                    <option value="Kolej DO">Kolej DO</option>
                    <option value="Kolej TAR">Kolej TAR</option>
                    <option value="Kolej TR">Kolej TR</option>
                    <option value="Kolej THO">Kolej THO</option>
                    <option value="Pintu Gerbang UiTM">Pintu Gerbang UiTM</option>
                    <option value="Pusat Islam">Pusat Islam</option>
                    <option value="Blok Kuliah - D">Blok Kuliah - D</option>
                </select>
            </div>
        </div>

        <div class="form-grid-3">
            <div class="form-group full-width">
                <label>Address Details *</label>
                <input type="text" id="address_details" name="address_details" placeholder="Enter full street address, apartment, unit, etc.">
            </div>
        </div>

        <div class="footer-navigation">
            <button type="button" class="main-btn btn-clear" onclick="clearForm()">Clear</button>
            <div class="right-buttons">
                <button type="button" class="main-btn main-btn-outline" onclick="goHome()">Home</button>
                <button type="submit" class="main-btn">Submit Booking</button>
            </div>
        </div>

    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
    let databaseRatePerDay = 0;
    let startPicker, endPicker;

    window.onload = function() {
        startPicker = flatpickr("#start_date", {
            dateFormat: "d/m/Y",
            minDate: "today",
            onChange: function(selectedDates, dateStr) {
                endPicker.set('minDate', dateStr);
                calculateRentDays();
            }
        });
        endPicker = flatpickr("#end_date", {
            dateFormat: "d/m/Y",
            minDate: "today",
            onChange: function() { calculateRentDays(); }
        });
        
        // Sync active car pricing layout attributes initial setup
        updateCarSelection();
        // Run initial load profile test structure sync
        toggleCustomerType();
    };

    // KEEPING CAR PICKING SYSTEM FULLY FUNCTIONAL BELOW
    function updateCarSelection() {
        const carSelect = document.getElementById('car_id');
        if (carSelect.options.length > 0) {
            const selectedOption = carSelect.options[carSelect.selectedIndex];
            databaseRatePerDay = parseFloat(selectedOption.getAttribute('data-price')) || 0;
            calculateRentDays();
        }
    }

    function toggleCustomerType() {
        const custType = document.getElementById('cust_type').value;
        const studentWrapper = document.getElementById('student_card_wrapper');
        const gridWrapper = document.getElementById('upload_matrix_grid');
        
        if (custType === 'Student') {
            studentWrapper.style.display = "block";
            if (window.innerWidth > 768) {
                gridWrapper.style.gridTemplateColumns = "repeat(2, 1fr)";
            }
        } else {
            studentWrapper.style.display = "none";
            gridWrapper.style.gridTemplateColumns = "1fr";
        }
    }

    function updateUploadStatus(input, previewId, textId) {
        const previewBox = document.getElementById(previewId);
        const statusText = document.getElementById(textId);
        if (input.files && input.files.length > 0) {
            previewBox.classList.add('uploaded');
            statusText.innerText = input.files[0].name;
        }
    }

    function clearForm() {
        if (confirm("Are you sure you want to clear all form inputs?")) {
            document.getElementById('bookingForm').reset();
            
            const pDriver = document.getElementById('driver_preview');
            if (pDriver) pDriver.classList.remove('uploaded');
            document.getElementById('driver_text').innerText = "No file chosen yet";

            const pStudent = document.getElementById('student_preview');
            if (pStudent) pStudent.classList.remove('uploaded');
            document.getElementById('student_text').innerText = "No file chosen yet";

            if (startPicker) startPicker.clear();
            if (endPicker) endPicker.clear();
            document.getElementById('time_warning_msg').style.display = "none";
            document.getElementById('estimated_billing').value = "RM 0.00";
            updateCarSelection();
            toggleCustomerType();
        }
    }

    function parseDateDMY(dateStr) {
        if (!dateStr) return null;
        const parts = dateStr.split('/');
        return new Date(parts[2], parts[1] - 1, parts[0]);
    }

    function calculateRentDays() {
        const startDateStr = document.getElementById('start_date').value;
        let endDateStr = document.getElementById('end_date').value;
        const pTime = document.getElementById('pickup_time').value;
        const dTime = document.getElementById('delivery_time').value;
        const totalDaysInput = document.getElementById('total_days');
        const billingInput = document.getElementById('estimated_billing');
        const warningMsg = document.getElementById('time_warning_msg');

        warningMsg.style.display = "none";

        if (startDateStr && endDateStr) {
            let start = parseDateDMY(startDateStr);
            let end = parseDateDMY(endDateStr);
            
            if (end && start) {
                if (startDateStr === endDateStr && pTime && dTime) {
                    if (dTime <= pTime) {
                        let nextDay = new Date(start);
                        nextDay.setDate(nextDay.getDate() + 1);
                        end = nextDay;
                        endPicker.setDate(nextDay);
                        warningMsg.style.display = "block";
                    }
                }
                const diff = Math.ceil((end.getTime() - start.getTime()) / (1000 * 3600 * 24));
                const totalDays = (diff === 0 ? 1 : diff);
                
                totalDaysInput.value = totalDays + " Days";
                
                // Real-Time Estimated Cost processing logic
                const aggregateCost = totalDays * databaseRatePerDay;
                billingInput.value = "RM " + aggregateCost.toFixed(2);
            }
        }
    }

    function validateForm(event) {
        const nameRegex = /^[a-zA-Z\s']{3,}$/; 
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/; 
        const phoneRegex = /^01[0-9]{8,10}$/; 

        const custType = document.getElementById('cust_type').value;
        const name = document.getElementById('main_name').value.trim();
        const ic = document.getElementById('id_card_number').value.trim();
        const dl = document.getElementById('driver_license').files.length;
        const sc = document.getElementById('student_card').files.length;
        const email = document.getElementById('main_email').value.trim();
        const phone = document.getElementById('contact_no').value.trim();
        const sDate = document.getElementById('start_date').value;
        const eDate = document.getElementById('end_date').value;
        const pTime = document.getElementById('pickup_time').value;
        const dTime = document.getElementById('delivery_time').value;
        const pArea = document.getElementById('pickup_area').value.trim();
        const address = document.getElementById('address_details').value.trim();

        if (!nameRegex.test(name)) {
            alert("⚠️ Please enter a valid Name!");
            event.preventDefault();
            return false;
        }
        if (ic === "" || ic.length < 6) {
            alert("⚠️ Please fill in your Identification Card Number completely!");
            event.preventDefault();
            return false;
        }
        if (dl === 0) {
            alert("⚠️ Please upload your Driver's License image!");
            event.preventDefault();
            return false;
        }
        if (custType === 'Student' && sc === 0) {
            alert("⚠️ Please upload your Student Card image!");
            event.preventDefault();
            return false;
        }
        if (!emailRegex.test(email)) {
            alert("⚠️ Invalid EMAIL format!");
            event.preventDefault();
            return false;
        }
        if (!phoneRegex.test(phone)) {
            alert("⚠️ Invalid PHONE NUMBER format! Use standard format (e.g., 0123456789)");
            event.preventDefault();
            return false;
        }
        if (sDate === "" || eDate === "" || pTime === "" || dTime === "" || pArea === "" || address === "") {
            alert("⚠️ Please fill in all required information marked with (*)");
            event.preventDefault();
            return false;
        }

        return true;
    }
    
    function goHome() { 
        window.location.href = '../auth/index.php'; 
    }
</script>

</body>
</html>
<?php $conn->close(); ?>