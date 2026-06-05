<?php
require_once 'includes/auth_session.php';
require_once '../config/db.php';

$success = '';
$error = '';
$edit_mode = false;
$partner_to_edit = null;

// Auto-Update Database Schema
try {
    $pdo->query("SELECT 1 FROM partners LIMIT 1");
}
catch (Exception $e) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS partners (
            id INT(11) NOT NULL AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            link VARCHAR(255) DEFAULT '#',
            logo VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    catch (Exception $ex) {
    }
}

// Handle Add/Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $link = $_POST['link'];

    // Update Existing Partner
    if (isset($_POST['update_id'])) {
        $id = $_POST['update_id'];
        $logo_path = $_POST['existing_logo'];

        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $allowed = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'svg');
            $filename = $_FILES['logo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                $target_dir = "../assets/images/partners/";
                $file_name = time() . '_' . basename($filename);
                $target_file = $target_dir . $file_name;
                if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
                    $logo_path = "assets/images/partners/" . $file_name;
                }
            }
            else {
                $error = "Invalid file type. Only standard image formats allowed.";
            }
        }

        $stmt = $pdo->prepare("UPDATE partners SET name = ?, link = ?, logo = ? WHERE id = ?");
        if ($stmt->execute([$name, $link, $logo_path, $id])) {
            header("Location: partners.php?msg=updated");
            exit;
        }
        else {
            $error = "Failed to update partner.";
        }
    }
    // Add New Partner
    else {
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $allowed = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'svg');
            $filename = $_FILES['logo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                $target_dir = "../assets/images/partners/";
                if (!file_exists($target_dir))
                    mkdir($target_dir, 0777, true);

                $file_name = time() . '_' . basename($filename);
                $target_file = $target_dir . $file_name;

                if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
                    $logo_path = "assets/images/partners/" . $file_name;
                    $stmt = $pdo->prepare("INSERT INTO partners (name, link, logo) VALUES (?, ?, ?)");
                    if ($stmt->execute([$name, $link, $logo_path])) {
                        header("Location: partners.php?msg=added");
                        exit;
                    }
                    else {
                        $error = "Database error.";
                    }
                }
                else {
                    $error = "Failed to move uploaded file.";
                }
            }
            else {
                $error = "Invalid file type. Only standard image formats allowed.";
            }
        }
        else {
            $error = "Please select a logo image.";
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("SELECT logo FROM partners WHERE id = ?");
    $stmt->execute([$id]);
    $partner = $stmt->fetch();

    if ($partner) {
        $stmt = $pdo->prepare("DELETE FROM partners WHERE id = ?");
        $stmt->execute([$id]);
        if (file_exists("../" . $partner['logo'])) {
            unlink("../" . $partner['logo']);
        }
        header("Location: partners.php?msg=deleted");
        exit;
    }
}

// Handle Edit Mode
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM partners WHERE id = ?");
    $stmt->execute([$id]);
    $partner_to_edit = $stmt->fetch();
    if ($partner_to_edit) {
        $edit_mode = true;
    }
}

// Handle Messages
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'added')
        $success = "Partner added successfully!";
    if ($_GET['msg'] == 'updated')
        $success = "Partner updated successfully!";
    if ($_GET['msg'] == 'deleted')
        $success = "Partner deleted successfully!";
}

include 'includes/header.php';

// Fetch Partners
$partners = $pdo->query("SELECT * FROM partners ORDER BY created_at DESC")->fetchAll();
?>

<style>
    .card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        padding: 30px;
        margin-bottom: 30px;
    }
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 2fr 1fr auto;
        gap: 20px;
        align-items: end;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #555;
    }
    .form-group input {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.3s;
    }
    .form-group input:focus {
        border-color: var(--primary-color, #007bff);
        outline: none;
    }
    .btn {
        padding: 12px 25px;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .table-container {
        overflow-x: auto;
    }
    .custom-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 10px;
    }
    .custom-table th {
        text-align: left;
        padding: 15px;
        color: #888;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.5px;
    }
    .custom-table tbody tr {
        background: #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        transition: transform 0.2s;
    }
    .custom-table tbody tr:hover {
        transform: scale(1.005);
        background: #fcfcfc;
    }
    .custom-table td {
        padding: 15px;
        vertical-align: middle;
        border-top: 1px solid #eee;
        border-bottom: 1px solid #eee;
    }
    .custom-table td:first-child {
        border-left: 1px solid #eee;
        border-top-left-radius: 8px;
        border-bottom-left-radius: 8px;
    }
    .custom-table td:last-child {
        border-right: 1px solid #eee;
        border-top-right-radius: 8px;
        border-bottom-right-radius: 8px;
    }
    .action-btn {
        width: 35px;
        height: 35px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        text-decoration: none;
        transition: background 0.2s;
        margin-left: 5px;
    }
    .action-btn.edit { background: #e3f2fd; color: #1976d2; }
    .action-btn.edit:hover { background: #bbdefb; }
    .action-btn.delete { background: #ffebee; color: #c62828; }
    .action-btn.delete:hover { background: #ffcdd2; }
    
    @media (max-width: 900px) {
        .form-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="header">
    <h2>Manage Review Partners</h2>
    <?php if ($edit_mode): ?>
        <a href="partners.php" class="btn" style="background: #6c757d; color: white;">Cancel Edit</a>
    <?php
endif; ?>
</div>

<?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php
endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php
endif; ?>

<div class="card">
    <h3 style="margin-top: 0; margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
        <?php echo $edit_mode ? 'Edit Partner Details' : 'Add New Review Partner'; ?>
    </h3>
    
    <form method="POST" enctype="multipart/form-data" class="form-grid">
        <?php if ($edit_mode): ?>
            <input type="hidden" name="update_id" value="<?php echo $partner_to_edit['id']; ?>">
            <input type="hidden" name="existing_logo" value="<?php echo $partner_to_edit['logo']; ?>">
        <?php
endif; ?>
        
        <div class="form-group">
            <label>Partner Name</label>
            <input type="text" name="name" value="<?php echo $edit_mode ? htmlspecialchars($partner_to_edit['name']) : ''; ?>" placeholder="E.g. TripAdvisor" required>
        </div>
        
        <div class="form-group">
            <label>Profile Link (URL)</label>
            <input type="url" name="link" value="<?php echo $edit_mode ? htmlspecialchars($partner_to_edit['link']) : ''; ?>" placeholder="E.g. https://tripadvisor.com/review/..." required>
        </div>
        
        <div class="form-group">
            <label><?php echo $edit_mode ? 'Change Logo (Optional)' : 'Logo Image'; ?></label>
            <input type="file" name="logo" accept="image/*" <?php echo $edit_mode ? '' : 'required'; ?> style="padding: 9px;">
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn" style="background: var(--primary-color, #007bff); color: white; margin-bottom: 2px;">
                <?php echo $edit_mode ? '<i class="fas fa-save"></i> Update Partner' : '<i class="fas fa-plus"></i> Add Partner'; ?>
            </button>
        </div>
    </form>
</div>

<div class="card">
    <h3 style="margin-top: 0; margin-bottom: 20px;">Current Partners List</h3>
    <div class="table-container">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Logo Preview</th>
                    <th>Partner Name</th>
                    <th>Profile Link</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($partners as $partner): ?>
                <tr style="<?php echo($edit_mode && $partner_to_edit['id'] == $partner['id']) ? 'background: #e3f2fd !important; transform: scale(1);' : ''; ?>">
                    <td>
                        <div style="width: 100px; height: 50px; display: flex; align-items: center; justify-content: center; background: #f8f9fa; border-radius: 6px; padding: 5px; border: 1px solid #eee;">
                            <img src="../<?php echo htmlspecialchars($partner['logo']); ?>" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                        </div>
                    </td>
                    <td style="font-weight: 600; color: #333; font-size: 15px;"><?php echo htmlspecialchars($partner['name']); ?></td>
                    <td>
                        <a href="<?php echo htmlspecialchars($partner['link']); ?>" target="_blank" style="color: #007bff; text-decoration: none; font-size: 13px; display: flex; align-items: center; gap: 5px;">
                            <i class="fas fa-external-link-alt" style="font-size: 10px;"></i> 
                            <?php echo htmlspecialchars(mb_strimwidth($partner['link'], 0, 40, "...")); ?>
                        </a>
                    </td>
                    <td style="text-align: right;">
                        <a href="?edit=<?php echo $partner['id']; ?>" class="action-btn edit" title="Edit"><i class="fas fa-pencil-alt"></i></a>
                        <a href="?delete=<?php echo $partner['id']; ?>" class="action-btn delete" onclick="return confirm('Are you sure you want to delete strictly this partner?');" title="Delete"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php
endforeach; ?>
                <?php if (count($partners) == 0): ?>
                <tr>
                    <td colspan="4" style="text-align: center; padding: 40px; color: #999;">
                        <i class="fas fa-handshake" style="font-size: 40px; margin-bottom: 10px; color: #eee;"></i><br>
                        No review partners added yet. Add one above!
                    </td>
                </tr>
                <?php
endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
