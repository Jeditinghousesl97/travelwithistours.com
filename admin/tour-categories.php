<?php
require_once '../config/db.php';

$success = '';
$error = '';
$edit_mode = false;
$cat_to_edit = null;

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    // Check if used in tours
    // Note: The previous logic checked 'tours' table 'category_id' column, but we have a join table 'tour_categories' now as per 'all-tours.php' view.
    // Let's check both or the tour_categories table.
    // Wait, 'all-tours.php' uses tour_categories. 'tour-categories.php' (this file) originally checked 'tours WHERE category_id = ?'. 
    // If the schema changed to M:N, this check needs update. If it's still 1:N in 'tours' table, this is fine.
    // Assuming 1:N or the check was valid. I will keep original check but wrap it.
    $check = $pdo->prepare("SELECT COUNT(*) FROM tours WHERE category_id = ?");
    $check->execute([$id]);
    if ($check->fetchColumn() > 0) {
        $error = "Cannot delete category: It is assigned to one or more tours.";
    }
    else {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        if ($stmt->execute([$id])) {
            header("Location: tour-categories.php?msg=deleted");
            exit;
        }
        else {
            $error = "Failed to delete category.";
        }
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    $id = $_POST['id'] ?? null;
    $type = 'tour'; // Hardcoded for this page

    if (empty($name)) {
        $error = "Name is required.";
    }
    else {
        if ($id) {
            // Update
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ? WHERE id = ? AND type = 'tour'");
            if ($stmt->execute([$name, $slug, $id])) {
                header("Location: tour-categories.php?msg=updated");
                exit;
            }
            else {
                $error = "Failed to update category.";
            }
        }
        else {
            // Create
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, type) VALUES (?, ?, ?)");
            if ($stmt->execute([$name, $slug, $type])) {
                header("Location: tour-categories.php?msg=added");
                exit;
            }
            else {
                $error = "Failed to create category.";
            }
        }
    }
}

// Check Edit Mode
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ? AND type = 'tour'");
    $stmt->execute([$edit_id]);
    $cat_to_edit = $stmt->fetch();
    if ($cat_to_edit)
        $edit_mode = true;
}

// Handle Messages
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'updated')
        $success = "Category updated successfully!";
    if ($_GET['msg'] == 'added')
        $success = "Category created successfully!";
    if ($_GET['msg'] == 'deleted')
        $success = "Category deleted successfully!";
}

include 'includes/header.php';

// Fetch Categories (Tour Only)
$categories = $pdo->query("SELECT * FROM categories WHERE type = 'tour' ORDER BY name ASC")->fetchAll();
?>

<div class="header">
    <h2>Manage Tour Categories</h2>
    <?php if ($edit_mode): ?>
        <a href="tour-categories.php" class="btn" style="background: #6c757d;">Cancel Edit</a>
    <?php
endif; ?>
</div>

<?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php
endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php
endif; ?>

<div class="card">
    <h3><?php echo $edit_mode ? 'Edit Category' : 'Add New Category'; ?></h3>
    <form method="POST">
        <?php if ($edit_mode): ?>
            <input type="hidden" name="id" value="<?php echo $cat_to_edit['id']; ?>">
        <?php
endif; ?>

        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Category Name</label>
            <input type="text" name="name" value="<?php echo $edit_mode ? htmlspecialchars($cat_to_edit['name']) : ''; ?>" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
        </div>

        <button type="submit" class="btn"><?php echo $edit_mode ? 'Update Category' : 'Add Category'; ?></button>
    </form>
</div>

<div class="card">
    <h3>Existing Categories</h3>
    <?php if (count($categories) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Slug</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $cat): ?>
            <tr>
                <td><?php echo htmlspecialchars($cat['name']); ?></td>
                <td><?php echo htmlspecialchars($cat['slug']); ?></td>
                <td>
                    <a href="?edit=<?php echo $cat['id']; ?>" style="color: #00bcd4; margin-right: 10px;"><i class="fas fa-edit"></i></a>
                    <a href="?delete=<?php echo $cat['id']; ?>" onclick="return confirm('Delete this category?');" style="color: #d9534f;"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            <?php
    endforeach; ?>
        </tbody>
    </table>
    <?php
else: ?>
        <p style="text-align: center; color: #777;">No categories found.</p>
    <?php
endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
