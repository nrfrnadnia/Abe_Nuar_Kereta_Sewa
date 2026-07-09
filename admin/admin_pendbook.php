<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "keretasewa_db";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) die("Database connection failure: " . $conn->connect_error);

// Handle Approval/Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $bookID = intval($_POST['bookID']);
    $newStatus = ($_POST['action'] === 'approve') ? 'Accepted' : 'Rejected';
    
    $update_sql = "UPDATE booking SET bookStatus = '$newStatus' WHERE bookID = $bookID";
    $conn->query($update_sql);
    header("Location: admin_pendbook.php");
    exit();
}

// Dynamic Badges
$pendingBookings = $conn->query("SELECT COUNT(*) FROM booking WHERE bookStatus = 'Pending'")->fetch_row()[0];
$pendingDeletion = $conn->query("SELECT COUNT(*) FROM booking WHERE bookStatus = 'Pending Deletion'")->fetch_row()[0];

// Updated Query: Included custLicense and custStudentCard to view documents
$query = "SELECT b.bookID, c.custName, c.custIC, c.contactno, c.custLicense, c.custStudentCard, car.carPlate, car.carModel, 
                 b.pickupDate, b.pickupTime, b.returnDate, b.returnTime, b.bookStatus 
          FROM booking b 
          JOIN customer c ON b.custID = c.custID 
          JOIN car ON b.carID = car.carID 
          WHERE b.bookStatus = 'Pending'";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pending Booking - Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #0B0F19; color: #F3F4F6; display: flex; min-height: 100vh; }
        .dashboard { display: grid; grid-template-columns: 260px 1fr; width: 100%; }
        .sidebar { background-color: #111827; border-right: 1px solid #1E293B; padding: 30px 20px; display: flex; flex-direction: column; box-shadow: 2px 0 15px rgba(0, 0, 0, 0.3); z-index: 10; }
        .sidebar-header { margin-bottom: 40px; text-align: center; }
        .sidebar-header h2 { color: #7E99FF; font-size: 22px; text-transform: uppercase; }
        .nav-menu { list-style: none; display: flex; flex-direction: column; gap: 15px; flex-grow: 1; }
        .nav-item { display: flex; justify-content: space-between; align-items: center; padding: 14px 18px; color: #94A3B8; border-radius: 8px; cursor: pointer; font-size: 15px; font-weight: 500; text-decoration: none; border: 1px solid transparent; }
        .nav-item:hover, .nav-item.active { background-color: #1E293B; color: #F3F4F6; border-color: #334155; }
        .nav-item.active { border-left: 4px solid #7E99FF; padding-left: 14px; }
        .badge { background-color: #EF4444; color: #FFFFFF; font-size: 12px; font-weight: 700; padding: 2px 8px; border-radius: 20px; min-width: 24px; text-align: center; }
        .logout-btn { margin-top: auto; padding: 14px 18px; background-color: #0F172A; color: #7E99FF; border: 1px solid #334155; border-radius: 50px; text-align: center; cursor: pointer; font-weight: 600; text-decoration: none; text-transform: uppercase; }
        
        .main-content { padding: 40px; overflow-y: auto; }
        .page-header { margin-bottom: 30px; }
        .page-header h1 { font-size: 28px; color: #F3F4F6; }
        .table-container { background-color: #111827; border-radius: 12px; border: 1px solid #1E293B; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 16px 20px; background-color: #0F172A; color: #94A3B8; font-size: 12px; text-transform: uppercase; border-bottom: 1px solid #1E293B; text-align: left; }
        td { padding: 16px 20px; border-bottom: 1px solid #1E293B; color: #F3F4F6; font-size: 14px; vertical-align: middle; }
        tr:hover { background-color: rgba(30, 41, 59, 0.4); }

        .action-btn { padding: 8px 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px; transition: 0.2s; margin-right: 5px; }
        .btn-approve { background: #10B981; color: white; }
        .btn-reject { background: #EF4444; color: white; }
        .btn-approve:hover { background: #059669; }
        .btn-reject:hover { background: #DC2626; }

        /* Interactive Thumbnail Gallery Styles */
        .doc-preview-container { display: flex; gap: 6px; }
        .thumb-img { width: 50px; height: 35px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 1px solid #334155; transition: transform 0.2s, border-color 0.2s; }
        .thumb-img:hover { transform: scale(1.15); border-color: #7E99FF; }

        /* Fullscreen Lightbox Modal Style */
        .img-modal { display: none; position: fixed; z-index: 2000; padding-top: 60px; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(11, 15, 25, 0.95); }
        .img-modal-content { margin: auto; display: block; max-width: 85%; max-height: 75vh; border-radius: 8px; border: 2px solid #334155; box-shadow: 0 4px 25px rgba(0, 0, 0, 0.6); }
        .img-modal-caption { margin: auto; display: block; width: 80%; text-align: center; color: #94A3B8; padding: 15px 0; font-size: 15px; font-weight: 500; }
        .close-modal { position: absolute; top: 20px; right: 35px; color: #94A3B8; font-size: 40px; font-weight: bold; transition: 0.3s; cursor: pointer; }
        .close-modal:hover { color: #F3F4F6; }
    </style>
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="sidebar-header"><h2>Admin Panel</h2></div>
            <nav class="nav-menu">
                <a href="admin_dashboard.php" class="nav-item">Dashboard</a>
                <a href="admin_tripschedule.php" class="nav-item">Schedule</a>
                <a href="admin_pendbook.php" class="nav-item active">Pending Booking <?php if($pendingBookings > 0): ?><span class="badge"><?php echo $pendingBookings; ?></span><?php endif; ?></a>
                <a href="admin_penddelete.php" class="nav-item">Pending Deletion <?php if($pendingDeletion > 0): ?><span class="badge"><?php echo $pendingDeletion; ?></span><?php endif; ?></a>
                <a href="admin_activetrip.php" class="nav-item">Active Trips</a>
                <a href="admin_pasttrip.php" class="nav-item">Past Trips</a>
                <a href="admin_carmanage.php" class="nav-item">Car Management</a>
                <a href="admin_settings.php" class="nav-item">Settings</a>
            </nav>
            <a href="../auth/index.php" class="logout-btn">Log Out</a>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1>Pending Bookings</h1>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th> <th>Customer</th> <th>IC</th> <th>Contact No</th> <th>Documents</th> <th>Plate</th>
                            <th>Model</th> <th>Pick-up</th> <th>Return</th> <th>Status</th> <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>BK-<?php echo $row['bookID']; ?></td>
                                <td><?php echo htmlspecialchars($row['custName']); ?></td>
                                <td><?php echo htmlspecialchars($row['custIC']); ?></td>
                                <td><?php echo htmlspecialchars($row['contactno']); ?></td>
                                <td>
                                    <div class="doc-preview-container">
                                        <?php if (!empty($row['custLicense'])): ?>
                                            <img src="../<?php echo htmlspecialchars($row['custLicense']); ?>" class="thumb-img" alt="Driver's License - <?php echo htmlspecialchars($row['custName']); ?>" onerror="this.src='../customer/<?php echo htmlspecialchars($row['custLicense']); ?>'; this.onerror=function(){this.style.display='none';}">
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($row['custStudentCard']) && $row['custStudentCard'] !== 'imgweb/default-studentcard.jpg'): ?>
                                            <img src="../<?php echo htmlspecialchars($row['custStudentCard']); ?>" class="thumb-img" alt="Student Card - <?php echo htmlspecialchars($row['custName']); ?>" onerror="this.src='../customer/<?php echo htmlspecialchars($row['custStudentCard']); ?>'; this.onerror=function(){this.style.display='none';}">
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($row['carPlate']); ?></td>
                                <td><?php echo htmlspecialchars($row['carModel']); ?></td>
                                <td><?php echo $row['pickupDate'] . ' ' . $row['pickupTime']; ?></td>
                                <td><?php echo $row['returnDate'] . ' ' . $row['returnTime']; ?></td>
                                <td><span style="color:#F59E0B; font-weight:bold;"><?php echo htmlspecialchars($row['bookStatus']); ?></span></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="bookID" value="<?php echo $row['bookID']; ?>">
                                        <button type="submit" name="action" value="approve" class="action-btn btn-approve">Approve</button>
                                        <button type="submit" name="action" value="reject" class="action-btn btn-reject">Reject</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="11" style="text-align:center;">No pending bookings found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <div id="imageModal" class="img-modal">
        <span class="close-modal">&times;</span>
        <img class="img-modal-content" id="modalImg">
        <div id="modalCaption" class="img-modal-caption"></div>
    </div>

    <script>
        const modal = document.getElementById("imageModal");
        const modalImg = document.getElementById("modalImg");
        const captionText = document.getElementById("modalCaption");

        // Open Lightbox view on clicking image thumbnail
        document.querySelectorAll('.thumb-img').forEach(img => {
            img.addEventListener('click', function() {
                modal.style.display = "block";
                modalImg.src = this.src;
                captionText.innerHTML = this.alt;
            });
        });

        // Close functions
        document.querySelector('.close-modal').addEventListener('click', function() {
            modal.style.display = "none";
        });

        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = "none";
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>