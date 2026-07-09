<?php
// Establish connection to the rental car database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "keretasewa_db";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check if database connection succeeds
if ($conn->connect_error) {
    die("Database connection failure: " . $conn->connect_error);
}

// 1. Fetch live dynamic sidebar counts from corresponding system tables
$pendingBookingCount = 0;
$pendingDeleteCount = 0;

$countBQuery = "SELECT COUNT(*) as total FROM booking WHERE bookStatus = 'Pending'";
$countBResult = $conn->query($countBQuery);
if ($countBResult) {
    $bRow = $countBResult->fetch_assoc();
    $pendingBookingCount = $bRow['total'];
}

// 2. Navigation & Calendar State Logic
date_default_timezone_set('Asia/Kuala_Lumpur');
$realToday = date('Y-m-d');

// Default to July 6, 2026 to show the demo data, unless a date is provided via URL
$currentDate = isset($_GET['date']) ? $_GET['date'] : '2026-07-06';
$view = isset($_GET['view']) ? $_GET['view'] : 'week';

$dt = new DateTime($currentDate);

// Calculate navigation parameters based on the current view
if ($view === 'week') {
    // Force to Sunday of the current week
    $dt->modify('-' . $dt->format('w') . ' days');
    $startDate = clone $dt;
    
    $prevDate = (clone $startDate)->modify('-1 week')->format('Y-m-d');
    $nextDate = (clone $startDate)->modify('+1 week')->format('Y-m-d');
    $displayTitle = "Week of " . $startDate->format('M j, Y');

} elseif ($view === 'month') {
    $dt->modify('first day of this month');
    $startDate = clone $dt;
    
    $prevDate = (clone $startDate)->modify('-1 month')->format('Y-m-d');
    $nextDate = (clone $startDate)->modify('+1 month')->format('Y-m-d');
    $displayTitle = $startDate->format('F Y');

} elseif ($view === 'year') {
    $dt->modify('first day of January this year');
    $startDate = clone $dt;
    
    $prevDate = (clone $startDate)->modify('-1 year')->format('Y-m-d');
    $nextDate = (clone $startDate)->modify('+1 year')->format('Y-m-d');
    $displayTitle = $startDate->format('Y');
}

// Gather structural records to display as active grid events
$scheduleEvents = [];
$query = "SELECT b.*, c.custName, car.carBrand, car.carModel, car.carPlate 
          FROM booking b
          JOIN customer c ON b.custID = c.custID
          JOIN car ON b.carID = car.carID";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $scheduleEvents[] = $row;
    }
}

// Helper function to render events dynamically for a specific date (Week View)
function renderDayEvents($date, $events) {
    $html = '';
    foreach ($events as $event) {
        $pickupDate = $event['pickupDate'];
        $returnDate = $event['returnDate'];
        
        if ($date >= $pickupDate && $date <= $returnDate) {
            $startTime = ($date == $pickupDate) ? $event['pickupTime'] : "00:00:00";
            $endTime = ($date == $returnDate) ? $event['returnTime'] : "24:00:00"; 
            
            list($startH, $startM, $startS) = explode(':', $startTime);
            $topPosition = ($startH * 72) + ($startM / 60 * 72);
            
            if ($endTime == "24:00:00") {
                $endH = 24; $endM = 0;
            } else {
                list($endH, $endM, $endS) = explode(':', $endTime);
            }
            $endPosition = ($endH * 72) + ($endM / 60 * 72);
            
            $height = max($endPosition - $topPosition, 25); // Minimum height for visibility
            
            $displayStart = date("g:i A", strtotime($startTime));
            $displayEnd = ($endTime == "24:00:00") ? "11:59 PM" : date("g:i A", strtotime($endTime));
            
            $status = $event['bookStatus'];
            $colorClass = "event-blue";
            if ($status == 'Pending') $colorClass = "event-purple";
            elseif ($status == 'Pending Deletion') $colorClass = "event-red";

            $title = htmlspecialchars($event['carBrand'] . ' ' . $event['carModel']);
            $client = htmlspecialchars($event['custName']);
            
            $html .= "<div class='event {$colorClass}' style='top: {$topPosition}px; height: {$height}px;' title='{$client} | {$title}'>";
            $html .= "<div class='event-title'>{$title}</div>";
            $html .= "<div class='event-time'>{$displayStart} - {$displayEnd}</div>";
            $html .= "</div>";
        }
    }
    return $html;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule - Car Rental Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #0B0F19; color: #F3F4F6; display: flex; min-height: 100vh; }

        /* Layout */
        .dashboard { display: grid; grid-template-columns: 260px 1fr; width: 100%; height: 100vh; overflow: hidden; }
        @media (max-width: 768px) { .dashboard { grid-template-columns: 1fr; } .sidebar { display: none; } }

        /* Sidebar Styling */
        .sidebar { background-color: #111827; border-right: 1px solid #1E293B; padding: 30px 20px; display: flex; flex-direction: column; box-shadow: 2px 0 15px rgba(0, 0, 0, 0.3); z-index: 10; }
        .sidebar-header { margin-bottom: 40px; text-align: center; }
        .sidebar-header h2 { color: #7E99FF; font-size: 22px; text-transform: uppercase; letter-spacing: 1px; }
        .nav-menu { list-style: none; display: flex; flex-direction: column; gap: 15px; flex-grow: 1; }
        .nav-item { display: flex; justify-content: space-between; align-items: center; padding: 14px 18px; background-color: transparent; color: #94A3B8; border-radius: 8px; cursor: pointer; font-size: 15px; font-weight: 500; transition: all 0.2s ease; text-decoration: none; border: 1px solid transparent; }
        .nav-item:hover, .nav-item.active { background-color: #1E293B; color: #F3F4F6; border-color: #334155; }
        .nav-item.active { border-left: 4px solid #7E99FF; padding-left: 14px; }
        .badge { background-color: #EF4444; color: #FFFFFF; font-size: 12px; font-weight: 700; padding: 2px 8px; border-radius: 20px; min-width: 24px; text-align: center; box-shadow: 0 0 8px rgba(239, 68, 68, 0.4); }
        .logout-btn { margin-top: auto; padding: 14px 18px; background-color: #0F172A; color: #7E99FF; border: 1px solid #334155; border-radius: 50px; text-align: center; cursor: pointer; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; transition: all 0.2s ease; text-decoration: none; }
        .logout-btn:hover { background-color: #1E293B; color: #FFFFFF; border-color: #7E99FF; box-shadow: 0 0 10px rgba(126, 153, 255, 0.2); }

        /* Main Content */
        .main-content { display: flex; flex-direction: column; height: 100vh; overflow: hidden; }
        .header-section { padding: 18px 40px 12px 40px; }
        .page-header h1 { font-size: 24px; color: #F3F4F6; }
        .page-header p { color: #94A3B8; margin-top: 4px; font-size: 13px; }

        /* Calendar Toolbar */
        .calendar-toolbar { display: flex; justify-content: space-between; align-items: center; margin-top: 14px; padding-bottom: 12px; }
        .calendar-nav { display: flex; align-items: center; gap: 10px; }
        .btn-nav { background-color: #1E293B; color: #F3F4F6; border: 1px solid #334155; padding: 6px 10px; border-radius: 6px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; }
        .btn-nav:hover { background-color: #334155; border-color: #7E99FF; }
        .current-date { font-size: 16px; font-weight: 600; color: #F3F4F6; min-width: 170px; text-align: center; }
        .view-filters { display: flex; background-color: #111827; border: 1px solid #1E293B; border-radius: 8px; overflow: hidden; }
        .view-btn { background: transparent; color: #94A3B8; border: none; padding: 8px 16px; cursor: pointer; font-size: 13px; font-weight: 500; transition: all 0.2s; }
        .view-btn:hover { background-color: #1E293B; color: #F3F4F6; }
        .view-btn.active { background-color: #334155; color: #7E99FF; box-shadow: inset 0 -2px 0 #7E99FF; }

        /* Core Container */
        .calendar-container { flex-grow: 1; background-color: #111827; border-top: 1px solid #1E293B; border-left: 1px solid #1E293B; border-top-left-radius: 12px; margin: 0 40px 40px 40px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2); }

        /* ================= WEEK VIEW ================= */
        .week-view { display: flex; flex-direction: column; height: 100%; }
        .calendar-header-row { display: grid; grid-template-columns: 90px repeat(7, 1fr); border-bottom: 1px solid #1E293B; background-color: #0F172A; min-height: 100px; }
        .time-spacer { border-right: 1px solid #1E293B; }
        .day-header { padding: 20px 12px; text-align: center; border-right: 1px solid #1E293B; color: #94A3B8; display: flex; flex-direction: column; justify-content: center; }
        .day-header .day-name { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; display: block; }
        .day-header .day-number { font-size: 26px; font-weight: 600; color: #F3F4F6; display: inline-block; width: 42px; height: 42px; line-height: 42px; border-radius: 50%; margin: 0 auto; }
        .day-header.today .day-name { color: #7E99FF; }
        .day-header.today .day-number { background-color: #7E99FF; color: #FFFFFF; box-shadow: 0 0 10px rgba(126, 153, 255, 0.4); }

        .calendar-body { flex-grow: 1; overflow-y: auto; position: relative; }
        .time-grid { display: grid; grid-template-columns: 90px repeat(7, 1fr); position: relative; }
        .time-labels { display: flex; flex-direction: column; border-right: 1px solid #1E293B; background-color: #0B0F19; }
        .time-slot-label { height: 72px; padding-right: 12px; text-align: right; font-size: 13px; color: #64748B; position: relative; }
        .time-slot-label span { position: relative; top: -8px; }
        .day-column { border-right: 1px solid #1E293B; position: relative; background-image: linear-gradient(to bottom, #1E293B 1px, transparent 1px); background-size: 100% 72px; }

        .event { position: absolute; left: 5px; right: 5px; border-radius: 8px; padding: 8px 12px; font-size: 13px; overflow: hidden; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s, z-index 0.2s; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .event:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.3); z-index: 50 !important; overflow: visible; height: auto !important; min-height: 100%; }
        .event-title { font-weight: 600; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .event-time { opacity: 0.8; font-size: 11px; }

        .event-blue { background-color: rgba(126, 153, 255, 0.2); border-left: 3px solid #7E99FF; color: #C2D1FF; }
        .event-purple { background-color: rgba(168, 85, 247, 0.2); border-left: 3px solid #A855F7; color: #E9D5FF; }
        .event-red { background-color: rgba(239, 68, 68, 0.2); border-left: 3px solid #EF4444; color: #FECACA; }

        .current-time-line { position: absolute; left: 0; right: 0; height: 2px; background-color: #EF4444; z-index: 5; pointer-events: none; }
        .current-time-line::before { content: ''; position: absolute; left: -4px; top: -4px; width: 10px; height: 10px; border-radius: 50%; background-color: #EF4444; }

        /* ================= MONTH VIEW ================= */
        .month-view { display: flex; flex-direction: column; height: 100%; background: #111827; }
        .month-header { display: grid; grid-template-columns: repeat(7, 1fr); background: #0F172A; text-align: center; padding: 15px 0; border-bottom: 1px solid #1E293B; font-weight: 600; color: #94A3B8; text-transform: uppercase; font-size: 13px; }
        .month-grid { display: grid; grid-template-columns: repeat(7, 1fr); grid-auto-rows: minmax(100px, 1fr); flex-grow: 1; overflow-y: auto; }
        .month-cell { border-right: 1px solid #1E293B; border-bottom: 1px solid #1E293B; padding: 10px; display: flex; flex-direction: column; gap: 4px; overflow-y: auto; }
        .month-cell.empty { background: #0B0F19; }
        .month-cell.today .date-num { background: #7E99FF; color: white; border-radius: 50%; width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center; }
        .date-num { font-size: 14px; font-weight: 600; margin-bottom: 8px; align-self: flex-end; color: #94A3B8; }
        .month-event { font-size: 11px; padding: 5px 8px; border-radius: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: pointer; transition: 0.2s; font-weight: 500; }
        .month-event:hover { filter: brightness(1.2); transform: translateX(2px); }
        .m-blue { background: rgba(126, 153, 255, 0.15); color: #A3B8FF; border-left: 2px solid #7E99FF; }
        .m-purple { background: rgba(168, 85, 247, 0.15); color: #D8B4FE; border-left: 2px solid #A855F7; }
        .m-red { background: rgba(239, 68, 68, 0.15); color: #FCA5A5; border-left: 2px solid #EF4444; }

        /* ================= YEAR VIEW ================= */
        .year-view { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 25px; padding: 40px; overflow-y: auto; height: 100%; background: #111827; align-content: start; }
        .year-month-card { background: #1E293B; border: 1px solid #334155; border-radius: 12px; padding: 30px 20px; text-align: center; cursor: pointer; transition: 0.2s; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .year-month-card:hover { border-color: #7E99FF; transform: translateY(-4px); box-shadow: 0 10px 15px rgba(0,0,0,0.2); }
        .ym-title { font-size: 20px; font-weight: 600; color: #F3F4F6; margin-bottom: 12px; }
        .ym-stat { font-size: 14px; color: #94A3B8; }
        .ym-stat span { font-weight: 700; color: #10B981; font-size: 16px; }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #0B0F19; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #475569; }
    </style>
</head>
<body>

    <div class="dashboard">
        <aside class="sidebar">
            <div class="sidebar-header"><h2>Admin Panel</h2></div>
            <nav class="nav-menu">
                <a href="admin_dashboard.php" class="nav-item">Dashboard</a>
                <a href="admin_tripschedule.php" class="nav-item active">Schedule</a>
                <a href="admin_pendbook.php" class="nav-item">Pending Booking <?php if($pendingBookingCount > 0): ?><span class="badge"><?php echo $pendingBookingCount; ?></span><?php endif; ?></a>
                <a href="admin_penddelete.php" class="nav-item">Pending Deletion <?php if($pendingDeleteCount > 0): ?><span class="badge"><?php echo $pendingDeleteCount; ?></span><?php endif; ?></a>
                <a href="admin_activetrip.php" class="nav-item">Active Trips</a>
                <a href="admin_pasttrip.php" class="nav-item">Past Trips</a>
                <a href="admin_carmanage.php" class="nav-item">Car Management</a>
                <a href="admin_settings.php" class="nav-item">Settings</a>
            </nav>
            <a href="../auth/index.php" class="logout-btn">Log Out</a>
        </aside>

        <main class="main-content">
            <div class="header-section">
                <div class="page-header">
                    <h1>Schedule Overview</h1>
                    <p>Manage fleet availability, maintenance, and active bookings.</p>
                </div>

                <div class="calendar-toolbar">
                    <div class="calendar-nav">
                        <button class="btn-nav" onclick="changeDate('<?php echo $realToday; ?>')">Today</button>
                        <button class="btn-nav" onclick="changeDate('<?php echo $prevDate; ?>')" aria-label="Previous">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path></svg>
                        </button>
                        <button class="btn-nav" onclick="changeDate('<?php echo $nextDate; ?>')" aria-label="Next">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path></svg>
                        </button>
                        <div class="current-date"><?php echo htmlspecialchars($displayTitle); ?></div>
                    </div>

                    <div class="view-filters">
                        <button class="view-btn <?php echo ($view == 'week') ? 'active' : ''; ?>" onclick="changeView('week')">Week</button>
                        <button class="view-btn <?php echo ($view == 'month') ? 'active' : ''; ?>" onclick="changeView('month')">Month</button>
                        <button class="view-btn <?php echo ($view == 'year') ? 'active' : ''; ?>" onclick="changeView('year')">Year</button>
                    </div>
                </div>
            </div>

            <div class="calendar-container">
                <?php if ($view === 'week'): ?>
                    <div class="week-view">
                        <div class="calendar-header-row">
                            <div class="time-spacer"></div>
                            <?php 
                                $weekDt = clone $startDate;
                                for($i=0; $i<7; $i++): 
                                    $colDate = $weekDt->format('Y-m-d');
                                    $isToday = ($colDate === $realToday) ? 'today' : '';
                            ?>
                                <div class="day-header <?php echo $isToday; ?>">
                                    <span class="day-name"><?php echo $weekDt->format('D'); ?></span>
                                    <span class="day-number"><?php echo $weekDt->format('j'); ?></span>
                                </div>
                            <?php 
                                    $weekDt->modify('+1 day');
                                endfor; 
                            ?>
                        </div>

                        <div class="calendar-body" id="weekBody">
                            <div class="time-grid">
                                <div class="time-labels">
                                    <div class="time-slot-label"></div> 
                                    <?php for($h=1; $h<=23; $h++): 
                                        $label = ($h < 12) ? "$h AM" : (($h == 12) ? "12 PM" : ($h-12)." PM");
                                    ?>
                                        <div class="time-slot-label"><span><?php echo $label; ?></span></div>
                                    <?php endfor; ?>
                                </div>

                                <?php 
                                    $weekDt = clone $startDate;
                                    for($i=0; $i<7; $i++): 
                                        $colDate = $weekDt->format('Y-m-d');
                                ?>
                                    <div class="day-column">
                                        <?php if ($colDate === $realToday): 
                                            $currentTimeTop = (date('H') * 72) + (date('i') / 60 * 72); 
                                        ?>
                                            <div class="current-time-line" style="top: <?php echo $currentTimeTop; ?>px;"></div>
                                        <?php endif; ?>
                                        
                                        <?php echo renderDayEvents($colDate, $scheduleEvents); ?>
                                    </div>
                                <?php 
                                        $weekDt->modify('+1 day');
                                    endfor; 
                                ?>
                            </div>
                        </div>
                    </div>

                <?php elseif ($view === 'month'): ?>
                    <div class="month-view">
                        <div class="month-header">
                            <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
                        </div>
                        <div class="month-grid">
                            <?php
                                $daysInMonth = $startDate->format('t');
                                $startDayOfWeek = $startDate->format('w'); // 0 (Sun) to 6 (Sat)
                                
                                // Empty trailing cells before 1st of month
                                for($i=0; $i<$startDayOfWeek; $i++) { 
                                    echo "<div class='month-cell empty'></div>"; 
                                }
                                
                                // Days of month
                                for($d=1; $d<=$daysInMonth; $d++) {
                                    $cellDate = $startDate->format('Y-m-') . str_pad($d, 2, '0', STR_PAD_LEFT);
                                    $isToday = ($cellDate === $realToday) ? 'today' : '';
                                    
                                    echo "<div class='month-cell $isToday'><div class='date-num'>$d</div>";
                                    
                                    foreach($scheduleEvents as $ev) {
                                        if ($cellDate >= $ev['pickupDate'] && $cellDate <= $ev['returnDate']) {
                                            $mColor = 'm-blue';
                                            if ($ev['bookStatus'] == 'Pending') $mColor = 'm-purple';
                                            elseif ($ev['bookStatus'] == 'Pending Deletion') $mColor = 'm-red';
                                            
                                            $shortTitle = htmlspecialchars($ev['carPlate']);
                                            $fullTitle = htmlspecialchars($ev['custName'] . " | " . $ev['carBrand'] . " " . $ev['carModel']);
                                            echo "<div class='month-event $mColor' title='$fullTitle'>$shortTitle</div>";
                                        }
                                    }
                                    echo "</div>";
                                }
                                
                                // Empty trailing cells at end of month
                                $totalCells = $startDayOfWeek + $daysInMonth;
                                $trailing = 7 - ($totalCells % 7);
                                if($trailing < 7) {
                                    for($i=0; $i<$trailing; $i++) { echo "<div class='month-cell empty'></div>"; }
                                }
                            ?>
                        </div>
                    </div>

                <?php elseif ($view === 'year'): ?>
                    <div class="year-view">
                        <?php
                            for($m=1; $m<=12; $m++) {
                                $monthDateStr = $startDate->format('Y-') . str_pad($m, 2, '0', STR_PAD_LEFT);
                                $monthName = date('F', mktime(0,0,0,$m, 10));
                                
                                $count = 0;
                                foreach($scheduleEvents as $ev) {
                                    $evStartM = substr($ev['pickupDate'], 0, 7);
                                    $evEndM = substr($ev['returnDate'], 0, 7);
                                    if ($monthDateStr >= $evStartM && $monthDateStr <= $evEndM) {
                                        $count++;
                                    }
                                }
                                
                                echo "<div class='year-month-card' onclick=\"changeDate('{$monthDateStr}-01', 'month')\">";
                                echo "<div class='ym-title'>$monthName</div>";
                                echo "<div class='ym-stat'><span>$count</span> Booking(s)</div>";
                                echo "</div>";
                            }
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Store current states for JS routing
        const baseDate = "<?php echo $currentDate; ?>";
        const currentView = "<?php echo $view; ?>";

        function changeDate(newDate, forceView = null) {
            const targetView = forceView ? forceView : currentView;
            window.location.href = `?date=${newDate}&view=${targetView}`;
        }

        function changeView(newView) {
            window.location.href = `?date=${baseDate}&view=${newView}`;
        }

        // Auto-scroll the week view so it starts near morning hours rather than midnight
        document.addEventListener("DOMContentLoaded", function() {
            const weekBody = document.getElementById('weekBody');
            if(weekBody) {
                weekBody.scrollTop = 420; 
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>