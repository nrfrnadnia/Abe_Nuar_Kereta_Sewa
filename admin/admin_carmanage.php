<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "keretasewa_db";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// ==========================================
// 1. HANDLE DELETION
// ==========================================
if (isset($_GET['delete_id'])) {
    $carID = $_GET['delete_id'];
    $imgQuery = "SELECT carImage FROM car WHERE carID = '$carID'";
    $imgResult = $conn->query($imgQuery);
    if ($imgResult && $imgResult->num_rows > 0) {
        $row = $imgResult->fetch_assoc();
        $imagePath = "uploads/" . $row['carImage'];
        if ($row['carImage'] != 'default.png' && !empty($row['carImage']) && file_exists($imagePath)) {
            unlink($imagePath); 
        }
    }
    $deleteSql = "DELETE FROM car WHERE carID = '$carID'";
    if ($conn->query($deleteSql) === TRUE) {
        echo "<script>alert('Car deleted successfully!'); window.location.href='admin_carmanage.php';</script>";
    } else {
        echo "<script>alert('Error deleting car: " . $conn->error . "'); window.location.href='admin_carmanage.php';</script>";
    }
    exit;
}

// ==========================================
// 2. HANDLE EDIT FORM SUBMISSION (POST)
// ==========================================
// Check if the entire post payload was blocked because the image file was too large
if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($_POST) && $_SERVER['CONTENT_LENGTH'] > 0) {
    echo "<script>alert('Upload failed: The image file size exceeds the server\'s total POST limit (post_max_size). Try a smaller image.'); window.location.href='admin_carmanage.php';</script>";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_car'])) {
    $carID = $_POST['car_id'];
    $brand = $_POST['brand'];
    $model = $_POST['model'];
    $plate = $_POST['plate'];
    $price = $_POST['price'];
    $availability = $_POST['availability'];
    
    $imageUpdateSql = ""; 

    // Check if a file was actually selected for upload
    if (isset($_FILES['carImage']) && $_FILES['carImage']['error'] != UPLOAD_ERR_NO_FILE) {
        // If the file selected has no system errors
        if ($_FILES['carImage']['error'] === UPLOAD_ERR_OK) {
            $targetDir = "uploads/"; 
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            
            // Add timestamp prefix to make filename unique and prevent browser caching issues
            $imageName = time() . "_" . basename($_FILES['carImage']['name']);
            $targetFilePath = $targetDir . $imageName;
            
            // Attempt to move file and handle potential directory permission failures
            if (move_uploaded_file($_FILES['carImage']['tmp_name'], $targetFilePath)) {
                $imageUpdateSql = ", carImage = '$imageName'";
                
                // Optional: Delete the old image from the folder to save disk space
                $oldImgQuery = "SELECT carImage FROM car WHERE carID = '$carID'";
                $oldImgResult = $conn->query($oldImgQuery);
                if ($oldImgResult && $oldImgResult->num_rows > 0) {
                    $oldRow = $oldImgResult->fetch_assoc();
                    $oldImagePath = "uploads/" . $oldRow['carImage'];
                    if ($oldRow['carImage'] != 'default.png' && !empty($oldRow['carImage']) && file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
            } else {
                echo "<script>alert('Failed to save the image. Please verify your \'uploads/\' directory has write permissions.'); window.location.href='admin_carmanage.php';</script>";
                exit;
            }
        } else {
            // Provide exact system reason why the file upload failed
            $errorMsg = "Unknown upload error.";
            switch ($_FILES['carImage']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $errorMsg = "The file exceeds the upload_max_filesize directive in php.ini.";
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $errorMsg = "The file exceeds the MAX_FILE_SIZE specified in the form.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errorMsg = "The file was only partially uploaded.";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $errorMsg = "Missing a temporary folder on your server.";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $errorMsg = "Failed to write file to disk.";
                    break;
            }
            echo "<script>alert('Image upload error: " . $errorMsg . "'); window.location.href='admin_carmanage.php';</script>";
            exit;
        }
    }

    $sql = "UPDATE car SET 
            carBrand = '$brand', carModel = '$model', carPlate = '$plate', 
            carPrice = '$price', carAvailability = '$availability'
            $imageUpdateSql WHERE carID = '$carID'";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Car updated successfully!'); window.location.href='admin_carmanage.php';</script>";
    } else {
        echo "<script>alert('Error updating car: " . $conn->error . "'); window.location.href='admin_carmanage.php';</script>";
    }
    exit;
}

// ==========================================
// 3. CHECK IF WE ARE IN "EDIT MODE"
// ==========================================
$isEditing = false;
$editData = null;
if (isset($_GET['edit_id'])) {
    $isEditing = true;
    $editId = $_GET['edit_id'];
    $result = $conn->query("SELECT * FROM car WHERE carID = '$editId'");
    if ($result->num_rows > 0) {
        $editData = $result->fetch_assoc();
    } else {
        echo "<script>alert('Car not found!'); window.location.href='admin_carmanage.php';</script>";
        exit;
    }
}

// ==========================================
// 4. FETCH DATA FOR TABLE (IF NOT EDITING)
// ==========================================
$pendingBookings = $conn->query("SELECT COUNT(*) FROM booking WHERE bookStatus = 'Pending'")->fetch_row()[0];
$pendingDeletionResult = $conn->query("SELECT COUNT(*) FROM booking WHERE bookStatus = 'Pending Deletion'");
$pendingDeletion = $pendingDeletionResult ? $pendingDeletionResult->fetch_row()[0] : 0;

$query = "SELECT * FROM car";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Car Management - Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #0B0F19; color: #F3F4F6; display: flex; min-height: 100vh; }
        .dashboard { display: grid; grid-template-columns: 260px 1fr; width: 100%; }
        
        /* Sidebar Styles */
        .sidebar { background-color: #111827; border-right: 1px solid #1E293B; padding: 30px 20px; display: flex; flex-direction: column; }
        .sidebar-header { margin-bottom: 40px; text-align: center; }
        .sidebar-header h2 { color: #7E99FF; font-size: 22px; text-transform: uppercase; }
        .nav-menu { list-style: none; display: flex; flex-direction: column; gap: 15px; flex-grow: 1; }
        .nav-item { display: flex; justify-content: space-between; align-items: center; padding: 14px 18px; color: #94A3B8; border-radius: 8px; cursor: pointer; font-size: 15px; font-weight: 500; text-decoration: none; border: 1px solid transparent; }
        .nav-item:hover, .nav-item.active { background-color: #1E293B; color: #F3F4F6; border-color: #334155; }
        .nav-item.active { border-left: 4px solid #7E99FF; padding-left: 14px; }
        .badge { background-color: #EF4444; color: #FFFFFF; font-size: 12px; font-weight: 700; padding: 2px 8px; border-radius: 20px; min-width: 24px; text-align: center; }
        .logout-btn { margin-top: auto; padding: 14px 18px; background-color: #0F172A; color: #7E99FF; border: 1px solid #334155; border-radius: 50px; text-align: center; text-decoration: none; font-weight: 600; text-transform: uppercase; }
        
        .main-content { padding: 40px; overflow-y: auto; }
        .page-header-row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; }
        .page-header h1 { font-size: 28px; color: #F3F4F6; }
        .btn-add { display: inline-flex; align-items: center; justify-content: center; padding: 10px 16px; background-color: #7E99FF; color: #0B0F19; border: none; border-radius: 8px; text-decoration: none; font-weight: 600; transition: all 0.2s ease; }
        .btn-add:hover { background-color: #5f7ff5; transform: translateY(-1px); }

        /* Table Styles */
        .table-container { background-color: #111827; border-radius: 12px; border: 1px solid #1E293B; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 16px 20px; background-color: #0F172A; color: #94A3B8; font-size: 12px; text-transform: uppercase; border-bottom: 1px solid #1E293B; text-align: left; }
        td { padding: 16px 20px; border-bottom: 1px solid #1E293B; color: #F3F4F6; font-size: 14px; vertical-align: middle; }
        tr:hover { background-color: rgba(30, 41, 59, 0.4); }
        .action-btn { padding: 6px 12px; background: transparent; border: 1px solid #475569; color: #94A3B8; border-radius: 6px; cursor: pointer; margin-right: 5px; transition: 0.2s; text-decoration: none; display: inline-block; }
        .action-btn:hover { background: #334155; color: white; }

        /* Form Styles */
        .form-container { background-color: #111827; padding: 40px; border-radius: 12px; border: 1px solid #1E293B; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; }
        .form-group { display: flex; flex-direction: column; gap: 10px; }
        .form-group label { font-size: 16px; font-weight: 600; color: #E2E8F0; }
        .form-group input, .form-group select { width: 100%; padding: 16px; background-color: #334155; border: 1px solid #475569; border-radius: 8px; color: #FFFFFF; font-size: 15px; outline: none; }
        .form-group input[type="file"] { padding: 12px; color: #94A3B8; cursor: pointer; }
        .form-group input:focus, .form-group select:focus { border-color: #7E99FF; background-color: #3B4B6E; }
        .select-wrapper { position: relative; }
        .select-wrapper::after { content: '▼'; font-size: 12px; color: #7E99FF; position: absolute; right: 16px; top: 50%; transform: translateY(-50%); pointer-events: none; }
        .form-group select { appearance: none; cursor: pointer; padding-right: 40px; }
        .button-group { display: flex; justify-content: flex-end; gap: 15px; }
        .btn-save { padding: 12px 30px; background-color: #7E99FF; color: #0F172A; border-radius: 50px; font-weight: 700; border: none; cursor: pointer; }
        .btn-cancel { padding: 12px 30px; background-color: transparent; color: #94A3B8; border: 1px solid #334155; border-radius: 6px; text-decoration: none; }
    </style>
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="sidebar-header"><h2>Admin Panel</h2></div>
            <nav class="nav-menu">
                <a href="admin_dashboard.php" class="nav-item">Dashboard</a>
                <a href="admin_tripschedule.php" class="nav-item">Schedule</a>
                <a href="admin_pendbook.php" class="nav-item">Pending Booking <?php if($pendingBookings > 0): ?><span class="badge"><?php echo $pendingBookings; ?></span><?php endif; ?></a>
                <a href="admin_penddelete.php" class="nav-item">Pending Deletion <?php if($pendingDeletion > 0): ?><span class="badge"><?php echo $pendingDeletion; ?></span><?php endif; ?></a>
                <a href="admin_activetrip.php" class="nav-item">Active Trips</a>
                <a href="admin_pasttrip.php" class="nav-item">Past Trips</a>
                <a href="admin_carmanage.php" class="nav-item active">Car Management</a>
                <a href="admin_settings.php" class="nav-item">Settings</a>
            </nav>
            <a href="../auth/index.php" class="logout-btn">Log Out</a>
        </aside>

        <main class="main-content">
            
            <?php if ($isEditing): ?>
                <div class="page-header-row">
                    <div class="page-header">
                        <h1>Edit Vehicle</h1>
                        <p style="color: #94A3B8; margin-top: 5px;">Updating details for <?php echo htmlspecialchars($editData['carPlate']); ?></p>
                    </div>
                </div>

                <div class="form-container">
                    <form method="POST" action="admin_carmanage.php" enctype="multipart/form-data">
                        <input type="hidden" name="car_id" value="<?php echo $editData['carID']; ?>">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Brand</label>
                                <input type="text" name="brand" required value="<?php echo htmlspecialchars($editData['carBrand']); ?>">
                            </div>
                            <div class="form-group">
                                <label>Model</label>
                                <input type="text" name="model" required value="<?php echo htmlspecialchars($editData['carModel']); ?>">
                            </div>
                            <div class="form-group">
                                <label>No. Plate</label>
                                <input type="text" name="plate" required value="<?php echo htmlspecialchars($editData['carPlate']); ?>">
                            </div>
                            <div class="form-group">
                                <label>Price per hour</label>
                                <input type="number" name="price" step="0.01" required value="<?php echo htmlspecialchars($editData['carPrice']); ?>">
                            </div>
                            <div class="form-group">
                                <label>Availability</label>
                                <div class="select-wrapper">
                                    <select name="availability">
                                        <option value="Available" <?php echo ($editData['carAvailability'] == 'Available') ? 'selected' : ''; ?>>Available</option>
                                        <option value="Unavailable" <?php echo ($editData['carAvailability'] == 'Unavailable') ? 'selected' : ''; ?>>Unavailable</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Car Image (Leave blank to keep current)</label>
                                <input type="file" name="carImage" accept="image/*">
                                <div style="margin-top: 5px; font-size: 13px; color: #94A3B8;">
                                    Current: <img src="uploads/<?php echo htmlspecialchars($editData['carImage']); ?>" alt="Car" style="height: 30px; vertical-align: middle; border-radius: 4px; margin-left: 10px;">
                                </div>
                            </div>
                        </div>

                        <div class="button-group">
                            <a href="admin_carmanage.php" class="btn-cancel">Cancel</a>
                            <button type="submit" name="update_car" class="btn-save">Save Changes</button>
                        </div>
                    </form>
                </div>

            <?php else: ?>
                <div class="page-header-row">
                    <div class="page-header">
                        <h1>Car Management</h1>
                        <p style="color: #94A3B8; margin-top: 5px;">Manage fleet pricing, details, and availability.</p>
                    </div>
                    <a href="admin_newcar.php" class="btn-add">+ Add New Vehicle</a>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Image</th> <th>Brand</th> <th>Model</th> <th>Plate</th> <th>Price/Hour</th> <th>Status</th> <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><img src="uploads/<?php echo htmlspecialchars($row['carImage']); ?>" alt="Car" style="width: 80px; height: 50px; object-fit: cover; border-radius: 6px; border: 1px solid #334155;"></td>
                                    <td><?php echo htmlspecialchars($row['carBrand']); ?></td>
                                    <td><?php echo htmlspecialchars($row['carModel']); ?></td>
                                    <td><?php echo htmlspecialchars($row['carPlate']); ?></td>
                                    <td>RM <?php echo number_format($row['carPrice'], 2); ?></td>
                                    <td><span style="color: <?php echo $row['carAvailability'] == 'Available' ? '#10B981' : '#EF4444'; ?>; font-weight: 500;"><?php echo htmlspecialchars($row['carAvailability']); ?></span></td>
                                    <td>
                                        <a href="admin_carmanage.php?edit_id=<?php echo $row['carID']; ?>" class="action-btn">Edit</a>
                                        <button class="action-btn" style="border-color: #EF4444; color: #EF4444;" onclick="if(confirm('Are you sure you want to delete this car?')) { window.location.href='admin_carmanage.php?delete_id=<?php echo $row['carID']; ?>'; }">Delete</button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="7" style="text-align:center;">No vehicles found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </main>
    </div>
</body>
</html>
<?php $conn->close(); ?>