<?php
require_once '../config/db.php';

// Handle Duplicate
if (isset($_GET['duplicate'])) {
    $id = $_GET['duplicate'];

    // 1. Fetch Original Tour
    $stmt = $pdo->prepare("SELECT * FROM tours WHERE id = ?");
    $stmt->execute([$id]);
    $tour = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($tour) {
        // Remove ID and Created At
        unset($tour['id']);
        unset($tour['created_at']);

        // Modify Name
        $tour['name'] .= " (Copy)";
        $tour['slug'] .= "-copy-" . time();
        $tour['is_featured'] = 0; // Reset featured status

        // 2. Insert Duplicate Tour
        // Dynamically build INSERT query based on keys
        $columns = implode(", ", array_keys($tour));
        $placeholders = implode(", ", array_fill(0, count($tour), "?"));

        $sql = "INSERT INTO tours ($columns) VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);

        if ($stmt->execute(array_values($tour))) {
            $new_tour_id = $pdo->lastInsertId();

            // 3. Duplicate Categories
            $cats = $pdo->prepare("SELECT category_id FROM tour_categories WHERE tour_id = ?");
            $cats->execute([$id]);
            $categories = $cats->fetchAll(PDO::FETCH_COLUMN);

            $insCat = $pdo->prepare("INSERT INTO tour_categories (tour_id, category_id) VALUES (?, ?)");
            foreach ($categories as $cat_id) {
                $insCat->execute([$new_tour_id, $cat_id]);
            }

            // 4. Duplicate Itinerary
            $itin = $pdo->prepare("SELECT * FROM tour_itineraries WHERE tour_id = ?");
            $itin->execute([$id]);
            $itineraries = $itin->fetchAll(PDO::FETCH_ASSOC);

            $insItin = $pdo->prepare("INSERT INTO tour_itineraries (tour_id, day_number, title, description, image_1, image_2, display_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($itineraries as $day) {
                $insItin->execute([
                    $new_tour_id,
                    $day['day_number'],
                    $day['title'],
                    $day['description'],
                    $day['image_1'],
                    $day['image_2'],
                    $day['display_order']
                ]);
            }

            header("Location: tours.php?msg=duplicated");
            exit;
        }
    }
}

// Handle Delete (Logic First)
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    // Fetch image to delete
    $stmt = $pdo->prepare("SELECT thumbnail FROM tours WHERE id = ?");
    $stmt->execute([$id]);
    $tour = $stmt->fetch();

    if ($tour) {
        // Delete pivot entries
        $pdo->prepare("DELETE FROM tour_categories WHERE tour_id = ?")->execute([$id]);

        $stmt = $pdo->prepare("DELETE FROM tours WHERE id = ?");
        if ($stmt->execute([$id])) {
            if ($tour['thumbnail'] && file_exists("../" . $tour['thumbnail'])) {
                unlink("../" . $tour['thumbnail']);
            }
            header("Location: tours.php?msg=deleted");
            exit;
        }
    }
}

// Handle Messages
$success = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'added')
        $success = "Tour created successfully!";
    if ($_GET['msg'] == 'deleted')
        $success = "Tour deleted successfully!";
    if ($_GET['msg'] == 'duplicated')
        $success = "Tour duplicated successfully!";
}

include 'includes/header.php';

// Fetch Tours with their categories
// GROUP_CONCAT is powerful for this listing View
$sql = "SELECT t.*, GROUP_CONCAT(c.name SEPARATOR ', ') as category_names 
        FROM tours t 
        LEFT JOIN tour_categories tc ON t.id = tc.tour_id 
        LEFT JOIN categories c ON tc.category_id = c.id 
        GROUP BY t.id 
        ORDER BY t.created_at DESC";
$tours = $pdo->query($sql)->fetchAll();
?>

<div class="header">
    <h2>Manage Tours</h2>
    <div style="display: flex; gap: 10px;">
        <a href="tour-categories.php" class="btn" style="background: #17a2b8;"><i class="fas fa-tags"></i> Categories</a>
        <a href="tour-edit.php" class="btn"><i class="fas fa-plus"></i> Add New Tour</a>
    </div>
</div>

<?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php
endif; ?>

<div class="card">
    <?php if (count($tours) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Categories</th>
                <th>Price</th>
                <th>Featured</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tours as $tour): ?>
            <tr>
                <td>
                    <?php if ($tour['thumbnail']): ?>
                        <img src="../<?php echo $tour['thumbnail']; ?>" width="60" style="border-radius: 4px;">
                    <?php
        else: ?>
                        <span style="color: #ccc;">No Image</span>
                    <?php
        endif; ?>
                </td>
                <td><?php echo htmlspecialchars($tour['name']); ?></td>
                <td>
                    <?php
        if ($tour['category_names']) {
            // Create badges for categories
            $cats = explode(', ', $tour['category_names']);
            foreach ($cats as $cat) {
                echo '<span style="background: #eee; padding: 2px 6px; border-radius: 4px; font-size: 11px; margin-right: 4px; display: inline-block;">' . htmlspecialchars($cat) . '</span>';
            }
        }
        else {
            echo '<span style="color: #999; font-size: 12px; font-style: italic;">Uncategorized</span>';
        }
?>
                </td>
                <td><?php echo htmlspecialchars($tour['price']); ?></td>
                <td>
                    <?php if ($tour['is_featured']): ?>
                        <span style="background: #28a745; color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 11px;">Yes</span>
                    <?php
        else: ?>
                        <span style="color: #ccc;">No</span>
                    <?php
        endif; ?>
                </td>
                <td>
                    <a href="?duplicate=<?php echo $tour['id']; ?>" onclick="return confirm('Duplicate this tour?');" style="color: #ff9800; margin-right: 10px;" title="Duplicate"><i class="fas fa-copy"></i></a>
                    <a href="tour-edit.php?id=<?php echo $tour['id']; ?>" style="color: #00bcd4; margin-right: 10px;" title="Edit"><i class="fas fa-edit"></i></a>
                    <a href="?delete=<?php echo $tour['id']; ?>" onclick="return confirm('Delete this tour?');" style="color: #d9534f;" title="Delete"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            <?php
    endforeach; ?>
        </tbody>
    </table>
    <?php
else: ?>
        <p style="text-align: center; color: #777;">No tours found. <a href="tour-edit.php">Add your first tour</a>.</p>
    <?php
endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
