<?php
// Set timezone to Malaysia
date_default_timezone_set('Asia/Kuala_Lumpur');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "keretasewa_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ** Handle Penalty Rate Updating via POST **
$penaltyFile = 'penalty_rate.txt';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_fine_rate'])) {
    $newRate = floatval($_POST['new_fine_rate']);
    file_put_contents($penaltyFile, $newRate);
    // Reload the page to clear POST state
    header("Location: admin_activetrip.php");
    exit();
}
$currentFineRate = file_exists($penaltyFile) ? floatval(file_get_contents($penaltyFile)) : 10.00;

// ** Automatically update status to 'Overdue' if the current time is past the scheduled return time **
$currentDateTimeStr = date('Y-m-d H:i:s');
$updateOverdueQuery = "UPDATE booking 
                       SET bookStatus = 'Overdue' 
                       WHERE bookStatus = 'Active' 
                       AND '$currentDateTimeStr' > CONCAT(returnDate, ' ', returnTime)";
$conn->query($updateOverdueQuery);

// Fetch dynamic counts for sidebar badges
$pendingBookings = $conn->query("SELECT COUNT(*) FROM booking WHERE bookStatus = 'Pending'")->fetch_row()[0];
$pendingDeletionResult = $conn->query("SELECT COUNT(*) FROM booking WHERE bookStatus = 'Pending Deletion'");
$pendingDeletion = $pendingDeletionResult ? $pendingDeletionResult->fetch_row()[0] : 0;

// Fetch active trips (including the newly added 'Overdue' status)
$query = "SELECT b.bookID, c.custName, car.carModel, car.carPlate, b.pickupDate, b.pickupTime, b.returnDate, b.returnTime, b.bookStatus 
          FROM booking b 
          JOIN customer c ON b.custID = c.custID 
          JOIN car ON b.carID = car.carID 
          WHERE b.bookStatus IN ('Accepted', 'Active', 'Overdue')";
$result = $conn->query($query);
$totalActive = $result ? $result->num_rows : 0;

// Capture current timestamp to check for overdue trips
$currentTimestamp = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Active Trips - Admin Dashboard</title>
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
        .page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; }
        .page-title h1 { font-size: 28px; color: #F3F4F6; margin-bottom: 5px; }
        .page-title p { color: #94A3B8; font-size: 15px; }
        
        /* Fine Controls */
        .fine-controls { background-color: #1E293B; border: 1px solid #334155; padding: 12px 20px; border-radius: 10px; display: flex; align-items: center; gap: 15px; }
        .fine-label { color: #94A3B8; font-size: 14px; font-weight: 600; text-transform: uppercase; }
        .fine-rate { color: #EF4444; font-size: 20px; font-weight: 700; }
        .btn-edit-fine { background-color: rgba(126, 153, 255, 0.1); color: #7E99FF; border: 1px solid rgba(126, 153, 255, 0.3); padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600; transition: all 0.2s; }
        .btn-edit-fine:hover { background-color: #7E99FF; color: #FFFFFF; }

        .filters { display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap; }
        .btn-filter { padding: 8px 16px; border-radius: 20px; border: 1px solid #334155; background: transparent; color: #94A3B8; cursor: pointer; font-weight: 600; transition: all 0.2s ease; }
        .btn-filter.active, .btn-filter:hover { background: #334155; color: #F3F4F6; }
        
        /* Highlight Overdue filter button when active */
        .btn-filter.overdue-filter.active { background: rgba(239, 68, 68, 0.2); color: #EF4444; border-color: #EF4444; }

        .table-container { background-color: #111827; border-radius: 12px; border: 1px solid #1E293B; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 16px 20px; background-color: #0F172A; color: #94A3B8; font-size: 12px; text-transform: uppercase; border-bottom: 1px solid #1E293B; text-align: left; }
        td { padding: 16px 20px; border-bottom: 1px solid #1E293B; color: #F3F4F6; font-size: 14px; }
        tr:hover { background-color: rgba(30, 41, 59, 0.4); }

        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        .status-active { background: rgba(16, 185, 129, 0.15); color: #10B981; }
        .status-accepted { background: rgba(59, 130, 246, 0.15); color: #3B82F6; }
        .status-overdue { background: rgba(239, 68, 68, 0.15); color: #EF4444; }
        
        .overdue-tag { display: inline-block; margin-top: 4px; padding: 2px 6px; background: rgba(239, 68, 68, 0.15); color: #EF4444; font-size: 10px; font-weight: 700; border-radius: 4px; letter-spacing: 0.5px; }
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
        <a href="admin_activetrip.php" class="nav-item active">Active Trips</a>
        <a href="admin_pasttrip.php" class="nav-item">Past Trips</a>
        <a href="admin_carmanage.php" class="nav-item">Car Management</a>
        <a href="admin_settings.php" class="nav-item">Settings</a>
      </nav>
      <a href="../auth/index.php" class="logout-btn">Log Out</a>
    </aside>

    <main class="main-content">
      <div class="page-header">
        <div class="page-title">
          <h1>Active Trips Overview</h1>
          <p>Monitor ongoing trips and manage fine rates.</p>
        </div>
        <div class="fine-controls">
          <span class="fine-label">Late Penalty Rate:</span>
          <span class="fine-rate" id="fineRateDisplay">RM <?php echo number_format($currentFineRate, 2); ?>/hr</span>
          <button class="btn-edit-fine" onclick="openFineSettingsModal()">Change Rate</button>
        </div>
      </div>

      <div class="filters">
        <button class="btn-filter active" onclick="filterTrips('All', this)">All (<?php echo $totalActive; ?>)</button>
        <button class="btn-filter overdue-filter" onclick="filterTrips('Overdue', this)">Overdue</button>
      </div>

      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>ID</th> <th>Customer</th> <th>Model & Plate</th>
              <th>Scheduled Pick-up</th> <th>Scheduled Return</th> <th>Current Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
              <?php while($row = $result->fetch_assoc()): 
                  // Check dynamic conditions for row styling
                  $returnTimestamp = strtotime($row['returnDate'] . ' ' . $row['returnTime']);
                  $isOverdue = ($currentTimestamp > $returnTimestamp || $row['bookStatus'] === 'Overdue') ? 'true' : 'false';
              ?>
                <tr class="trip-row" data-status="<?php echo htmlspecialchars($row['bookStatus']); ?>" data-overdue="<?php echo $isOverdue; ?>">
                  <td>BK-<?php echo $row['bookID']; ?></td>
                  <td><?php echo htmlspecialchars($row['custName']); ?></td>
                  <td><?php echo htmlspecialchars($row['carModel'] . ' (' . $row['carPlate'] . ')'); ?></td>
                  <td><?php echo date('d M Y, h:i A', strtotime($row['pickupDate'] . ' ' . $row['pickupTime'])); ?></td>
                  <td>
                      <?php echo date('d M Y, h:i A', $returnTimestamp); ?>
                      <?php if ($isOverdue === 'true'): ?>
                          <br><span class="overdue-tag">OVERDUE</span>
                      <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($row['bookStatus'] === 'Overdue'): ?>
                      <span class="status-badge status-overdue">OVERDUE</span>
                    <?php elseif ($row['bookStatus'] === 'Active'): ?>
                      <span class="status-badge status-active">ACTIVE</span>
                    <?php else: ?>
                      <span class="status-badge status-accepted">ACCEPTED</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="6" style="text-align:center;">No active trips at the moment.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>

  <script>
    let currentActiveFilter = 'All';

    function executeSearchFilter() {
      const rows = document.querySelectorAll('.trip-row');
      rows.forEach(row => {
        const status = row.getAttribute('data-status');
        const isOverdue = row.getAttribute('data-overdue');
        
        if (currentActiveFilter === 'All') {
            row.style.display = '';
        } else if (currentActiveFilter === 'Overdue') {
            // Only show rows that are flagged as overdue in data attributes or database status
            row.style.display = (isOverdue === 'true' || status === 'Overdue') ? '' : 'none';
        } else {
            // Show Active or Accepted
            row.style.display = (status === currentActiveFilter) ? '' : 'none';
        }
      });
    }

    function filterTrips(statusType, buttonElement) {
      const filterButtons = document.querySelectorAll('.btn-filter');
      filterButtons.forEach(btn => btn.classList.remove('active'));
      buttonElement.classList.add('active');

      currentActiveFilter = statusType;
      executeSearchFilter(); 
    }

    // Modal to change fine rate directly into the server via POST
    function openFineSettingsModal() {
      const currentRateText = "<?php echo number_format($currentFineRate, 2); ?>";
      const newFineInput = prompt("Enter new penalty rate per hour (RM):", currentRateText);
      
      if (newFineInput !== null && newFineInput.trim() !== "" && !isNaN(newFineInput)) {
        const formattedPrice = parseFloat(newFineInput).toFixed(2);
        
        // Dynamically submit the new value straight to PHP backend
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'new_fine_rate';
        input.value = formattedPrice;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
        
      } else if (newFineInput !== null) {
        alert("Please enter a valid numeric price value only.");
      }
    }
  </script>
</body>
</html>
<?php $conn->close(); ?>