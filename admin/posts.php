<?php
// posts.php - List all blog posts
require_once '../config/db.php';

// Robust Auto-fix for missing columns
$columns_to_check = [
    'author_id' => "ALTER TABLE posts ADD COLUMN author_id INT(11) NULL",
    'slug' => "ALTER TABLE posts ADD COLUMN slug VARCHAR(255) NOT NULL",
    'excerpt' => "ALTER TABLE posts ADD COLUMN excerpt TEXT NULL",
    'status' => "ALTER TABLE posts ADD COLUMN status ENUM('published', 'draft') DEFAULT 'published'",
    'thumbnail' => "ALTER TABLE posts ADD COLUMN thumbnail VARCHAR(255) NULL"
];

foreach ($columns_to_check as $col => $sql) {
    try {
        $pdo->query("SELECT $col FROM posts LIMIT 1");
    }
    catch (Exception $e) {
        try {
            $pdo->exec($sql);
        }
        catch (Exception $ex) {
        // Ignore if alter fails (e.g. table locks), but it shouldn't
        }
    }
}

$msg = '';
$error = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    if ($stmt->execute([$id])) {
        header("Location: posts.php?msg=deleted");
        exit;
    }
    else {
        $error = "Failed to delete post.";
    }
}

// Handle Duplicate
if (isset($_GET['duplicate'])) {
    $id = $_GET['duplicate'];
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($post) {
        unset($post['id']);
        unset($post['created_at']);
        $post['title'] .= " (Copy)";
        $post['slug'] .= "-copy-" . time();
        $post['status'] = 'draft'; // Set duplicated post as draft

        $columns = implode(", ", array_keys($post));
        $placeholders = implode(", ", array_fill(0, count($post), "?"));

        $sql = "INSERT INTO posts ($columns) VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute(array_values($post))) {
            header("Location: posts.php?msg=duplicated");
            exit;
        }
        else {
            $error = "Failed to duplicate post.";
        }
    }
}

// Handle Messages from Redirects
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'deleted')
        $msg = "Post deleted successfully!";
    if ($_GET['msg'] == 'duplicated')
        $msg = "Post duplicated successfully as Draft!";
    if ($_GET['msg'] == 'created')
        $msg = "Post created successfully!";
    if ($_GET['msg'] == 'updated')
        $msg = "Post updated successfully!";
}

include 'includes/header.php';

// Fetch Posts
$stmt = $pdo->query("SELECT p.*, u.username FROM posts p LEFT JOIN users u ON p.author_id = u.id ORDER BY p.created_at DESC");
$posts = $stmt->fetchAll();
?>

<div class="header">
    <h2>Manage Blog Posts</h2>
    <a href="post-edit.php" class="btn"><i class="fas fa-plus"></i> Add New Post</a>
</div>

<?php if (!empty($msg)): ?>
    <div class="alert alert-success"><?php echo $msg; ?></div>
<?php
endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php
endif; ?>

<div class="card">
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="text-align: left; border-bottom: 2px solid #eee;">
                <th style="padding: 10px;">Title</th>
                <th style="padding: 10px;">Author</th>
                <th style="padding: 10px;">Status</th>
                <th style="padding: 10px;">Date</th>
                <th style="padding: 10px;">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (count($posts) > 0): ?>
            <?php foreach ($posts as $post): ?>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #eee;">
                    <?php if ($post['thumbnail']): ?>
                        <img src="../<?php echo htmlspecialchars($post['thumbnail']); ?>" style="width: 40px; height: 40px; object-fit: cover; vertical-align: middle; margin-right: 10px; border-radius: 4px;">
                    <?php
        endif; ?>
                    <strong><?php echo htmlspecialchars($post['title']); ?></strong>
                </td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><?php echo htmlspecialchars($post['username'] ?? 'Admin'); ?></td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;">
                    <span style="padding: 4px 10px; border-radius: 12px; font-size: 12px; background: <?php echo $post['status'] == 'published' ? '#d4edda' : '#fff3cd'; ?>; color: <?php echo $post['status'] == 'published' ? '#155724' : '#856404'; ?>;">
                        <?php echo ucfirst($post['status']); ?>
                    </span>
                </td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><?php echo date('M d, Y', strtotime($post['created_at'])); ?></td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;">
                    <a href="post-edit.php?id=<?php echo $post['id']; ?>" style="color: #00bcd4; margin-right: 10px;"><i class="fas fa-edit"></i></a>
                    <a href="?duplicate=<?php echo $post['id']; ?>" style="color: #ffc107; margin-right: 10px;" title="Duplicate"><i class="fas fa-copy"></i></a>
                    <a href="?delete=<?php echo $post['id']; ?>" style="color: #d9534f;" onclick="return confirm('Delete this post?');"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            <?php
    endforeach; ?>
        <?php
else: ?>
            <tr><td colspan="5" style="padding: 20px; text-align: center;">No posts found.</td></tr>
        <?php
endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
