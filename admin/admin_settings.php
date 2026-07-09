<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "keretasewa_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// ==========================================
// HANDLE FORM ACTIONS (ADD, EDIT, DELETE)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add') {
        $adminName = $conn->real_escape_string($_POST['adminName']);
        // Hash the password for security
        $adminPass = password_hash($_POST['adminPass'], PASSWORD_DEFAULT); 
        
        $sql = "INSERT INTO admin (adminName, adminPass) VALUES ('$adminName', '$adminPass')";
        $conn->query($sql);
        
    } elseif ($action === 'edit') {
        $adminID = intval($_POST['adminID']);
        $adminName = $conn->real_escape_string($_POST['adminName']);
        
        // Only update password if a new one is typed in
        if (!empty($_POST['adminPass'])) {
            $adminPass = password_hash($_POST['adminPass'], PASSWORD_DEFAULT);
            $sql = "UPDATE admin SET adminName='$adminName', adminPass='$adminPass' WHERE adminID=$adminID";
        } else {
            $sql = "UPDATE admin SET adminName='$adminName' WHERE adminID=$adminID";
        }
        $conn->query($sql);
        
    } elseif ($action === 'delete') {
        $adminID = intval($_POST['adminID']);
        $sql = "DELETE FROM admin WHERE adminID=$adminID";
        $conn->query($sql);
    }
    
    // Refresh page to prevent form resubmission
    header("Location: admin_settings.php");
    exit();
}

// ==========================================
// FETCH DASHBOARD DATA
// ==========================================
$pendingBookingsResult = $conn->query("SELECT COUNT(*) FROM booking WHERE bookStatus = 'Pending'");
$pendingBookings = $pendingBookingsResult ? $pendingBookingsResult->fetch_row()[0] : 0;

$pendingDeletionResult = $conn->query("SELECT COUNT(*) FROM booking WHERE bookStatus = 'Pending Deletion'");
$pendingDeletion = $pendingDeletionResult ? $pendingDeletionResult->fetch_row()[0] : 0;

$query = "SELECT * FROM admin";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Dashboard</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #0B0F19; color: #F3F4F6; display: flex; min-height: 100vh; }
        
        .dashboard { display: grid; grid-template-columns: 260px 1fr; width: 100%; }
        
        /* Sidebar */
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

        /* Main Content */
        .main-content { padding: 40px; overflow-y: auto; }
        .page-header-row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; }
        .page-header h1 { font-size: 28px; color: #F3F4F6; }
        .page-header p { color: #94A3B8; margin-top: 5px; }

        .btn-add { display: inline-flex; align-items: center; justify-content: center; padding: 10px 16px; background-color: #7E99FF; color: #0B0F19; border: none; border-radius: 8px; font-weight: 600; transition: all 0.2s ease; cursor: pointer; }
        .btn-add:hover { background-color: #5f7ff5; transform: translateY(-1px); }
        
        .table-container { background-color: #111827; border-radius: 12px; border: 1px solid #1E293B; overflow-x: auto; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2); }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 16px 20px; background-color: #0F172A; color: #94A3B8; font-size: 12px; text-transform: uppercase; border-bottom: 1px solid #1E293B; text-align: left; }
        td { padding: 16px 20px; border-bottom: 1px solid #1E293B; color: #F3F4F6; font-size: 14px; }
        tr:hover { background-color: rgba(30, 41, 59, 0.4); }

        .status-active { color: #10B981; background: rgba(16, 185, 129, 0.15); padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .action-btn { padding: 6px 12px; background: transparent; border: 1px solid #475569; color: #94A3B8; border-radius: 6px; cursor: pointer; margin-right: 5px; transition: 0.2s; }
        .action-btn:hover { background: #334155; color: white; }

        /* Modal Styles */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 100; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
        .modal-content { background: #111827; padding: 30px; border-radius: 12px; width: 400px; border: 1px solid #1E293B; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        .modal-title { font-size: 20px; color: #F3F4F6; margin-bottom: 20px; font-weight: 600; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; color: #94A3B8; font-size: 13px; font-weight: 500; }
        .form-group input { width: 100%; padding: 10px 12px; background: #0B0F19; border: 1px solid #334155; border-radius: 6px; color: white; font-size: 14px; outline: none; }
        .form-group input:focus { border-color: #7E99FF; }
        .modal-actions { display: flex; gap: 10px; margin-top: 25px; }
        .btn-submit { flex: 1; background: #7E99FF; color: #0B0F19; border: none; padding: 10px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-submit:hover { background: #5f7ff5; }
        .btn-cancel { flex: 1; background: transparent; color: #94A3B8; border: 1px solid #475569; padding: 10px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-cancel:hover { background: #1E293B; color: white; }
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
                <a href="admin_carmanage.php" class="nav-item">Car Management</a>
                <a href="admin_settings.php" class="nav-item active">Settings</a>
            </nav>
            <a href="../auth/index.php" class="logout-btn">Log Out</a>
        </aside>

        <main class="main-content">
            <div class="page-header-row">
                <div class="page-header">
                    <h1>Administrator Settings</h1>
                    <p>Manage system administrators and account permissions.</p>
                </div>
                <button class="btn-add" onclick="openModal('add')">+ Add New Admin</button>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Admin Name</th>
                            <th>Password (Hashed)</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): 
                                $adminID = $row['adminID'];
                                $adminName = htmlspecialchars($row['adminName']);
                                // Truncate hashed password for clean display
                                $shortPass = substr($row['adminPass'], 0, 20) . '...'; 
                            ?>
                            <tr>
                                <td><?php echo $adminName; ?></td>
                                <td style="font-family: monospace; color: #94A3B8;"><?php echo $shortPass; ?></td>
                                <td><span class="status-active">Active</span></td>
                                <td>
                                    <button class="action-btn" onclick="openModal('edit', '<?php echo $adminID; ?>', '<?php echo addslashes($adminName); ?>')">Edit</button>
                                    
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="adminID" value="<?php echo $adminID; ?>">
                                        <button type="submit" class="action-btn" style="border-color: #EF4444; color: #EF4444;" onclick="return confirm('Are you absolutely sure you want to delete this admin account?');">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center;">No administrators found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <div class="modal-overlay" id="adminModal">
        <div class="modal-content">
            <h3 class="modal-title" id="modalTitle">Add New Admin</h3>
            <form method="POST" id="adminForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="adminID" id="formAdminID" value="">
                
                <div class="form-group">
                    <label>Admin Username</label>
                    <input type="text" name="adminName" id="formAdminName" required placeholder="Enter username">
                </div>
                
                <div class="form-group">
                    <label>Password (Appear as Identification number)</label>
                    <input type="password" name="adminPass" id="formAdminPass" placeholder="Enter password">
                    <small id="passHint" style="color: #64748B; font-size: 11px; display: none; margin-top: 4px;">Leave blank to keep existing password.</small>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-submit">Save Admin</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(mode, id = '', name = '') {
            const modal = document.getElementById('adminModal');
            const formAction = document.getElementById('formAction');
            const modalTitle = document.getElementById('modalTitle');
            const nameInput = document.getElementById('formAdminName');
            const passInput = document.getElementById('formAdminPass');
            const idInput = document.getElementById('formAdminID');
            const passHint = document.getElementById('passHint');

            modal.style.display = 'flex';
            formAction.value = mode;

            if (mode === 'edit') {
                modalTitle.innerText = 'Edit Admin Details';
                idInput.value = id;
                nameInput.value = name;
                passInput.required = false; // Optional password change on edit
                passHint.style.display = 'block';
            } else {
                modalTitle.innerText = 'Add New Admin';
                idInput.value = '';
                nameInput.value = '';
                passInput.value = '';
                passInput.required = true; // Required on add
                passHint.style.display = 'none';
            }
        }

        function closeModal() {
            document.getElementById('adminModal').style.display = 'none';
        }
        
        // Close modal if clicked outside
        window.onclick = function(event) {
            const modal = document.getElementById('adminModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>