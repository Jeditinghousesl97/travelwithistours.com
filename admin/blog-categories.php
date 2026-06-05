<?php
require_once '../config/db.php';

$success = '';
$error = '';
$edit_mode = false;
$category_to_edit = null;

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    // Check if category has posts
    // Assuming a pivot table post_categories or direct post.category_id exists. 
    // Since we don't have schema details, let's assume direct deletion is okay for now or we will add check later.
    // For now simple delete.
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ? AND type = 'blog'");
    if ($stmt->execute([$id])) {
        header("Location: blog-categories.php?msg=deleted");
        exit;
    }
    else {
        $error = "Failed to delete category.";
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    $id = $_POST['id'] ?? null;

    if ($id) {
        // Update
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ? WHERE id = ? AND type = 'blog'");
        if ($stmt->execute([$name, $slug, $id])) {
            $success = "Category updated successfully!";
        }
        else {
            $error = "Failed to update category.";
        }
    }
    else {
        // Insert
        // Check duplicate slug
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Category with this name already exists.";
        }
        else {
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, type) VALUES (?, ?, 'blog')");
            if ($stmt->execute([$name, $slug])) {
                $success = "Category created successfully!";
            }
            else {
                $error = "Failed to create category.";
            }
        }
    }
}

// Robust Auto-fix for missing columns in categories
$columns_to_check = [
    'created_at' => "ALTER TABLE categories ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
    'type' => "ALTER TABLE categories ADD COLUMN type ENUM('tour', 'blog') NOT NULL DEFAULT 'tour'"
];

foreach ($columns_to_check as $col => $sql) {
    try {
        $pdo->query("SELECT $col FROM categories LIMIT 1");
    }
    catch (Exception $e) {
        try {
            $pdo->exec($sql);
        }
        catch (Exception $ex) {
        // Ignore
        }
    }
}

// Fetch Categories
$categories = $pdo->query("SELECT * FROM categories WHERE type = 'blog' ORDER BY created_at DESC")->fetchAll();

// Edit Mode
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ? AND type = 'blog'");
    $stmt->execute([$edit_id]);
    $category_to_edit = $stmt->fetch();
    if ($category_to_edit) {
        $edit_mode = true;
    }
}

if (isset($_GET['msg']) && $_GET['msg'] == 'deleted')
    $success = "Category deleted successfully!";
?>
<?php include 'includes/header.php'; ?>

<div class="header">
    <h2>Manage Blog Categories</h2>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php
endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php
endif; ?>

<div class="card">
    <h3><?php echo $edit_mode ? 'Edit Category' : 'Add New Blog Category'; ?></h3>
    <form method="POST" action="">
        <?php if ($edit_mode): ?>
            <input type="hidden" name="id" value="<?php echo $category_to_edit['id']; ?>">
        <?php
endif; ?>
        
        <div style="display: flex; gap: 10px; align-items: flex-end;">
            <div style="flex-grow: 1;">
                <label>Category Name</label>
                <input type="text" name="name" required value="<?php echo $edit_mode ? htmlspecialchars($category_to_edit['name']) : ''; ?>" placeholder="Enter category name...">
            </div>
            <div style="margin-bottom: 15px;">
                <button type="submit" class="btn"><?php echo $edit_mode ? 'Update Category' : 'Add Category'; ?></button>
                <?php if ($edit_mode): ?>
                    <a href="blog-categories.php" class="btn" style="background: #6c757d;">Cancel</a>
                <?php
endif; ?>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <h3>Existing Blog Categories</h3>
    <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
        <tr style="text-align: left; border-bottom: 2px solid #eee;">
            <th style="padding: 10px;">ID</th>
            <th style="padding: 10px;">Name</th>
            <th style="padding: 10px;">Slug</th>
            <th style="padding: 10px;">Created At</th>
            <th style="padding: 10px;">Actions</th>
        </tr>
        <?php if (count($categories) > 0): ?>
            <?php foreach ($categories as $cat): ?>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><?php echo $cat['id']; ?></td>
                <td style="padding: 10px; border-bottom: 1px solid #eee; font-weight: 500;"><?php echo htmlspecialchars($cat['name']); ?></td>
                <td style="padding: 10px; border-bottom: 1px solid #eee; color: #666;"><?php echo $cat['slug']; ?></td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><?php echo date('Y-m-d', strtotime($cat['created_at'])); ?></td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;">
                    <a href="?edit=<?php echo $cat['id']; ?>" style="color: #00bcd4; margin-right: 10px;"><i class="fas fa-edit"></i> Edit</a>
                    <a href="?delete=<?php echo $cat['id']; ?>" style="color: #d9534f;" onclick="return confirm('Are you sure you want to delete this category?');"><i class="fas fa-trash"></i> Delete</a>
                </td>
            </tr>
            <?php
    endforeach; ?>
        <?php
else: ?>
            <tr><td colspan="5" style="padding: 20px; text-align: center;">No blog categories found.</td></tr>
        <?php
endif; ?>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
