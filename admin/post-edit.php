<?php
require_once '../config/db.php';
ob_start();
include 'includes/header.php';

$title = '';
$content = '';
$excerpt = '';
$status = 'published';
$thumbnail = '';
$error = '';
$post_id = $_GET['id'] ?? null;
$edit_mode = (bool)$post_id;
$category_id = '';
$is_featured = 0;

// Fetch Existing Post
if ($edit_mode) {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();
    if ($post) {
        $title = $post['title'];
        $content = $post['content'];
        $excerpt = $post['excerpt'];
        $status = $post['status'];
        $thumbnail = $post['thumbnail'];
        $category_id = $post['category_id'] ?? '';
        $is_featured = $post['is_featured'] ?? 0;
        $seo_title = $post['seo_title'] ?? '';
        $seo_description = $post['seo_description'] ?? '';
        $seo_keywords = $post['seo_keywords'] ?? '';
    }
}
else {
    $seo_title = '';
    $seo_description = '';
    $seo_keywords = '';
}

// Handle Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $excerpt = $_POST['excerpt'];
    $status = $_POST['status'];
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));

    // SEO Meta
    $seo_title = $_POST['seo_title'];
    $seo_description = $_POST['seo_description'];
    $seo_keywords = $_POST['seo_keywords'];

    // Image Upload
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
        $target_dir = "../assets/images/blog/";
        if (!file_exists($target_dir))
            mkdir($target_dir, 0777, true);

        $file_name = time() . '_' . basename($_FILES["thumbnail"]["name"]);
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["thumbnail"]["tmp_name"], $target_file)) {
            $thumbnail = "assets/images/blog/" . $file_name;
        }
    }

    $category_id = $_POST['category_id'] ?: null;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    // Auto-add columns if missing (Quick Fix - Separated to avoid partial failure)
    try {
        $pdo->query("SELECT category_id FROM posts LIMIT 1");
    }
    catch (Exception $e) {
        try {
            $pdo->exec("ALTER TABLE posts ADD COLUMN category_id INT(11) NULL");
        }
        catch (Exception $ex) {
        }
    }

    try {
        $pdo->query("SELECT is_featured FROM posts LIMIT 1");
    }
    catch (Exception $e) {
        try {
            $pdo->exec("ALTER TABLE posts ADD COLUMN is_featured TINYINT(1) DEFAULT 0");
        }
        catch (Exception $ex) {
        }
    }

    if ($edit_mode) {
        $stmt = $pdo->prepare("UPDATE posts SET title = ?, slug = ?, content = ?, excerpt = ?, status = ?, thumbnail = ?, category_id = ?, is_featured = ?, seo_title = ?, seo_description = ?, seo_keywords = ? WHERE id = ?");
        if ($stmt->execute([$title, $slug, $content, $excerpt, $status, $thumbnail, $category_id, $is_featured, $seo_title, $seo_description, $seo_keywords, $post_id])) {
            header("Location: posts.php?msg=updated");
            exit;
        }
        else {
            $error = "Failed to update post.";
        }
    }
    else {
        $author_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("INSERT INTO posts (title, slug, content, excerpt, status, thumbnail, author_id, category_id, is_featured, seo_title, seo_description, seo_keywords) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$title, $slug, $content, $excerpt, $status, $thumbnail, $author_id, $category_id, $is_featured, $seo_title, $seo_description, $seo_keywords])) {
            header("Location: posts.php?msg=created");
            exit;
        }
        else {
            $error = "Failed to create post.";
        }
    }
}
?>

<div class="header">
    <h2><?php echo $edit_mode ? 'Edit Post' : 'Add New Post'; ?></h2>
    <a href="posts.php" class="btn btn-secondary">&larr; Back to Posts</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php
endif; ?>

<form method="POST" enctype="multipart/form-data">
    <div class="post-layout-grid">
        <!-- Left Column: Content -->
        <div>
            <div class="form-group">
                <label class="form-label" style="font-size: 16px;">Post Title</label>
                <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($title); ?>" style="font-size: 18px; padding: 15px;">
            </div>

            <div class="form-group">
                <label class="form-label">Post Content</label>
                <!-- TinyMCE will attach to this textarea -->
                <textarea name="content" id="content" class="form-control" style="height: 500px;"><?php echo htmlspecialchars($content); ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Excerpt (Short Summary)</label>
                <textarea name="excerpt" class="form-control" rows="4"><?php echo htmlspecialchars($excerpt); ?></textarea>
            </div>
        </div>

        <!-- Right Column: Settings -->
        <div>
            <div class="form-section" style="position: sticky; top: 20px;">
                <h3 style="margin-top: 0; font-size: 16px;">Publish Settings</h3>
                
                <!-- Status -->
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="published" <?php echo $status == 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="draft" <?php echo $status == 'draft' ? 'selected' : ''; ?>>Draft</option>
                    </select>
                </div>

                <!-- Categories -->
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-control">
                        <option value="">-- Select Category --</option>
                        <?php
$cats = $pdo->query("SELECT * FROM categories WHERE type = 'blog' ORDER BY name ASC")->fetchAll();
foreach ($cats as $cat) {
    $selected = ($category_id == $cat['id']) ? 'selected' : '';
    echo "<option value='{$cat['id']}' $selected>{$cat['name']}</option>";
}
?>
                    </select>
                </div>

                <!-- Featured Checkbox -->
                <div class="form-group">
                     <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 600; color: #555;">
                        <input type="checkbox" name="is_featured" value="1" style="width: 18px; height: 18px; margin: 0;" <?php echo($is_featured) ? 'checked' : ''; ?>>
                        Mark as Featured
                    </label>
                </div>

                <!-- Featured Image -->
                <div class="form-group" style="border-top: 1px solid #ddd; padding-top: 20px; margin-top: 20px;">
                    <label class="form-label">Featured Image</label>
                    <?php if ($thumbnail): ?>
                        <div style="margin-bottom: 10px;">
                            <img src="../<?php echo htmlspecialchars($thumbnail); ?>" style="width: 100%; border-radius: 4px; border: 1px solid #ddd;">
                        </div>
                    <?php
endif; ?>
                    <input type="file" name="thumbnail" accept="image/*" class="form-control" style="padding: 9px;">
                    <small style="color: #666; display: block; margin-top: 5px;">Recommended: 800x600px</small>
                </div>

                <!-- SEO Section -->
                <div class="form-section" style="border: 1px solid #cce5ff; background: #f0f8ff;">
                    <h3 style="margin-top: 0; font-size: 16px; color: #004085;"><i class="fas fa-search"></i> SEO Optimizations</h3>
                    <div class="form-group">
                        <label class="form-label">SEO Meta Title</label>
                        <input type="text" name="seo_title" class="form-control" value="<?php echo htmlspecialchars($seo_title); ?>" placeholder="Leave empty for default">
                    </div>
                    <div class="form-group">
                        <label class="form-label">SEO Meta Description</label>
                        <textarea name="seo_description" class="form-control" rows="3"><?php echo htmlspecialchars($seo_description); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">SEO Keywords</label>
                        <input type="text" name="seo_keywords" class="form-control" value="<?php echo htmlspecialchars($seo_keywords); ?>">
                    </div>
                </div>
                
                <hr style="margin: 20px 0; border-top: 1px solid #eee;">
                
                <button type="submit" class="btn btn-success" style="width: 100%; font-size: 16px; padding: 12px;">
                    <?php echo $edit_mode ? 'Update Post' : 'Publish Post'; ?>
                </button>
            </div>
        </div>
    </div>
</form>

<style>
    .post-layout-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
    }
    
    @media (max-width: 900px) {
        .post-layout-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<!-- TinyMCE Script (Switched to cdnjs to avoid API key requirement) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: '#content',
        height: 500,
        plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
        branding: false,
        promotion: false,
        elementpath: false,
        images_upload_url: 'upload_image.php',
        automatic_uploads: true,
        images_upload_handler: (blobInfo, progress) => new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('file', blobInfo.blob(), blobInfo.filename());

            fetch('upload_image.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP Error: ' + response.status);
                }
                return response.json();
            })
            .then(json => {
                if (!json || typeof json.location != 'string') {
                    reject('Invalid JSON: ' + JSON.stringify(json));
                    return;
                }
                resolve(json.location);
            })
            .catch(error => {
                reject('Image upload failed: ' + error.message);
            });
        })
    });
</script>

<?php include 'includes/footer.php'; ?>
