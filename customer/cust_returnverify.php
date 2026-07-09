<?php
// Start a session to safely pass data to the return details page
session_start();

// Database Configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "keretasewa_db";

// Establish Database Connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check if connection succeeded
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize error state
$error_msg = "";

// Process the form submission when an identification lookup occurs
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ic_input'])) {
    $ic_search = $conn->real_escape_string(trim($_POST['ic_input']));
    
    if (strlen($ic_search) < 4) {
        $error_msg = "*Please enter a valid identification format (at least 4 characters).";
    } else {
        // Query database to fetch any active or pending bookings for this customer
        $sql = "SELECT b.bookID, b.bookStatus, b.pickupDate, b.returnDate, c.custName, c.custIC, car.carBrand, car.carModel, car.carPlate 
                FROM booking b
                JOIN customer c ON b.custID = c.custID
                JOIN car car ON b.carID = car.carID
                WHERE (c.custIC = '$ic_search' OR c.custEmail = '$ic_search' OR c.contactno = '$ic_search')
                AND b.bookStatus NOT IN ('Returned', 'Completed', 'Cancelled', 'Deleted', 'Pending Deletion', 'Deletion Accepted')
                ORDER BY b.bookID DESC LIMIT 1";
                
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $booking_details = $result->fetch_assoc();
            
            // Check if the booking is still pending approval
            if ($booking_details['bookStatus'] === 'Pending') {
                $error_msg = "*Your booking is still pending approval. You cannot return the car yet.";
            } else {
                // Store active values inside session globals for use in cust_return.php
                $_SESSION['active_bookID'] = $booking_details['bookID'];
                $_SESSION['active_custName'] = $booking_details['custName'];
                $_SESSION['active_carPlate'] = $booking_details['carPlate'];
                $_SESSION['active_carModel'] = $booking_details['carBrand'] . ' ' . $booking_details['carModel'];
                
                // Redirect directly to the return processing page without any popups
                header("Location: cust_return.php");
                exit();
            }
        } else {
            $error_msg = "*No active booking found for the provided identification.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Rental Return System</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        body {
            background-color: #0c0f17; 
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* --- LIGHT BLUE BLURRY GRAFFITI DECORATIONS --- */
        body::before, body::after {
            content: '';
            position: absolute;
            width: 350px;
            height: 350px;
            border-radius: 50%;
            background: linear-gradient(45deg, #7c9eff, #a5bfff);
            filter: blur(120px);
            opacity: 0.25;
            z-index: 0;
            pointer-events: none;
        }

        body::before {
            top: -10%;
            left: -10%;
        }

        body::after {
            bottom: -10%;
            right: -10%;
            background: linear-gradient(45deg, #4f73d9, #7c9eff);
        }

        /* Base Container System */
        .auth-container {
            background-color: #151b2b;
            width: 100%;
            max-width: 480px;
            border-radius: 24px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05);
            padding: 45px 35px;
            text-align: center;
            position: relative;
            z-index: 10;
            backdrop-filter: blur(10px);
        }

        /* Branding Top Edge Geometry */
        .auth-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #7c9eff, #a5bfff, #7c9eff);
            background-size: 200% auto;
            animation: shine 3s linear infinite;
        }

        @keyframes shine {
            to { background-position: 200% center; }
        }

        /* Typography Matrix */
        .title-matrix {
            margin-bottom: 40px;
        }

        .title-matrix h2 {
            font-size: 28px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.5px;
            margin-bottom: 12px;
        }

        .title-matrix p {
            font-size: 14px;
            color: #8892b0;
            line-height: 1.6;
        }

        /* Input Controls Setup */
        .input-group {
            margin-bottom: 30px;
            text-align: left;
        }

        .input-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #7c9eff;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .input-group input {
            width: 100%;
            background-color: #0c0f17;
            border: 2px solid #232d45;
            padding: 16px 20px;
            border-radius: 12px;
            color: #ffffff;
            font-size: 16px;
            outline: none;
            transition: all 0.3s ease;
        }

        .input-group input::placeholder {
            color: #4a5568;
            font-weight: 500;
        }

        .input-group input:focus {
            border-color: #7c9eff;
            box-shadow: 0 0 0 4px rgba(124, 158, 255, 0.15);
        }

        /* Validation Feedback State */
        .input-error {
            border-color: #ef4444 !important;
            background-color: rgba(239, 68, 68, 0.05) !important;
        }

        .error-message {
            color: #ef4444;
            font-size: 12px;
            font-weight: 500;
            margin-top: 10px;
            display: <?php echo !empty($error_msg) ? 'block' : 'none'; ?>;
            text-align: left;
        }

        /* Core Call To Action Button */
        .btn-primary {
            width: 100%;
            background: linear-gradient(135deg, #7c9eff 0%, #5b83f0 100%);
            color: #ffffff;
            border: none;
            padding: 16px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(91, 131, 240, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(91, 131, 240, 0.5);
            background: linear-gradient(135deg, #8ba9ff 0%, #698ef5 100%);
        }

        .btn-primary:active {
            transform: translateY(1px);
        }

        /* Navigational Fallback */
        .back-link {
            display: inline-block;
            margin-top: 25px;
            color: #8892b0;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: #ffffff;
        }

        /* Interactive Shake Effect on Error */
        @keyframes vibrate {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-4px); }
            40% { transform: translateX(4px); }
            60% { transform: translateX(-4px); }
            80% { transform: translateX(4px); }
        }

        .vibrate {
            animation: vibrate 0.4s ease-in-out;
        }
    </style>
</head>
<body>

    <div class="auth-container">
        
        <div class="step-section active" id="step-1">
            <div class="title-matrix">
                <h2>Return Verification</h2>
                <p>Please enter your IC number to proceed with your vehicle return.</p>
            </div>

            <form id="verifyForm" method="POST" action="" onsubmit="return validateInput()">
                <div class="input-group">
                    <label for="ic_input">Identification Number</label>
                    <input type="text" id="ic-input" name="ic_input" 
                           placeholder="e.g., 010203045566" 
                           class="<?php echo !empty($error_msg) ? 'input-error vibrate' : ''; ?>"
                           oninput="clearError()" 
                           autocomplete="off">
                    <div class="error-message" id="ic-error"><?php echo $error_msg; ?></div>
                </div>

                <button type="submit" class="btn-primary">Verify Identification</button>
            </form>
            
            <a href="../auth/index.php" class="back-link">← Cancel and return to home</a>
        </div>

    </div>

    <script>
        // Triggered exclusively before the form is submitted to the server for initial blank string protections
        function validateInput() {
            const icInput = document.getElementById('ic-input');
            const errorMsg = document.getElementById('ic-error');
            const trimmedVal = icInput.value.trim();

            if (trimmedVal.length < 4) {
                icInput.classList.add('input-error');
                errorMsg.innerText = "*Please enter at least 4 characters.";
                errorMsg.style.display = "block";
                
                // Retrigger vibration effect
                icInput.classList.remove('vibrate');
                void icInput.offsetWidth; // force reflow
                icInput.classList.add('vibrate');
                
                return false;
            }
            return true;
        }

        // Clear error styling when the user starts typing
        function clearError() {
            const icInput = document.getElementById('ic-input');
            const errorMsg = document.getElementById('ic-error');
            
            if (icInput.value.trim() !== "") {
                icInput.classList.remove('input-error');
                icInput.classList.remove('vibrate');
                errorMsg.style.display = "none";
            }
        }
    </script>

</body>
</html>
<?php 
// Close the database connection
if (isset($conn) && $conn) {
    $conn->close(); 
}
?>