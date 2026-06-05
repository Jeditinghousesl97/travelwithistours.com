<?php
require_once '../config/db.php';

// Start session and check auth manually since we are delaying header.php inclusion
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Handle Delete Request
if (isset($_POST['delete_inquiry'])) {
    $id = $_POST['inquiry_id'];
    $stmt = $pdo->prepare("DELETE FROM inquiries WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: inquiries.php?status=deleted");
    exit;
}

include 'includes/header.php';

// Fetch Inquiries
$stmt = $pdo->query("SELECT * FROM inquiries ORDER BY created_at DESC");
$inquiries = $stmt->fetchAll();
?>

<div class="header">
    <h2>Inquiries & Bookings</h2>
    <a href="export_inquiries.php" class="btn" style="background: #28a745;"><i class="fas fa-file-excel"></i> Export to Excel</a>
</div>

<?php if (isset($_GET['status']) && $_GET['status'] == 'deleted'): ?>
    <div class="alert alert-success">Inquiry deleted successfully.</div>
<?php
endif; ?>

<div class="card">
    <?php if (count($inquiries) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Message / Details</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($inquiries as $row): ?>
            <tr>
                <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                <td>
                    <?php if ($row['type'] == 'booking'): ?>
                        <span style="background: #e3f2fd; color: #0d47a1; padding: 2px 8px; border-radius: 4px; font-weight: 600; font-size: 12px;">BOOKING</span>
                    <?php
        else: ?>
                        <span style="background: #f3e5f5; color: #7b1fa2; padding: 2px 8px; border-radius: 4px; font-weight: 600; font-size: 12px;">CONTACT</span>
                    <?php
        endif; ?>
                </td>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                <td>
                    <div style="max-height: 50px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 200px; color: #666; font-size: 13px;">
                        <?php echo htmlspecialchars(substr($row['message'], 0, 100)); ?>...
                    </div>
                </td>
                <td>
                    <div style="display: flex; gap: 5px;">
                        <button class="btn" style="background: #00bcd4; padding: 5px 10px; font-size: 12px;" onclick="viewInquiry(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                            <i class="fas fa-eye"></i>
                        </button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this inquiry?');">
                            <input type="hidden" name="inquiry_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="delete_inquiry" class="btn" style="background: #ff1a4a; padding: 5px 10px; font-size: 12px;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php
    endforeach; ?>
        </tbody>
    </table>

    <!-- View Modal -->
    <div id="viewModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: #fff; width: 600px; max-width: 90%; border-radius: 8px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.2);">
            <div style="padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background: #f9f9f9;">
                <h3 style="margin: 0;">Inquiry Details</h3>
                <button onclick="closeModal()" style="border: none; background: none; font-size: 20px; cursor: pointer;">&times;</button>
            </div>
            <div style="padding: 20px; overflow-y: auto; max-height: 70vh;">
                <p><strong>Date:</strong> <span id="modalDate"></span></p>
                <p><strong>Type:</strong> <span id="modalType"></span></p>
                <p><strong>Name:</strong> <span id="modalName"></span></p>
                <p><strong>Email:</strong> <span id="modalEmail"></span></p>
                <p><strong>Phone:</strong> <span id="modalPhone"></span></p>
                <hr style="border: 0; border-top: 1px solid #eee; margin: 15px 0;">
                <div style="background: #f4f4f4; padding: 15px; border-radius: 5px; white-space: pre-wrap; font-family: monospace; font-size: 14px;" id="modalMessage"></div>
            </div>
            <div style="padding: 15px 20px; border-top: 1px solid #eee; text-align: right;">
                <button onclick="closeModal()" class="btn" style="background: #666;">Close</button>
            </div>
        </div>
    </div>

    <script>
    function viewInquiry(data) {
        document.getElementById('modalDate').textContent = new Date(data.created_at).toDateString();
        document.getElementById('modalType').textContent = data.type.toUpperCase();
        document.getElementById('modalName').textContent = data.name;
        document.getElementById('modalEmail').textContent = data.email;
        document.getElementById('modalPhone').textContent = data.phone;
        document.getElementById('modalMessage').textContent = data.message;
        
        document.getElementById('viewModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('viewModal').style.display = 'none';
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        var modal = document.getElementById('viewModal');
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
    </script>
    <?php
else: ?>
        <p style="text-align: center; color: #666;">No inquiries yet.</p>
    <?php
endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
