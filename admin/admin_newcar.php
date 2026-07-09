<?php
// Establish database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "keretasewa_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $brand = $_POST['brand'];
    $model = $_POST['model'];
    $plate = $_POST['plate'];
    $price = $_POST['price'];
    $availability = $_POST['availability'];
    
    // Default image name if nothing is uploaded
    $imageName = 'default.png';

    // Handle the actual physical image file upload
    if (isset($_FILES['carImage']) && $_FILES['carImage']['error'] == 0) {
        $targetDir = "uploads/"; 
        
        // Create the 'uploads' directory automatically if it doesn't exist
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        // Clean the file name to avoid path traversal issues
        $imageName = basename($_FILES['carImage']['name']);
        $targetFilePath = $targetDir . $imageName;
        
        // Move the file from the temporary directory to your project's uploads folder
        move_uploaded_file($_FILES['carImage']['tmp_name'], $targetFilePath);
    }

    // FIXED: Column names matched directly to your SQL schema (carPlate, carBrand, carModel, carPrice, carAvailability, carImage)
    $sql = "INSERT INTO car (carPlate, carBrand, carModel, carPrice, carAvailability, carImage) 
            VALUES ('$plate', '$brand', '$model', '$price', '$availability', '$imageName')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('New car added successfully!'); window.location.href='admin_carmanage.php';</script>";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adding New Car</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #0B0F19; /* Deep Black/Midnight Blue */
            color: #F3F4F6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 900px;
            background-color: #111827; /* Dark Tech Black */
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5), 0 0 15px rgba(126, 153, 255, 0.1);
            border: 1px solid #1E293B;
            position: relative;
            min-height: 550px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .header {
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #7E99FF; /* Light Blue/Periwinkle Accent */
        }

        .header p {
            font-size: 14px;
            color: #94A3B8;
            margin-top: 4px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        @media (max-width: 600px) {
            .form-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .form-group label {
            font-size: 16px;
            font-weight: 600;
            color: #E2E8F0;
        }

        /* Lighter, high-contrast inputs for maximum legibility */
        .form-group input, .form-group select {
            width: 100%;
            padding: 16px;
            background-color: #334155; /* Considerably lighter slate gray for clarity */
            border: 1px solid #475569;
            border-radius: 8px;
            color: #FFFFFF;
            font-size: 15px;
            outline: none;
            transition: all 0.3s ease;
        }

        /* Specific styling for file inputs */
        .form-group input[type="file"] {
            padding: 12px;
            color: #94A3B8;
            cursor: pointer;
        }

        /* Hides native browser spinner buttons for number input */
        .form-group input[type="number"]::-webkit-outer-spin-button,
        .form-group input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .form-group input[type="number"] {
            appearance: textfield;
            -moz-appearance: textfield;
        }

        .form-group input::placeholder {
            color: #94A3B8; /* Lighter placeholder for readability */
        }

        /* Focus states with the light blue theme */
        .form-group input:focus, .form-group select:focus {
            border-color: #7E99FF;
            box-shadow: 0 0 8px rgba(126, 153, 255, 0.4);
            background-color: #3B4B6E; /* High-visibility focused background */
        }

        /* Styling the dropdown arrow */
        .select-wrapper {
            position: relative;
        }

        .select-wrapper::after {
            content: '▼';
            font-size: 12px;
            color: #7E99FF;
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
        }

        .form-group select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            cursor: pointer;
            padding-right: 40px;
        }

        /* Options layout fix for darker themes */
        .form-group select option {
            background-color: #1E293B;
            color: #FFFFFF;
        }

        /* Button Section */
        .button-group {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: auto;
        }

        .btn {
            padding: 12px 30px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
        }

        .btn-add {
            background-color: #7E99FF; 
            color: #0F172A; /* Dark text for contrast against light blue */
            border-radius: 50px; /* Pillow shape matching image design */
            font-weight: 700;
        }

        .btn-add:hover {
            background-color: #6582F4;
            box-shadow: 0 0 15px rgba(126, 153, 255, 0.6);
        }

        .btn-secondary {
            background-color: #0F172A;
            color: #94A3B8;
            border: 1px solid #334155;
        }

        .btn-secondary:hover {
            background-color: #1E293B;
            color: #F8FAFC;
            border-color: #475569;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <h1>Adding New Car</h1>
            <p>Please enter the car's info</p>
        </div>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-group">
                    <label for="brand">Brand</label>
                    <input type="text" id="brand" name="brand" required placeholder="Car's Brand">
                </div>

                <div class="form-group">
                    <label for="model">Model</label>
                    <input type="text" id="model" name="model" required placeholder="Car's Model">
                </div>

                <div class="form-group">
                    <label for="plate">No. Plate</label>
                    <input type="text" id="plate" name="plate" required placeholder="Car's plate number">
                </div>

                <div class="form-group">
                    <label for="price">Price per hour</label>
                    <input 
                        type="number" 
                        id="price" 
                        name="price"
                        min="0"
                        step="0.01"
                        required
                        placeholder="Car's price per hour (e.g. 25)"
                        oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1');"
                    >
                </div>

                <div class="form-group">
                    <label for="availability">Availability</label>
                    <div class="select-wrapper">
                        <select id="availability" name="availability">
                            <option value="Available">Available</option>
                            <option value="Unavailable">Unavailable</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="carImage">Car Image</label>
                    <input type="file" id="carImage" name="carImage" accept="image/*" required>
                </div>
            </div>

            <div class="button-group">
                <button type="submit" class="btn btn-add">Add</button>
                <a href="admin_dashboard.php" class="btn btn-secondary" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">Home</a>
                <a href="admin_carmanage.php" class="btn btn-secondary" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">Back</a>
            </div>
        </form>
    </div>

</body>
</html>
<?php $conn->close(); ?>