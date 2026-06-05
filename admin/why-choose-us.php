<?php
require_once '../config/db.php';

// Create table if it doesn't exist and insert dummy data
try {
    $sql = "CREATE TABLE IF NOT EXISTS why_choose_us (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        icon VARCHAR(100),
        bg_color VARCHAR(50),
        icon_color VARCHAR(50),
        display_order INT DEFAULT 0,
        status TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);

    // Check if empty, insert initial data
    $stmt = $pdo->query("SELECT COUNT(*) FROM why_choose_us");
    $count = $stmt->fetchColumn();
    if ($count == 0) {
        $insert = "INSERT INTO why_choose_us (title, description, icon, bg_color, icon_color, display_order) VALUES
        ('Price match guarantee', 'Amazing holiday package deals in Sri Lanka at un-matchable rates.', 'fas fa-tags', '#f3e5f5', '#8e24aa', 1),
        ('Proven experience', 'The GPS Lanka Travels team comprises of locals who are well experienced in the field.', 'far fa-compass', '#e0f7fa', '#006064', 2),
        ('Personal consultant', 'Our friendly team of consultants offer personalized services to clients.', 'far fa-lightbulb', '#e8eaf6', '#1a237e', 3),
        ('24 Hour Ground Support', 'We are at your service 24 hours a day to help with any concerns.', 'fas fa-mobile-alt', '#e0f2f1', '#004d40', 4),
        ('Fair Booking Conditions', 'The bookings policy is prepared with utmost concern for our guests.', 'fas fa-passport', '#fce4ec', '#880e4f', 5),
        ('Secure Payment Centre', 'We use a safe and secure financial platform to confirm all your bookings.', 'fas fa-shield-alt', '#f1f8e9', '#33691e', 6)";
        $pdo->exec($insert);
    }
}
catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$success = '';
$error = '';
$edit_mode = false;
$item_to_edit = null;

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM why_choose_us WHERE id = ?");
    if ($stmt->execute([$id])) {
        header("Location: why-choose-us.php?msg=deleted");
        exit;
    }
    else {
        $error = "Failed to delete item.";
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $icon = $_POST['icon'] ?? 'fas fa-check';
    $bg_color = $_POST['bg_color'] ?? '#f0f0f0';
    $icon_color = $_POST['icon_color'] ?? '#333333';
    $display_order = $_POST['display_order'] ?? 0;

    $id = $_POST['id'] ?? null;

    if ($id) {
        // Update
        $stmt = $pdo->prepare("UPDATE why_choose_us SET title=?, description=?, icon=?, bg_color=?, icon_color=?, display_order=? WHERE id=?");
        $result = $stmt->execute([$title, $description, $icon, $bg_color, $icon_color, $display_order, $id]);

        if ($result) {
            header("Location: why-choose-us.php?msg=updated");
            exit;
        }
        else {
            $error = "Database error on update.";
        }
    }
    else {
        // Create
        $stmt = $pdo->prepare("INSERT INTO why_choose_us (title, description, icon, bg_color, icon_color, display_order) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$title, $description, $icon, $bg_color, $icon_color, $display_order])) {
            header("Location: why-choose-us.php?msg=added");
            exit;
        }
        else {
            $error = "Database error on insert.";
        }
    }
}

// Check for Edit Mode
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM why_choose_us WHERE id = ?");
    $stmt->execute([$edit_id]);
    $item_to_edit = $stmt->fetch();
    if ($item_to_edit) {
        $edit_mode = true;
    }
}

// Handle Messages
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'updated')
        $success = "Section item updated successfully!";
    if ($_GET['msg'] == 'added')
        $success = "Section item added successfully!";
    if ($_GET['msg'] == 'deleted')
        $success = "Section item deleted successfully!";
}

include 'includes/header.php';

// Fetch items
$items = $pdo->query("SELECT * FROM why_choose_us ORDER BY display_order ASC")->fetchAll();
?>

<div class="header">
    <h2>Manage "Why Choose Us" Section</h2>
    <?php if ($edit_mode): ?>
        <a href="why-choose-us.php" class="btn" style="background: #6c757d;">Cancel Edit</a>
    <?php
endif; ?>
</div>

<?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php
endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php
endif; ?>

<div class="card">
    <h3><?php echo $edit_mode ? 'Edit Item' : 'Add New Item'; ?></h3>
    <form method="POST">
        <?php if ($edit_mode): ?>
            <input type="hidden" name="id" value="<?php echo $item_to_edit['id']; ?>">
        <?php
endif; ?>

        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Title</label>
            <input type="text" name="title" value="<?php echo $edit_mode ? htmlspecialchars($item_to_edit['title']) : ''; ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" required>
        </div>
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Description</label>
            <textarea name="description" rows="3" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" required><?php echo $edit_mode ? htmlspecialchars($item_to_edit['description']) : ''; ?></textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">FontAwesome Icon Class (e.g., fas fa-tags)</label>
                <input type="text" name="icon" value="<?php echo $edit_mode ? htmlspecialchars($item_to_edit['icon']) : 'fas fa-star'; ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" required>
                <small style="color: #666; margin-top: 5px; display: block;">Find icons at <a href="https://fontawesome.com/v5/search" target="_blank" style="color: #00bcd4;">FontAwesome 5</a></small>
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Display Order</label>
                <input type="number" name="display_order" value="<?php echo $edit_mode ? htmlspecialchars($item_to_edit['display_order']) : '0'; ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Icon Background Color</label>
                <input type="color" name="bg_color" value="<?php echo $edit_mode ? htmlspecialchars($item_to_edit['bg_color']) : '#f3e5f5'; ?>" style="padding: 5px; border: 1px solid #ddd; border-radius: 4px; height: 40px; width: 100px;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Icon Text Color</label>
                <input type="color" name="icon_color" value="<?php echo $edit_mode ? htmlspecialchars($item_to_edit['icon_color']) : '#8e24aa'; ?>" style="padding: 5px; border: 1px solid #ddd; border-radius: 4px; height: 40px; width: 100px;">
            </div>
        </div>
        
        <button type="submit" class="btn" style="padding: 12px 24px; font-size: 16px;">
            <?php echo $edit_mode ? 'Update Item' : 'Add Item'; ?>
        </button>
    </form>
</div>

<div class="card">
    <h3>Existing Items</h3>
    <?php if (count($items) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Order</th>
                <th>Icon</th>
                <th>Title</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?php echo $item['display_order']; ?></td>
                <td>
                    <div style="width: 40px; height: 40px; background: <?php echo htmlspecialchars($item['bg_color']); ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: <?php echo htmlspecialchars($item['icon_color']); ?>; margin: 0 auto;">
                        <i class="<?php echo htmlspecialchars($item['icon']); ?>" style="font-size: 16px;"></i>
                    </div>
                </td>
                <td><?php echo htmlspecialchars($item['title']); ?></td>
                <td><?php echo htmlspecialchars(mb_strimwidth($item['description'], 0, 50, "...")); ?></td>
                <td>
                    <a href="?edit=<?php echo $item['id']; ?>" style="color: #00bcd4; margin-right: 10px;"><i class="fas fa-edit"></i></a>
                    <a href="?delete=<?php echo $item['id']; ?>" onclick="return confirm('Delete this item?');" style="color: #d9534f;"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            <?php
    endforeach; ?>
        </tbody>
    </table>
    <?php
else: ?>
        <p style="text-align: center; color: #777;">No items added yet.</p>
    <?php
endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
