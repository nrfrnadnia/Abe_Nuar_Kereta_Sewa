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

// Fetch dynamic counts for sidebar badges
$pendingBookings = $conn->query("SELECT COUNT(*) FROM booking WHERE bookStatus = 'Pending'")->fetch_row()[0];
$pendingDeletionResult = $conn->query("SELECT COUNT(*) FROM booking WHERE bookStatus = 'Pending Deletion'");
$pendingDeletion = $pendingDeletionResult ? $pendingDeletionResult->fetch_row()[0] : 0;

// Fetch completed trips and sum up total revenue
$query = "SELECT b.bookID, b.pickupDate, b.returnDate, b.bookStatus, c.custName, car.carModel, car.carPlate, 
                 p.payTotal, p.dayTotal, p.timeTotal, pen.penTotal 
          FROM booking b 
          JOIN customer c ON b.custID = c.custID 
          JOIN car ON b.carID = car.carID 
          LEFT JOIN payment p ON b.bookID = p.bookID
          LEFT JOIN penalty pen ON b.bookID = pen.bookID
          WHERE b.bookStatus IN ('Completed', 'Returned', 'Fine Paid')
          ORDER BY b.returnDate DESC";

$result = $conn->query($query);

// Pre-calculate totals for statistics
$totalRevenue = 0;
$recordsData = [];

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $totalRevenue += floatval($row['payTotal']);
        $recordsData[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel - Past Trips</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Poppins', sans-serif; }
    body { background-color: #0B0F19; color: #F3F4F6; display: flex; min-height: 100vh; }
    .dashboard { display: grid; grid-template-columns: 260px 1fr; width: 100%; }
    .sidebar { background-color: #111827; border-right: 1px solid #1E293B; padding: 30px 20px; display: flex; flex-direction: column; box-shadow: 2px 0 15px rgba(0, 0, 0, 0.3); z-index: 10; }
    .sidebar-header { margin-bottom: 40px; text-align: center; }
    .sidebar-header h2 { color: #7E99FF; font-size: 22px; text-transform: uppercase; letter-spacing: 1px; }
    .nav-menu { list-style: none; display: flex; flex-direction: column; gap: 15px; flex-grow: 1; }
    .nav-item { display: flex; justify-content: space-between; align-items: center; padding: 14px 18px; color: #94A3B8; text-decoration: none; font-weight: 500; font-size: 15px; border-radius: 8px; transition: all 0.2s ease; cursor: pointer; border: 1px solid transparent; }
    .nav-item:hover, .nav-item.active { background-color: #1E293B; color: #F3F4F6; border-color: #334155; }
    .nav-item.active { border-left: 4px solid #7E99FF; padding-left: 14px; }
    .badge { background-color: #EF4444; color: #FFFFFF; font-size: 12px; font-weight: 700; padding: 2px 8px; border-radius: 20px; min-width: 24px; text-align: center; box-shadow: 0 0 8px rgba(239, 68, 68, 0.4); }
    .logout-btn { margin-top: auto; padding: 14px 18px; background-color: #0F172A; color: #7E99FF; border: 1px solid #334155; border-radius: 50px; text-align: center; cursor: pointer; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; transition: all 0.2s ease; text-decoration: none; }
    .logout-btn:hover { background-color: #1E293B; color: #FFFFFF; border-color: #7E99FF; box-shadow: 0 0 10px rgba(126, 153, 255, 0.2); }
    .main-content { flex: 1; padding: 40px; overflow-y: auto; }
    .header-row { margin-bottom: 30px; }
    .page-title { font-size: 1.8rem; font-weight: 700; margin-bottom: 5px; }
    .page-subtitle { color: #657597; font-size: 0.9rem; }
    .stats-row { display: grid; grid-template-columns: repeat(2, minmax(180px, 1fr)); gap: 16px; margin-bottom: 28px; }
    .stat-card { background-color: #121824; border: 1px solid rgba(255, 255, 255, 0.03); border-top: 3px solid #7b91f9; border-radius: 8px; padding: 16px 18px; }
    .stat-label { font-size: 0.8rem; font-weight: 700; color: #657597; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; }
    .stat-value { font-size: 2rem; font-weight: 700; color: #7b91f9; }
    .controls-row { display: flex; flex-direction: column; gap: 15px; margin-bottom: 25px; background-color: #121824; padding: 20px; border-radius: 8px; border: 1px solid rgba(255, 255, 255, 0.02); }
    .filter-line { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
    .filter-label { font-size: 0.8rem; color: #657597; font-weight: 700; text-transform: uppercase; min-width: 110px; }
    .filter-btn-group { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .btn-filter { background-color: #1a2232; border: 1px solid rgba(255, 255, 255, 0.05); color: #cfd7e6; padding: 6px 14px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease; height: 34px; }
    .btn-filter:hover { background-color: rgba(123, 145, 249, 0.1); color: #7b91f9; }
    .btn-filter.active { background-color: #7b91f9; color: #0b0e14; border-color: #7b91f9; }
    .select-filter { background-color: #1a2232; border: 1px solid rgba(255, 255, 255, 0.05); color: #cfd7e6; padding: 6px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; cursor: pointer; outline: none; height: 34px; transition: all 0.2s ease; }
    .select-filter:focus, .select-filter.active { border-color: #7b91f9; color: #7b91f9; }
    .select-filter option { background-color: #121824; color: #ffffff; }
    .btn-export { background-color: #1a2232; border: 1px solid #2ed573; color: #2ed573; padding: 8px 18px; border-radius: 6px; font-size: 0.85rem; font-weight: 600; cursor: pointer; align-self: flex-end; transition: all 0.2s ease; }
    .btn-export:hover { background-color: #2ed573; color: white; }
    .table-container { background-color: #121824; border: 1px solid rgba(255, 255, 255, 0.03); border-radius: 8px; padding: 25px; }
    .table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .table-title { font-size: 1.1rem; font-weight: 600; }
    .search-box { background-color: #1a2232; border: 1px solid rgba(255, 255, 255, 0.05); padding: 10px 16px; border-radius: 6px; color: white; font-size: 0.85rem; outline: none; width: 280px; }
    .search-box:focus { border-color: #7b91f9; }
    .trips-table { width: 100%; border-collapse: collapse; text-align: left; }
    .trips-table th { color: #4e5d7c; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 12px 16px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
    .trips-table td { padding: 16px; font-size: 0.9rem; border-bottom: 1px solid rgba(255, 255, 255, 0.02); }
    .car-info { font-weight: 600; color: #ffffff; }
    .plate-number { font-size: 0.75rem; color: #657597; display: block; margin-top: 2px; }
    .date-sub { font-size: 0.75rem; color: #657597; display: block; margin-top: 2px; }
    .duration-badge { background-color: #1a2232; color: #7b91f9; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600; border: 1px solid rgba(123, 145, 249, 0.15); display: inline-block; }
    .status-pill { display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
    .status-pill.completed { background-color: rgba(46, 213, 115, 0.1); color: #2ed573; }
    .status-pill.fine-paid { background-color: rgba(255, 165, 0, 0.1); color: #ffa500; }
    @media (max-width: 768px) { .dashboard { grid-template-columns: 1fr; } .sidebar { display: none; } }
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
        <a href="admin_pasttrip.php" class="nav-item active">Past Trips</a>
        <a href="admin_carmanage.php" class="nav-item">Car Management</a>
        <a href="admin_settings.php" class="nav-item">Settings</a>
      </nav>
      <a href="../auth/index.php" class="logout-btn">Log Out</a>
    </aside>

    <main class="main-content">
    <div class="header-row">
      <h1 class="page-title">Past Trips</h1>
      <p class="page-subtitle">View and trace historical completed vehicle hire records.</p>
    </div>

    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-label">Total Filtered Records</div>
        <div class="stat-value" id="pastTotalCount"><?php echo count($recordsData); ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total Revenue Collected</div>
        <div class="stat-value" style="color: #2ed573;">RM <?php echo number_format($totalRevenue, 2); ?></div>
      </div>
    </div>

    <div class="controls-row">
      <div class="filter-line">
        <span class="filter-label">Filter Time:</span>
        <div class="filter-btn-group">
          <button class="btn-filter active" id="btnFilterAll" onclick="filterPastTime('all', this)">All History</button>
          <button class="btn-filter" id="btnFilterWeek" onclick="filterPastTime('week', this)">This Week</button>
          <select class="select-filter" id="monthSelect" onchange="dropdownElementChanged()">
            <option value="">-- All Months --</option>
            <option value="0">January</option> <option value="1">February</option> <option value="2">March</option>
            <option value="3">April</option> <option value="4">May</option> <option value="5">June</option>
            <option value="6">July</option> <option value="7">August</option> <option value="8">September</option>
            <option value="9">October</option> <option value="10">November</option> <option value="11">December</option>
          </select>
          <select class="select-filter" id="yearSelect" onchange="dropdownElementChanged()">
            <option value="">-- All Years --</option>
          </select>
        </div>
      </div>

      <div class="filter-line">
        <span class="filter-label">Filter Status:</span>
        <div class="filter-btn-group">
          <button class="btn-filter active" onclick="filterPastStatus('all', this)">All Status</button>
          <button class="btn-filter" onclick="filterPastStatus('completed', this)">Completed</button>
          <button class="btn-filter" onclick="filterPastStatus('fine-paid', this)">Fine Paid</button>
        </div>
      </div>

      <button class="btn-export" onclick="downloadCSV()">📥 Export Report</button>
    </div>

    <div class="table-container">
      <div class="table-header">
        <h2 class="table-title">Historical Records</h2>
        <input type="text" id="pastTableSearch" class="search-box" placeholder="Search Customer Name or Plate..." onkeyup="executePastSearchFilter()">
      </div>

      <table class="trips-table">
        <thead>
          <tr>
            <th>Customer</th>
            <th>Vehicle Details</th>
            <th>Trip Period</th>
            <th>Duration</th>
            <th>Total Paid</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody id="pastTableBody">
            <?php if (!empty($recordsData)): ?>
                <?php foreach ($recordsData as $row): 
                    $dataStatus = ($row['penTotal'] > 0) ? 'fine-paid' : 'completed';
                    $pillClass = ($row['penTotal'] > 0) ? 'fine-paid' : 'completed';
                    $pillText = ($row['penTotal'] > 0) ? 'Fine Paid' : 'Completed';
                    
                    // Convert hours to a string if needed
                    $timeMins = intval($row['timeTotal']);
                    $hours = floor($timeMins / 60);
                ?>
                <tr data-status="<?php echo $dataStatus; ?>">
                    <td class="cus-name"><?php echo htmlspecialchars($row['custName']); ?></td>
                    <td>
                        <span class="car-info"><?php echo htmlspecialchars($row['carModel']); ?></span>
                        <span class="plate-number"><?php echo htmlspecialchars($row['carPlate']); ?></span>
                    </td>
                    <td>
                        <span><?php echo $row['returnDate']; ?></span>
                        <span class="date-sub">Start: <?php echo $row['pickupDate']; ?></span>
                    </td>
                    <td><span class="duration-badge"><?php echo $row['dayTotal']; ?> Days <?php echo $hours; ?> Hours</span></td>
                    <td>RM <?php echo number_format($row['payTotal'], 2); ?></td>
                    <td><span class="status-pill <?php echo $pillClass; ?>"><?php echo $pillText; ?></span></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center;">No past trips recorded yet.</td></tr>
            <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>

  <script>
    let currentTimeFilter = 'all'; 
    let currentStatusFilter = 'all';

    function setupYearDropdown() {
      const yearSelect = document.getElementById('yearSelect');
      const currentYear = new Date().getFullYear();
      for (let year = 2020; year <= currentYear; year++) {
        let option = document.createElement('option');
        option.value = year;
        option.innerText = year;
        yearSelect.appendChild(option);
      }
    }

    function executePastSearchFilter() {
      const searchInputValue = document.getElementById('pastTableSearch').value.toLowerCase().trim();
      const tableRows = document.getElementById('pastTableBody').getElementsByTagName('tr');
      
      const monthSelectValue = document.getElementById('monthSelect').value;
      const yearSelectValue = document.getElementById('yearSelect').value;
      
      let visibleCount = 0;
      const today = new Date();
      
      for (let i = 0; i < tableRows.length; i++) {
        const row = tableRows[i];
        if(row.cells.length === 1) continue; // Skip empty message
        
        const rowStatus = row.getAttribute('data-status');
        const nameValue = row.querySelector('.cus-name').innerText.toLowerCase();
        const plateValue = row.querySelector('.plate-number').innerText.toLowerCase();
        
        const dateSpan = row.cells[2].querySelector('span');
        const dateString = dateSpan ? dateSpan.innerText.trim() : "";
        const rowDate = new Date(dateString);

        let matchesTimeFilter = false;

        if (currentTimeFilter === 'all') {
          matchesTimeFilter = true;
        } else if (currentTimeFilter === 'week') {
          const diffTime = Math.abs(today - rowDate);
          const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
          if (diffDays <= 7) matchesTimeFilter = true;
        } else if (currentTimeFilter === 'dropdown-select') {
          let matchesMonth = (monthSelectValue === "" || rowDate.getMonth() === parseInt(monthSelectValue));
          let matchesYear = (yearSelectValue === "" || rowDate.getFullYear() === parseInt(yearSelectValue));
          if (matchesMonth && matchesYear) matchesTimeFilter = true;
        }

        let matchesStatusFilter = (currentStatusFilter === 'all' || rowStatus === currentStatusFilter);
        const matchesSearch = nameValue.includes(searchInputValue) || plateValue.includes(searchInputValue);

        if (matchesSearch && matchesTimeFilter && matchesStatusFilter) {
          row.style.display = "";
          visibleCount++;
        } else {
          row.style.display = "none";
        }
      }
      document.getElementById('pastTotalCount').innerText = visibleCount;
    }

    function filterPastTime(timeType, buttonElement) {
      document.getElementById('btnFilterAll').classList.remove('active');
      document.getElementById('btnFilterWeek').classList.remove('active');
      document.getElementById('monthSelect').classList.remove('active');
      document.getElementById('yearSelect').classList.remove('active');
      
      buttonElement.classList.add('active');
      currentTimeFilter = timeType;

      if (timeType !== 'dropdown-select') {
        document.getElementById('monthSelect').value = "";
        document.getElementById('yearSelect').value = "";
      }
      executePastSearchFilter();
    }

    function dropdownElementChanged() {
      const mSel = document.getElementById('monthSelect');
      const ySel = document.getElementById('yearSelect');

      if (mSel.value !== "" || ySel.value !== "") {
        document.getElementById('btnFilterAll').classList.remove('active');
        document.getElementById('btnFilterWeek').classList.remove('active');
        currentTimeFilter = 'dropdown-select';
        
        if (mSel.value !== "") mSel.classList.add('active'); else mSel.classList.remove('active');
        if (ySel.value !== "") ySel.classList.add('active'); else ySel.classList.remove('active');
      } else {
        currentTimeFilter = 'all';
        document.getElementById('btnFilterAll').classList.add('active');
        mSel.classList.remove('active');
        ySel.classList.remove('active');
      }
      executePastSearchFilter();
    }

    function filterPastStatus(statusType, buttonElement) {
      const group = buttonElement.parentElement.querySelectorAll('.btn-filter');
      group.forEach(btn => btn.classList.remove('active'));
      buttonElement.classList.add('active');
      currentStatusFilter = statusType;
      executePastSearchFilter();
    }

    function downloadCSV() {
      const rows = document.querySelectorAll("table tr");
      let csvContent = "data:text/csv;charset=utf-8,";
      rows.forEach(row => {
        if (row.style.display !== "none") {
          let rowData = [];
          const cols = row.querySelectorAll("td, th");
          cols.forEach(col => {
            let text = col.innerText.replace(/(\r\n|\n|\r)/gm, " ").trim();
            rowData.push(`"${text}"`);
          });
          csvContent += rowData.join(",") + "\n";
        }
      });
      const encodedUri = encodeURI(csvContent);
      const link = document.createElement("a");
      link.setAttribute("href", encodedUri);
      link.setAttribute("download", "Laporan_Past_Trips.csv");
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }

    window.onload = function() {
      setupYearDropdown();
      executePastSearchFilter();
    }
  </script>
</body>
</html>
<?php $conn->close(); ?>