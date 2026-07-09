<?php
// Start session management if needed
session_start();

// Set timezone to Malaysia
date_default_timezone_set('Asia/Kuala_Lumpur');

// Database connection configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "keretasewa_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// 1. Fetch dynamic counts for sidebar alerts and metric counters
$pendingBookings = $conn->query("SELECT COUNT(*) FROM booking WHERE bookStatus = 'Pending'")->fetch_row()[0];

// Synced query matching active trip filters ('Accepted' and 'Active')
$activeTrips = $conn->query("SELECT COUNT(*) FROM booking WHERE bookStatus IN ('Accepted', 'Active')")->fetch_row()[0];

// Synced query matching pending deletion criteria
$pendingDeletionResult = $conn->query("SELECT COUNT(*) FROM booking WHERE bookStatus = 'Pending Deletion'");
$pendingDeletion = $pendingDeletionResult ? $pendingDeletionResult->fetch_row()[0] : 0;

// Fleet calculation metric
$totalCars = $conn->query("SELECT COUNT(*) FROM car")->fetch_row()[0];

// 2. Fetch Dynamic Graph Data based on filter selection (weekly, monthly, yearly)
$view = isset($_GET['view']) ? $_GET['view'] : 'weekly';
$chartData = [];
$maxCount = 0;

if ($view === 'monthly') {
    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    foreach ($months as $idx => $mName) { $chartData[$mName] = 0; }
    
    $graphQuery = "SELECT MONTH(pickupDate) as m_num, COUNT(*) as cnt 
                   FROM booking 
                   WHERE pickupDate IS NOT NULL AND YEAR(pickupDate) = YEAR(CURDATE())
                   GROUP BY MONTH(pickupDate)";
    $graphResult = $conn->query($graphQuery);
    if ($graphResult) {
        while ($row = $graphResult->fetch_assoc()) {
            $mIdx = intval($row['m_num']) - 1;
            if (isset($months[$mIdx])) { $chartData[$months[$mIdx]] = intval($row['cnt']); }
        }
    }
} elseif ($view === 'yearly') {
    $currentYear = intval(date('Y'));
    for ($i = $currentYear - 4; $i <= $currentYear; $i++) { $chartData[$i] = 0; }
    
    $graphQuery = "SELECT YEAR(pickupDate) as y_num, COUNT(*) as cnt 
                   FROM booking 
                   WHERE pickupDate IS NOT NULL AND YEAR(pickupDate) >= ($currentYear - 4)
                   GROUP BY YEAR(pickupDate)";
    $graphResult = $conn->query($graphQuery);
    if ($graphResult) {
        while ($row = $graphResult->fetch_assoc()) {
            $yKey = intval($row['y_num']);
            if (isset($chartData[$yKey])) { $chartData[$yKey] = intval($row['cnt']); }
        }
    }
} else {
    $view = 'weekly';
    $weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    foreach ($weekdays as $day) { $chartData[$day] = 0; }
    
    $graphQuery = "SELECT WEEKDAY(pickupDate) as w_day, COUNT(*) as cnt 
                   FROM booking 
                   WHERE pickupDate IS NOT NULL 
                   GROUP BY WEEKDAY(pickupDate)";
    $graphResult = $conn->query($graphQuery);
    if ($graphResult) {
        while ($row = $graphResult->fetch_assoc()) {
            $wIdx = intval($row['w_day']);
            if (isset($weekdays[$wIdx])) { $chartData[$weekdays[$wIdx]] = intval($row['cnt']); }
        }
    }
}

foreach ($chartData as $val) { if ($val > $maxCount) $maxCount = $val; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Abe Nuar Kereta Sewa</title>
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
        .page-header h1 { font-size: 28px; color: #F3F4F6; margin-bottom: 5px; }
        
        .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 25px; margin-bottom: 40px; }
        .metric-card { background-color: #111827; border: 1px solid #1E293B; padding: 25px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); }
        .metric-title { color: #94A3B8; font-size: 13px; text-transform: uppercase; margin-bottom: 10px; }
        .metric-value { font-size: 32px; font-weight: 700; color: #F3F4F6; }
        
        .chart-section { background-color: #111827; border: 1px solid #1E293B; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); }
        .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .chart-filters { display: flex; gap: 10px; background-color: #0F172A; padding: 4px; border-radius: 30px; border: 1px solid #1E293B; }
        .chart-filter-btn { padding: 8px 20px; border-radius: 25px; border: none; background: transparent; color: #94A3B8; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; transition: all 0.2s ease; }
        .chart-filter-btn.active { background-color: #7E99FF; color: #0B0F19; }
        
        .bar-chart { display: flex; justify-content: space-between; align-items: flex-end; height: 260px; padding-top: 20px; border-bottom: 2px solid #1E293B; gap: 12px; }
        .bar-group { display: flex; flex-direction: column; align-items: center; flex: 1; height: 100%; justify-content: flex-end; position: relative; }
        .bar { width: 100%; max-width: 40px; background: #7E99FF; border-radius: 6px 6px 0 0; }
        .bar-label { margin-top: 12px; color: #94A3B8; font-size: 12px; }
    </style>
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="sidebar-header"><h2>Admin Panel</h2></div>
            <nav class="nav-menu">
                <a href="admin_dashboard.php" class="nav-item active">Dashboard</a>
                <a href="admin_tripschedule.php" class="nav-item">Schedule</a>
                <a href="admin_pendbook.php" class="nav-item">Pending Booking <?php if($pendingBookings > 0): ?><span class="badge"><?php echo $pendingBookings; ?></span><?php endif; ?></a>
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
                <h1>Dashboard Overview</h1>
            </div>

            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-title">Pending Booking</div>
                    <div class="metric-value"><?php echo $pendingBookings; ?></div>
                </div>
                <div class="metric-card">
                    <div class="metric-title">Pending Deletion</div>
                    <div class="metric-value"><?php echo $pendingDeletion; ?></div>
                </div>
                <div class="metric-card">
                    <div class="metric-title">Active Trips</div>
                    <div class="metric-value"><?php echo $activeTrips; ?></div>
                </div>
                <div class="metric-card">
                    <div class="metric-title">Fleet Vehicles</div>
                    <div class="metric-value"><?php echo $totalCars; ?></div>
                </div>
            </div>

            <div class="chart-section">
                <div class="chart-header">
                    <h3>Reservation Load</h3>
                    <div class="chart-filters">
                        <a href="admin_dashboard.php?view=weekly" class="chart-filter-btn <?php echo ($view === 'weekly') ? 'active' : ''; ?>">Weekly</a>
                        <a href="admin_dashboard.php?view=monthly" class="chart-filter-btn <?php echo ($view === 'monthly') ? 'active' : ''; ?>">Monthly</a>
                        <a href="admin_dashboard.php?view=yearly" class="chart-filter-btn <?php echo ($view === 'yearly') ? 'active' : ''; ?>">Yearly</a>
                    </div>
                </div>
                <div class="bar-chart">
                    <?php foreach ($chartData as $label => $countValue): 
                        $pct = ($maxCount > 0) ? (($countValue / $maxCount) * 100) : 0;
                    ?>
                    <div class="bar-group">
                        <div class="bar" style="height: <?php echo $pct; ?>%;"></div>
                        <span class="bar-label"><?php echo htmlspecialchars($label); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
<?php $conn->close(); ?>