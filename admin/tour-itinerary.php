<?php
require_once '../config/db.php';

$tour_id = $_GET['tour_id'] ?? null;
if (!$tour_id) {
    header("Location: tours.php");
    exit;
}

// Fetch Tour Info
$stmt = $pdo->prepare("SELECT name FROM tours WHERE id = ?");
$stmt->execute([$tour_id]);
$tour = $stmt->fetch();
if (!$tour)
    die("Tour not found.");

$success = '';
$error = '';
$edit_mode = false;
$item_to_edit = null;

// Handle Delete
if (isset($_GET['delete'])) {
    $del_id = $_GET['delete'];
    $stmt = $pdo->prepare("SELECT image_1, image_2 FROM tour_itineraries WHERE id = ?");
    $stmt->execute([$del_id]);
    $imgs = $stmt->fetch();

    if ($imgs) {
        $stmt = $pdo->prepare("DELETE FROM tour_itineraries WHERE id = ?");
        if ($stmt->execute([$del_id])) {
            if ($imgs['image_1'] && file_exists("../" . $imgs['image_1']))
                unlink("../" . $imgs['image_1']);
            if ($imgs['image_2'] && file_exists("../" . $imgs['image_2']))
                unlink("../" . $imgs['image_2']);
            header("Location: tour-itinerary.php?tour_id=$tour_id&msg=deleted");
            exit;
        }
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $day_number = $_POST['day_number'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $display_order = $_POST['display_order'];
    $id = $_POST['id'] ?? null;

    $image_1_path = null;
    $image_2_path = null;
    $target_dir = "../assets/images/itinerary/";
    if (!file_exists($target_dir))
        mkdir($target_dir, 0777, true);

    // Image 1
    if (isset($_FILES['image_1']) && $_FILES['image_1']['error'] == 0) {
        $fn = time() . '_1_' . basename($_FILES["image_1"]["name"]);
        if (move_uploaded_file($_FILES["image_1"]["tmp_name"], $target_dir . $fn)) {
            $image_1_path = "assets/images/itinerary/" . $fn;
        }
    }

    // Image 2
    if (isset($_FILES['image_2']) && $_FILES['image_2']['error'] == 0) {
        $fn = time() . '_2_' . basename($_FILES["image_2"]["name"]);
        if (move_uploaded_file($_FILES["image_2"]["tmp_name"], $target_dir . $fn)) {
            $image_2_path = "assets/images/itinerary/" . $fn;
        }
    }

    if ($id) {
        // Update
        $sql = "UPDATE tour_itineraries SET day_number=?, title=?, description=?, display_order=?";
        $params = [$day_number, $title, $description, $display_order];

        if ($image_1_path) {
            $sql .= ", image_1=?";
            $params[] = $image_1_path;
        }
        if ($image_2_path) {
            $sql .= ", image_2=?";
            $params[] = $image_2_path;
        }

        $sql .= " WHERE id=?";
        $params[] = $id;

        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            header("Location: tour-itinerary.php?tour_id=$tour_id&msg=updated");
            exit;
        }
        else {
            $error = "Failed to update.";
        }

    }
    else {
        // Create
        $stmt = $pdo->prepare("INSERT INTO tour_itineraries (tour_id, day_number, title, description, display_order, image_1, image_2) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$tour_id, $day_number, $title, $description, $display_order, $image_1_path, $image_2_path])) {
            header("Location: tour-itinerary.php?tour_id=$tour_id&msg=added");
            exit;
        }
        else {
            $error = "Failed to add itinerary item.";
        }
    }
}

// Check Edit
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM tour_itineraries WHERE id = ?");
    $stmt->execute([$edit_id]);
    $item_to_edit = $stmt->fetch();
    if ($item_to_edit)
        $edit_mode = true;
}

// Messages
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'added')
        $success = "Day added successfully!";
    if ($_GET['msg'] == 'updated')
        $success = "Day updated successfully!";
    if ($_GET['msg'] == 'deleted')
        $success = "Day deleted successfully!";
}

include 'includes/header.php';

// Fetch Items
$items = $pdo->prepare("SELECT * FROM tour_itineraries WHERE tour_id = ? ORDER BY display_order ASC");
$items->execute([$tour_id]);
$itinerary = $items->fetchAll();
?>

<div class="header">
    <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
        <h2>Itinerary for: <?php echo htmlspecialchars($tour['name']); ?></h2>
        <a href="tour-edit.php?id=<?php echo $tour_id; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Tour
        </a>
    </div>
</div>

<?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php
endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php
endif; ?>

<div class="card">
    <div class="form-section">
        <h3><?php echo $edit_mode ? 'Edit Day' : 'Add New Day'; ?></h3>
        <form method="POST" enctype="multipart/form-data">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="id" value="<?php echo $item_to_edit['id']; ?>">
            <?php
endif; ?>
            
            <div class="form-grid" style="grid-template-columns: 1fr 2fr 1fr;">
                <div class="form-group">
                    <label class="form-label">Day Number</label>
                    <input type="text" name="day_number" class="form-control" placeholder="e.g. 01" value="<?php echo $edit_mode ? htmlspecialchars($item_to_edit['day_number']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Title / Location</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Arrival in Negombo" value="<?php echo $edit_mode ? htmlspecialchars($item_to_edit['title']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Sort Order</label>
                    <input type="number" name="display_order" class="form-control" value="<?php echo $edit_mode ? htmlspecialchars($item_to_edit['display_order']) : '0'; ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" rows="5" class="form-control" required><?php echo $edit_mode ? htmlspecialchars($item_to_edit['description']) : ''; ?></textarea>
            </div>

            <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                <div class="form-group">
                    <label class="form-label">Image 1</label>
                    <?php if ($edit_mode && $item_to_edit['image_1']): ?>
                        <div style="margin-bottom: 5px;">
                            <img src="../<?php echo $item_to_edit['image_1']; ?>" height="60" style="border-radius: 4px; border: 1px solid #ddd;">
                        </div>
                    <?php
endif; ?>
                    <input type="file" name="image_1" accept="image/*" class="form-control" style="padding: 9px;">
                </div>
                <div class="form-group">
                    <label class="form-label">Image 2</label>
                    <?php if ($edit_mode && $item_to_edit['image_2']): ?>
                        <div style="margin-bottom: 5px;">
                            <img src="../<?php echo $item_to_edit['image_2']; ?>" height="60" style="border-radius: 4px; border: 1px solid #ddd;">
                        </div>
                    <?php
endif; ?>
                    <input type="file" name="image_2" accept="image/*" class="form-control" style="padding: 9px;">
                </div>
            </div>

            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button type="submit" class="btn">
                    <i class="fas fa-save"></i> <?php echo $edit_mode ? 'Update Day' : 'Add Day'; ?>
                </button>
                <?php if ($edit_mode): ?>
                    <a href="tour-itinerary.php?tour_id=<?php echo $tour_id; ?>" class="btn btn-secondary">Cancel</a>
                <?php
endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <h3>Current Itinerary</h3>
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Images</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itinerary as $item): ?>
                <tr>
                    <td style="font-weight: 600; color: #00bcd4;"><?php echo htmlspecialchars($item['day_number']); ?></td>
                    <td style="font-weight: 600;"><?php echo htmlspecialchars($item['title']); ?></td>
                    <td style="color: #666;"><?php echo htmlspecialchars(mb_strimwidth($item['description'], 0, 100, "...")); ?></td>
                    <td>
                        <div style="display: flex; gap: 5px;">
                            <?php if ($item['image_1']): ?>
                                <img src="../<?php echo $item['image_1']; ?>" width="40" height="40" style="object-fit: cover; border-radius: 4px; border: 1px solid #eee;">
                            <?php
    endif; ?>
                            <?php if ($item['image_2']): ?>
                                <img src="../<?php echo $item['image_2']; ?>" width="40" height="40" style="object-fit: cover; border-radius: 4px; border: 1px solid #eee;">
                            <?php
    endif; ?>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; gap: 5px;">
                            <a href="?tour_id=<?php echo $tour_id; ?>&edit=<?php echo $item['id']; ?>" class="btn" style="padding: 5px 10px; font-size: 12px;" title="Edit"><i class="fas fa-edit"></i></a>
                            <a href="?tour_id=<?php echo $tour_id; ?>&delete=<?php echo $item['id']; ?>" onclick="return confirm('Delete this day?');" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;" title="Delete"><i class="fas fa-trash"></i></a>
                        </div>
                    </td>
                </tr>
                <?php
endforeach; ?>
                <?php if (count($itinerary) == 0): ?>
                    <tr><td colspan="5" style="text-align: center; padding: 30px; color: #777;">No itinerary days added yet.</td></tr>
                <?php
endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
