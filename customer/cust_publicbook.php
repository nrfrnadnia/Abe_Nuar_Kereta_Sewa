<?php
session_start();

// Database Configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "keretasewa_db";

// Establish Database Connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check Connection Integrity
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Fetch available cars to display in the form
$car_query = "SELECT * FROM car WHERE carAvailability = 'Available'";
$available_cars = $conn->query($car_query);

// Process form data entry updates upon secure submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the selected carID from the form 
    $carID = isset($_POST['carID']) ? intval($_POST['carID']) : 0;
    
    if ($carID === 0) {
        die("<script>alert('❌ Please select a vehicle first!'); window.history.back();</script>");
    }

    $name = $conn->real_escape_string(trim($_POST['name']));
    $ic = $conn->real_escape_string(trim($_POST['id_card_number']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $phone = $conn->real_escape_string(trim($_POST['contact_no']));
    
    // Parse Flatpickr 'd/m/Y' strings into 'Y-m-d' strings for database standard compatibility
    $start_date_raw = $_POST['start_date'];
    $end_date_raw = $_POST['end_date'];
    
    $start_date = '';
    if (!empty($start_date_raw)) {
        $parts = explode('/', $start_date_raw);
        if (count($parts) === 3) {
            $start_date = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }
    }
    
    $end_date = '';
    if (!empty($end_date_raw)) {
        $parts = explode('/', $end_date_raw);
        if (count($parts) === 3) {
            $end_date = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }
    }
    
    $pickup_time = $conn->real_escape_string($_POST['pickup_time']);
    $delivery_time = $conn->real_escape_string($_POST['delivery_time']);
    $pickup_area = $conn->real_escape_string(trim($_POST['pickup_area']));
    $address_details = $conn->real_escape_string(trim($_POST['address_details']));
    
    // Set up dedicated secure upload folder structures for licensing files
    $upload_dir = 'uploads/licenses/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $license_path = '';
    if (isset($_FILES['driver_license']) && $_FILES['driver_license']['error'] === UPLOAD_ERR_OK) {
        $file_ext = pathinfo($_FILES['driver_license']['name'], PATHINFO_EXTENSION);
        $new_filename = 'license_' . $ic . '_' . time() . '.' . $file_ext;
        $target_file_path = $upload_dir . $new_filename;
        if (move_uploaded_file($_FILES['driver_license']['tmp_name'], $target_file_path)) {
            $license_path = $target_file_path;
        }
    }
    
    // Cross-verify if customer record already exists to avoid redundant record duplicates
    $cust_check = "SELECT custID FROM customer WHERE custIC = '$ic' LIMIT 1";
    $res_check = $conn->query($cust_check);
    
    if ($res_check && $res_check->num_rows > 0) {
        $row_cust = $res_check->fetch_assoc();
        $custID = $row_cust['custID'];
    } else {
        // Build new structural profile row when customer is first discovered
        $ins_cust = "INSERT INTO customer (custName, custIC, custEmail, contactno, custLicense) 
                     VALUES ('$name', '$ic', '$email', '$phone', '$license_path')";
        if ($conn->query($ins_cust)) {
            $custID = $conn->insert_id;
        } else {
            die("Error creating customer file reference: " . $conn->error);
        }
    }
    
    // Append active transaction dataset entries into the master booking tables
    $ins_book = "INSERT INTO booking (custID, carID, pickupDate, returnDate, pickupTime, returnTime, pickupLocation, addressDetails, bookStatus) 
                 VALUES ($custID, $carID, '$start_date', '$end_date', '$pickup_time', '$delivery_time', '$pickup_area', '$address_details', 'Pending')";
                 
    if ($conn->query($ins_book)) {
        echo "<script>
                alert('✅ Done! All information inserted and form submitted successfully.');
                window.location.href = 'index.html';
              </script>";
        exit();
    } else {
        echo "<script>alert('❌ System Error: Unable to complete booking record registration -> " . $conn->real_escape_string($conn->error) . "');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Info Form - Single Page</title>
    
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

        /* NEW CAR SELECTION GRID CSS */
        .car-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .car-card-label {
            cursor: pointer;
            display: block;
        }

        .car-card-label input[type="radio"] {
            display: none; 
        }

        .car-card {
            background-color: #242424;
            border: 2px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .car-card:hover {
            border-color: rgba(123, 145, 249, 0.5);
            transform: translateY(-5px);
        }

        .car-card-label input[type="radio"]:checked + .car-card {
            border-color: #7b91f9;
            background-color: rgba(123, 145, 249, 0.1);
            box-shadow: 0 0 15px rgba(123, 145, 249, 0.2);
        }

        .car-image {
            width: 100%;
            height: 140px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 15px;
            background-color: #1a1a1a;
        }

        .car-brand {
            font-size: 12px;
            color: #aaaaaa;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .car-model {
            font-size: 18px;
            color: #ffffff;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .car-price {
            font-size: 15px;
            color: #36b37e;
            font-weight: 600;
            background-color: rgba(54, 179, 126, 0.1);
            padding: 5px 10px;
            border-radius: 20px;
            display: inline-block;
        }

        /* GRID SISTEM KUNCI */
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

        /* Gabungan Input Start & End Date */
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

        .form-group input {
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
            color: #777777;
            font-weight: 500;
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

        /* Upload Area */
        .upload-center-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            margin-bottom: 10px;
        }

        .upload-card {
            background-color: #242424;
            padding: 24px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            width: 100%;
            max-width: 550px;
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

        /* Navigasi Bawah */
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
            .form-grid-3, .form-grid-2 { grid-template-columns: 1fr; }
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
            <h2>Select Vehicle</h2>
            <p>Please choose your preferred car</p>
        </div>

        <div class="car-grid">
            <?php if ($available_cars && $available_cars->num_rows > 0): ?>
                <?php while($car = $available_cars->fetch_assoc()): ?>
                    <label class="car-card-label">
                        <input type="radio" name="carID" value="<?php echo $car['carID']; ?>" required>
                        <div class="car-card">
                            <img src="<?php echo !empty($car['carImage']) ? htmlspecialchars($car['carImage']) : 'imgweb/default-car.jpg'; ?>" alt="Car Image" class="car-image">
                            <div class="car-brand"><?php echo htmlspecialchars($car['carBrand']); ?></div>
                            <div class="car-model"><?php echo htmlspecialchars($car['carModel']); ?></div>
                            <div class="car-price">RM <?php echo number_format($car['carPrice'], 2); ?> / hour</div>
                        </div>
                    </label>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color: #ff5630;">Tiada kereta yang 'Available' pada masa ini.</p>
            <?php endif; ?>
        </div>

        <div class="section-divider"></div>
        <div class="header-section">
            <h2>Booking Info</h2>
            <p>Please enter all your booking information below</p>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label>Name *</label>
                <input type="text" id="main_name" name="name" placeholder="Your name">
            </div>
            <div class="form-group">
                <label>Identify Card Number *</label>
                <input type="text" id="id_card_number" name="id_card_number" placeholder="Identify card number">
            </div>
        </div>

        <div class="upload-center-wrapper">
            <div class="upload-card">
                <div class="upload-inner-box">
                    <input type="file" id="driver_license" name="driver_license" accept="image/*" style="display:none" onchange="updateUploadStatus(this, 'driver_preview', 'driver_text')">
                    <button type="button" class="blue-upload-btn" onclick="document.getElementById('driver_license').click()">Click to upload Driver's License Picture</button>
                    <div class="preview-box" id="driver_preview">
                        <span id="driver_text">No file chosen yet</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-divider"></div>

        <div class="form-grid-3">
            <div class="form-group">
                <label>Email *</label>
                <input type="text" id="main_email" name="email" placeholder="Enter email">
            </div>
            <div class="form-group">
                <label>Contact No *</label>
                <input type="text" id="contact_no" name="contact_no" placeholder="Enter contact number">
            </div>
            <div></div> </div>

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
                <label>Delivery Time *</label>
                <input type="time" id="delivery_time" name="delivery_time" onchange="calculateRentDays()">
                <span class="time-warning" id="time_warning_msg">⚠️ Tarikh tamat diselaraskan ke esok hari.</span>
            </div>
        </div>

        <div class="form-grid-3">
            <div class="form-group">
                <label>Total Rent Days</label>
                <input type="text" id="total_days" name="total_days" placeholder="Auto calculated" readonly>
            </div>
            <div class="form-group">
                <label>Enter Pickup Area *</label>
                <input type="text" id="pickup_area" name="pickup_area" placeholder="Enter Pickup Area">
            </div>
            <div></div> 
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
                <button type="submit" class="main-btn">Submit</button>
            </div>
        </div>

    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
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
    };

    function updateUploadStatus(input, previewId, textId) {
        const previewBox = document.getElementById(previewId);
        const statusText = document.getElementById(textId);
        if (input.files && input.files.length > 0) {
            previewBox.classList.add('uploaded');
            statusText.innerText = input.files[0].name;
        }
    }

    function clearForm() {
        if (confirm("Adakah anda pasti mahu mengosongkan semua input borang?")) {
            document.getElementById('bookingForm').reset();
            const pDriver = document.getElementById('driver_preview');
            if (pDriver) pDriver.classList.remove('uploaded');
            document.getElementById('driver_text').innerText = "No file chosen yet";
            if (startPicker) startPicker.clear();
            if (endPicker) endPicker.clear();
            document.getElementById('time_warning_msg').style.display = "none";
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
                totalDaysInput.value = (diff === 0 ? 1 : diff) + " Days";
            }
        }
    }

    function validateForm(event) {
        event.preventDefault();

        // Check if car is selected
        const selectedCar = document.querySelector('input[name="carID"]:checked');
        if (!selectedCar) {
            alert("⚠️ Please select a car first!");
            return false;
        }

        const nameRegex = /^[a-zA-Z\s']{3,}$/; 
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/; 
        const phoneRegex = /^01[0-9]{8,10}$/; 

        const name = document.getElementById('main_name').value.trim();
        const ic = document.getElementById('id_card_number').value.trim();
        const dl = document.getElementById('driver_license').files.length;
        const email = document.getElementById('main_email').value.trim();
        const phone = document.getElementById('contact_no').value.trim();
        const sDate = document.getElementById('start_date').value;
        const eDate = document.getElementById('end_date').value;
        const pTime = document.getElementById('pickup_time').value;
        const dTime = document.getElementById('delivery_time').value;
        const pArea = document.getElementById('pickup_area').value.trim();
        const address = document.getElementById('address_details').value.trim();

        if (!nameRegex.test(name)) {
            alert("⚠️ Please enter a valid name!");
            return false;
        }
        if (ic === "") {
            alert("⚠️ Please insert your identification number!");
            return false;
        }
        if (dl === 0) {
            alert("⚠️ Please upload your Driver's License!");
            return false;
        }
        if (!emailRegex.test(email)) {
            alert("⚠️ Please enter a valid email format!");
            return false;
        }
        if (!phoneRegex.test(phone)) {
            alert("⚠️ Please enter a valid phone number!");
            return false;
        }
        if (sDate === "" || eDate === "" || pTime === "" || dTime === "" || pArea === "" || address === "") {
            alert("⚠️ Please enter all required information (*)");
            return false;
        }

        // Programmatically bypasses default loop listener locks to dispatch structured standard elements
        document.getElementById('bookingForm').submit();
    }
    
    function goHome() { window.location.href = 'index.html'; }
</script>

</body>
</html>
<?php $conn->close(); ?>