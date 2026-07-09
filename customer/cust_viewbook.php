<?php
session_start();
$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "keretasewa_db";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 1. Get the IC Number from the URL parameter '?id=' passed by the modal in index.php
$ic_number = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : null;
$custID = null;

// 2. Resolve the Customer ID from the provided IC Number
if ($ic_number) {
    $cust_query = "SELECT custID FROM customer WHERE custIC = '$ic_number' LIMIT 1";
    $cust_result = $conn->query($cust_query);
    if ($cust_result && $cust_result->num_rows > 0) {
        $cust_row = $cust_result->fetch_assoc();
        $custID = $cust_row['custID'];
    }
}

// Handle Deletion Requests - Awaiting admin approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $bookID = intval($_POST['bookID']);
    $currentStatus = isset($_POST['currentStatus']) ? $_POST['currentStatus'] : 'Pending';
    
    // If booking was already Accepted, use a specialized status so admin can revert if rejected
    $newStatus = ($currentStatus === 'Accepted') ? 'Accepted Deletion' : 'Pending Deletion';
    
    $update_sql = "UPDATE booking SET bookStatus = '$newStatus' WHERE bookID = $bookID";
    $conn->query($update_sql);
    
    // Redirect back to avoid form re-submission on refresh
    header("Location: cust_viewbook.php?id=" . urlencode($ic_number));
    exit();
}

// 3. Fetch bookings separated into Active and Past categories (Excluding Deleted/Cancelled)
$activeBookings = [];
$pastBookings = [];

if ($custID) {
    $sql = "SELECT b.*, c.custName, c.contactno, car.carBrand, car.carModel, car.carPlate 
            FROM booking b
            JOIN customer c ON b.custID = c.custID
            JOIN car car ON b.carID = car.carID
            WHERE b.custID = $custID 
              AND b.bookStatus NOT IN ('Deleted', 'Deletion Accepted', 'Cancelled')
            ORDER BY b.bookID DESC";
            
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $status = $row['bookStatus'];
            
            // Group based on status
            if (in_array($status, ['Completed', 'Returned', 'Fine Paid'])) {
                $pastBookings[] = $row;
            } else {
                $activeBookings[] = $row;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings Status</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Poppins', sans-serif; }
        body { background-color: #0b0e14; color: #ffffff; min-height: 100vh; padding: 40px 20px; display: flex; justify-content: center; align-items: flex-start; }
        .container { width: 100%; max-width: 1100px; background-color: #0f131c; border: 1px solid rgba(255, 255, 255, 0.03); border-radius: 16px; padding: 40px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5); }
        
        .header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); padding-bottom: 20px; }
        .title h1 { font-size: 26px; font-weight: 700; color: #ffffff; }
        .title p { color: #94a3b8; font-size: 14px; margin-top: 4px; }
        .btn-home { background: transparent; border: 1px solid #7c9eff; color: #7c9eff; padding: 10px 24px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; transition: all 0.2s ease; }
        .btn-home:hover { background-color: #7c9eff; color: #0b0e14; }

        .table-container { overflow-x: auto; margin-bottom: 40px; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 16px; background-color: #141a26; color: #94a3b8; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid rgba(255, 255, 255, 0.05); }
        td { padding: 16px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); font-size: 14px; color: #e2e8f0; vertical-align: middle; }
        tr:hover { background-color: rgba(255, 255, 255, 0.02); }

        .status-badge { display: inline-block; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; text-align: center; }
        .status-pending { background-color: rgba(245, 158, 11, 0.12); color: #f59e0b; }
        .status-accepted { background-color: rgba(16, 185, 129, 0.12); color: #10b981; }
        .status-active { background-color: rgba(59, 130, 246, 0.12); color: #3b82f6; }
        .status-completed { background-color: rgba(139, 92, 246, 0.12); color: #8b5cf6; }
        .status-returned { background-color: rgba(139, 92, 246, 0.12); color: #8b5cf6; }
        .status-finepaid { background-color: rgba(139, 92, 246, 0.12); color: #8b5cf6; }
        .status-pendingdeletion, .status-accepteddeletion { background-color: rgba(239, 68, 68, 0.12); color: #ef4444; }

        .btn-action { background-color: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); color: #ffffff; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 500; cursor: pointer; transition: all 0.2s ease; margin-right: 5px; }
        .btn-action:hover { background-color: #7c9eff; color: #0b0e14; border-color: #7c9eff; }
        .btn-delete { background-color: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444; }
        .btn-delete:hover { background-color: #ef4444; color: #ffffff; border-color: #ef4444; }

        /* --- COLLAPSIBLE BOX DESIGN SECTION --- */
        .collapsible-box-section { background-color: #141a26; border: 1px dashed rgba(255, 255, 255, 0.1); border-radius: 12px; padding: 20px; margin-top: 20px; }
        .collapsible-box-header { display: flex; justify-content: space-between; align-items: center; cursor: pointer; user-select: none; }
        .collapsible-box-header h3 { font-size: 16px; color: #94a3b8; display: flex; align-items: center; gap: 10px; }
        .collapsible-box-counter { background-color: rgba(255, 255, 255, 0.08); color: #94a3b8; padding: 2px 8px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .toggle-icon { font-size: 14px; color: #94a3b8; transition: transform 0.3s ease; }
        .collapsible-box-content { display: none; margin-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.05); padding-top: 15px; }
        .collapsible-box-section.open .collapsible-box-content { display: block; }
        .collapsible-box-section.open .toggle-icon { transform: rotate(180deg); }

        /* --- SYSTEM MODAL COMPONENT WINDOW --- */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(7, 10, 15, 0.8); backdrop-filter: blur(8px); display: flex; justify-content: center; align-items: center; z-index: 100; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; }
        .modal-overlay.show { opacity: 1; pointer-events: auto; }
        .modal-box { background-color: #0f131c; border: 1px solid rgba(255, 255, 255, 0.05); width: 100%; max-width: 550px; border-radius: 16px; padding: 35px; box-shadow: 0 25px 50px rgba(0,0,0,0.6); transform: translateY(-20px); transition: transform 0.3s ease; }
        .modal-overlay.show .modal-box { transform: translateY(0); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .modal-header h2 { font-size: 20px; font-weight: 700; color: #7c9eff; }
        .btn-close { background: transparent; border: none; color: #94a3b8; font-size: 22px; cursor: pointer; transition: color 0.2s; }
        .btn-close:hover { color: #ffffff; }
        
        .detail-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid rgba(255, 255, 255, 0.02); font-size: 14px; }
        .detail-label { color: #94a3b8; font-weight: 500; }
        .detail-value { color: #ffffff; font-weight: 600; text-align: right; }
        .no-data { text-align: center; color: #94a3b8; padding: 30px 0; font-style: italic; }
    </style>
</head>
<body>

    <div class="container">
        <div class="header-section">
            <div class="title">
                <h1>My Reservations</h1>
                <p>Track booking logs, approval states, or request cancellations.</p>
            </div>
            <a href="../auth/index.php" class="btn-home">Back to Home</a>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Car Model</th>
                        <th>Plate No</th>
                        <th>Pick-up Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($activeBookings)): ?>
                        <?php foreach ($activeBookings as $row): 
                            $displayStatus = ($row['bookStatus'] === 'Accepted Deletion') ? 'Pending Deletion' : $row['bookStatus'];
                            $statusClass = 'status-' . strtolower(str_replace(' ', '', $displayStatus));
                        ?>
                            <tr>
                                <td>#BK-<?php echo $row['bookID']; ?></td>
                                <td><?php echo htmlspecialchars($row['carBrand'] . ' ' . $row['carModel']); ?></td>
                                <td><span style="color: #7c9eff; font-weight: 600;"><?php echo htmlspecialchars($row['carPlate']); ?></span></td>
                                <td><?php echo date('d M Y', strtotime($row['pickupDate'])); ?></td>
                                <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $displayStatus; ?></span></td>
                                <td>
                                    <button class="btn-action view-details-btn" 
                                            data-id="<?php echo $row['bookID']; ?>"
                                            data-name="<?php echo htmlspecialchars($row['custName']); ?>"
                                            data-phone="<?php echo htmlspecialchars($row['contactno']); ?>"
                                            data-plate="<?php echo htmlspecialchars($row['carPlate']); ?>"
                                            data-model="<?php echo htmlspecialchars($row['carBrand'] . ' ' . $row['carModel']); ?>"
                                            data-pdate="<?php echo date('d M Y', strtotime($row['pickupDate'])); ?>"
                                            data-ptime="<?php echo date('h:i A', strtotime($row['pickupTime'])); ?>"
                                            data-rdate="<?php echo date('d M Y', strtotime($row['returnDate'])); ?>"
                                            data-rtime="<?php echo date('h:i A', strtotime($row['returnTime'])); ?>"
                                            data-status="<?php echo $displayStatus; ?>">
                                        View Details
                                    </button>
                                    
                                    <?php if (in_array($row['bookStatus'], ['Pending', 'Accepted'])): ?>
                                        <form method="POST" action="" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to request cancellation for this booking?');">
                                            <input type="hidden" name="bookID" value="<?php echo $row['bookID']; ?>">
                                            <input type="hidden" name="currentStatus" value="<?php echo $row['bookStatus']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn-action btn-delete">Cancel Booking</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="no-data">No active or pending reservations found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="collapsible-box-section" id="pastTripsSection">
            <div class="collapsible-box-header" onclick="toggleSection('pastTripsSection')">
                <h3>
                    🕒 Past Trips
                    <span class="collapsible-box-counter"><?php echo count($pastBookings); ?></span>
                </h3>
                <span class="toggle-icon">▼</span>
            </div>
            
            <div class="collapsible-box-content">
                <div class="table-container" style="margin-bottom: 0;">
                    <table>
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Car Model</th>
                                <th>Plate No</th>
                                <th>Pick-up Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($pastBookings)): ?>
                                <?php foreach ($pastBookings as $row): 
                                    $statusClass = 'status-' . strtolower(str_replace(' ', '', $row['bookStatus']));
                                ?>
                                    <tr>
                                        <td>#BK-<?php echo $row['bookID']; ?></td>
                                        <td><?php echo htmlspecialchars($row['carBrand'] . ' ' . $row['carModel']); ?></td>
                                        <td><span style="color: #7c9eff; font-weight: 600;"><?php echo htmlspecialchars($row['carPlate']); ?></span></td>
                                        <td><?php echo date('d M Y', strtotime($row['pickupDate'])); ?></td>
                                        <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $row['bookStatus']; ?></span></td>
                                        <td>
                                            <button class="btn-action view-details-btn" 
                                                    data-id="<?php echo $row['bookID']; ?>"
                                                    data-name="<?php echo htmlspecialchars($row['custName']); ?>"
                                                    data-phone="<?php echo htmlspecialchars($row['contactno']); ?>"
                                                    data-plate="<?php echo htmlspecialchars($row['carPlate']); ?>"
                                                    data-model="<?php echo htmlspecialchars($row['carBrand'] . ' ' . $row['carModel']); ?>"
                                                    data-pdate="<?php echo date('d M Y', strtotime($row['pickupDate'])); ?>"
                                                    data-ptime="<?php echo date('h:i A', strtotime($row['pickupTime'])); ?>"
                                                    data-rdate="<?php echo date('d M Y', strtotime($row['returnDate'])); ?>"
                                                    data-rtime="<?php echo date('h:i A', strtotime($row['returnTime'])); ?>"
                                                    data-status="<?php echo $row['bookStatus']; ?>">
                                                Review Log
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="no-data">No completed trips found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="detailModal">
        <div class="modal-box">
            <div class="modal-header">
                <h2>Reservation Summary</h2>
                <button class="btn-close" id="closeModalBtn">&times;</button>
            </div>
            <div id="modalDetailsContent"></div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('detailModal');
        const closeBtn = document.getElementById('closeModalBtn');
        const modalDetailsContent = document.getElementById('modalDetailsContent');

        function toggleSection(sectionId) {
            const section = document.getElementById(sectionId);
            section.classList.toggle('open');
        }

        document.querySelectorAll('.view-details-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const data = this.dataset;
                
                const bookingDetails = [
                    { label: 'Booking Code Reference', value: '#BK-' + data.id },
                    { label: 'Customer Name', value: data.name },
                    { label: 'Contact Number', value: data.phone },
                    { label: 'No. Plate', value: data.plate },
                    { label: 'Car Model', value: data.model },
                    { label: 'Pick-Up Date', value: data.pdate },
                    { label: 'Pick-Up Time', value: data.ptime }, 
                    { label: 'Return Date', value: data.rdate },
                    { label: 'Return Time', value: data.rtime },  
                    { label: 'Status Log Summary', value: `<span class="status-badge status-${data.status.toLowerCase().replace(' ', '')}">${data.status}</span>` }
                ];

                let htmlContent = '';
                bookingDetails.forEach(detail => {
                    htmlContent += `
                        <div class="detail-row">
                            <span class="detail-label">${detail.label}</span>
                            <span class="detail-value">${detail.value}</span>
                        </div>
                    `;
                });

                modalDetailsContent.innerHTML = htmlContent;
                modal.classList.add('show');
            });
        });

        closeBtn.addEventListener('click', () => modal.classList.remove('show'));
        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('show');
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>