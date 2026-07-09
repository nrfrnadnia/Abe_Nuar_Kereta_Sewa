<?php
// Establish connection to the rental car database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "keretasewa_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database connection failure: " . $conn->connect_error);
}

// Handle Approval/Rejection for Pending Deletions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $bookID = intval($_POST['bookID']);
    
    if ($_POST['action'] === 'approve') {
        $newStatus = 'Deletion Accepted';
    } else {
        // Rejection Logic: Inspect database state to determine if it reverts to 'Accepted' or 'Pending'
        $status_query = "SELECT bookStatus FROM booking WHERE bookID = $bookID LIMIT 1";
        $status_res = $conn->query($status_query);
        $currentStatus = 'Pending Deletion';
        if ($status_res && $status_res->num_rows > 0) {
            $status_row = $status_res->fetch_assoc();
            $currentStatus = $status_row['bookStatus'];
        }
        
        $newStatus = ($currentStatus === 'Accepted Deletion') ? 'Accepted' : 'Pending';
    }
    
    $update_sql = "UPDATE booking SET bookStatus = '$newStatus' WHERE bookID = $bookID";
    $conn->query($update_sql);
    
    // Refresh page to update the table
    header("Location: admin_penddelete.php");
    exit();
}

// Fetch dynamic counts for sidebar badges (Includes both standard and accepted deletion entries)
$pendingBookings = $conn->query("SELECT COUNT(*) FROM booking WHERE bookStatus = 'Pending'")->fetch_row()[0];
$pendingDeletionResult = $conn->query("SELECT COUNT(*) FROM booking WHERE bookStatus IN ('Pending Deletion', 'Accepted Deletion')");
$pendingDeletion = $pendingDeletionResult ? $pendingDeletionResult->fetch_row()[0] : 0;

// Query to fetch data for the table
$query = "SELECT b.bookID, c.custName, c.custIC, c.contactno, car.carPlate, car.carModel, 
                 b.pickupDate, b.pickupTime, b.returnDate, b.returnTime, b.bookStatus 
          FROM booking b 
          JOIN customer c ON b.custID = c.custID 
          JOIN car ON b.carID = car.carID 
          WHERE b.bookStatus IN ('Pending Deletion', 'Accepted Deletion')";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Deletion - Admin Dashboard</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #0B0F19; color: #F3F4F6; display: flex; min-height: 100vh; }
        
        .dashboard { display: grid; grid-template-columns: 260px 1fr; width: 100%; }
        
        /* Unified Sidebar */
        .sidebar { background-color: #111827; border-right: 1px solid #1E293B; padding: 30px 20px; display: flex; flex-direction: column; box-shadow: 2px 0 15px rgba(0, 0, 0, 0.3); z-index: 10; }
        .sidebar-header { margin-bottom: 40px; text-align: center; }
        .sidebar-header h2 { color: #7E99FF; font-size: 22px; text-transform: uppercase; letter-spacing: 1px; }
        .nav-menu { list-style: none; display: flex; flex-direction: column; gap: 15px; flex-grow: 1; }
        .nav-item { display: flex; justify-content: space-between; align-items: center; padding: 14px 18px; color: #94A3B8; border-radius: 8px; cursor: pointer; font-size: 15px; font-weight: 500; transition: all 0.2s ease; text-decoration: none; border: 1px solid transparent; }
        .nav-item:hover, .nav-item.active { background-color: #1E293B; color: #F3F4F6; border-color: #334155; }
        .nav-item.active { border-left: 4px solid #7E99FF; padding-left: 14px; }
        .badge { background-color: #EF4444; color: #FFFFFF; font-size: 12px; font-weight: 700; padding: 2px 8px; border-radius: 20px; min-width: 24px; text-align: center; }
        .logout-btn { margin-top: auto; padding: 14px 18px; background-color: #0F172A; color: #7E99FF; border: 1px solid #334155; border-radius: 50px; text-align: center; cursor: pointer; font-weight: 600; text-decoration: none; text-transform: uppercase; transition: all 0.2s ease; }
        .logout-btn:hover { background-color: #1E293B; color: #FFFFFF; border-color: #7E99FF; box-shadow: 0 0 10px rgba(126, 153, 255, 0.2); }

        /* Unified Main Content & Tables */
        .main-content { padding: 40px; overflow-y: auto; }
        .page-header { margin-bottom: 30px; }
        .page-header h1 { font-size: 28px; color: #F3F4F6; }
        .page-header p { color: #94A3B8; margin-top: 5px; }
        
        .table-container { background-color: #111827; border-radius: 12px; border: 1px solid #1E293B; overflow-x: auto; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2); }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 16px 20px; background-color: #0F172A; color: #94A3B8; font-size: 12px; text-transform: uppercase; border-bottom: 1px solid #1E293B; text-align: left; }
        td { padding: 16px 20px; border-bottom: 1px solid #1E293B; color: #F3F4F6; font-size: 14px; }
        tr:hover { background-color: rgba(30, 41, 59, 0.4); }

        /* Unique Action Buttons CSS */
        .action-btn { padding: 8px 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px; transition: 0.2s; margin-right: 5px; }
        .btn-approve { background: #10B981; color: white; }
        .btn-reject { background: #EF4444; color: white; }
        .btn-approve:hover { background: #059669; }
        .btn-reject:hover { background: #DC2626; }
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
                <a href="admin_penddelete.php" class="nav-item active">Pending Deletion <?php if($pendingDeletion > 0): ?><span class="badge"><?php echo $pendingDeletion; ?></span><?php endif; ?></a>
                <a href="admin_activetrip.php" class="nav-item">Active Trips</a>
                <a href="admin_pasttrip.php" class="nav-item">Past Trips</a>
                <a href="admin_carmanage.php" class="nav-item">Car Management</a>
                <a href="admin_settings.php" class="nav-item">Settings</a>
            </nav>
            <a href="../auth/index.php" class="logout-btn">Log Out</a>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1>Pending Deletion Requests</h1>
                <p>Review and confirm booking removal requests.</p>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th> <th>Customer</th> <th>IC</th> <th>Contact No</th> <th>Plate</th>
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
                                <td><?php echo htmlspecialchars($row['carPlate']); ?></td>
                                <td><?php echo htmlspecialchars($row['carModel']); ?></td>
                                <td><?php echo $row['pickupDate'] . ' ' . $row['pickupTime']; ?></td>
                                <td><?php echo $row['returnDate'] . ' ' . $row['returnTime']; ?></td>
                                <td><span style="color:#EF4444; font-weight:bold;">Pending Deletion</span></td>
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
                            <tr><td colspan="10" style="text-align:center;">No pending deletions found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
<?php $conn->close(); ?>