<?php
require_once 'includes/auth_session.php';
require_once '../config/db.php';

$tour = null;
$error = '';
$success = '';
$selected_categories = [];

// Check if editing
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM tours WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $tour = $stmt->fetch();

    if ($tour) {
        // Fetch Selected Categories
        $catStmt = $pdo->prepare("SELECT category_id FROM tour_categories WHERE tour_id = ?");
        $catStmt->execute([$tour['id']]);
        $selected_categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

// Fetch All Categories for Dropdown (Tour type only)
$catStmt = $pdo->query("SELECT * FROM categories WHERE type = 'tour' ORDER BY name ASC");
$all_categories = $catStmt->fetchAll();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $sub_heading = $_POST['sub_heading'];
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    $category_ids = $_POST['category_ids'] ?? [];

    // Meta
    $duration = $_POST['duration'];
    $tour_type = $_POST['tour_type'];
    $min_people = $_POST['min_people'];
    $location_count = $_POST['location_count'];
    $price = $_POST['price'];

    // Content
    $highlights = $_POST['highlights'];
    $map_embed_code = $_POST['map_embed_code'];
    $insightful_tips = $_POST['insightful_tips'];
    $faq_content = $_POST['faq_content'];

    $short_desc = $_POST['short_description'];
    // long_description field still exists in DB but we might use it less now
    $long_desc = $_POST['long_description'];

    // SEO Meta
    $seo_title = $_POST['seo_title'];
    $seo_description = $_POST['seo_description'];
    $seo_keywords = $_POST['seo_keywords'];

    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    // Handle Image Upload
    // Handle Image Upload
    $thumbnail = $tour['thumbnail'] ?? '';
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
        $allowed = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        $filename = $_FILES['thumbnail']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $target_dir = "../assets/images/tours/";
            if (!file_exists($target_dir))
                mkdir($target_dir, 0777, true);

            $file_name = time() . '_' . basename($filename);
            $target_file = $target_dir . $file_name;

            if (move_uploaded_file($_FILES["thumbnail"]["tmp_name"], $target_file)) {
                $thumbnail = "assets/images/tours/" . $file_name;
            }
        }
        else {
            $error = "Invalid file type. Only JPG, JPEG, PNG, GIF, & WEBP are allowed.";
        }
    }

    try {
        $pdo->beginTransaction();

        $primary_cat = !empty($category_ids) ? $category_ids[0] : null;

        if ($tour) {
            // Update
            $sql = "UPDATE tours SET category_id=?, name=?, sub_heading=?, slug=?, price=?, short_description=?, long_description=?, thumbnail=?, is_featured=?,
                    duration=?, tour_type=?, min_people=?, location_count=?, highlights=?, map_embed_code=?, insightful_tips=?, faq_content=?,
                    seo_title=?, seo_description=?, seo_keywords=?
                    WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $primary_cat, $name, $sub_heading, $slug, $price, $short_desc, $long_desc, $thumbnail, $is_featured,
                $duration, $tour_type, $min_people, $location_count, $highlights, $map_embed_code, $insightful_tips, $faq_content,
                $seo_title, $seo_description, $seo_keywords,
                $tour['id']
            ]);
            $tour_id = $tour['id'];
        }
        else {
            // Create
            $sql = "INSERT INTO tours (category_id, name, sub_heading, slug, price, short_description, long_description, thumbnail, is_featured,
                    duration, tour_type, min_people, location_count, highlights, map_embed_code, insightful_tips, faq_content,
                    seo_title, seo_description, seo_keywords) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $primary_cat, $name, $sub_heading, $slug, $price, $short_desc, $long_desc, $thumbnail, $is_featured,
                $duration, $tour_type, $min_people, $location_count, $highlights, $map_embed_code, $insightful_tips, $faq_content,
                $seo_title, $seo_description, $seo_keywords
            ]);
            $tour_id = $pdo->lastInsertId();
        }

        // Update Categories
        $delStmt = $pdo->prepare("DELETE FROM tour_categories WHERE tour_id = ?");
        $delStmt->execute([$tour_id]);

        if (!empty($category_ids)) {
            $insStmt = $pdo->prepare("INSERT INTO tour_categories (tour_id, category_id) VALUES (?, ?)");
            foreach ($category_ids as $cat_id) {
                $insStmt->execute([$tour_id, $cat_id]);
            }
        }

        $pdo->commit();

        header("Location: tour-edit.php?id=" . $tour_id . "&msg=updated");
        exit;

    }
    catch (Exception $e) {
        $pdo->rollBack();
        $error = "Failed to save tour: " . $e->getMessage();
    }
}

// Handle Messages
if (isset($_GET['msg']) && $_GET['msg'] == 'updated')
    $success = "Tour updated successfully!";
if (isset($error) && $error) {
// Keep error variable
}

include 'includes/header.php';
?>

<div class="header">
    <h2><?php echo $tour ? 'Edit Tour' : 'Add New Tour'; ?></h2>
    <div>
        <?php if ($tour): ?>
            <a href="tour-itinerary.php?tour_id=<?php echo $tour['id']; ?>" class="btn" style="background: #e040fb;"><i class="fas fa-list-ol"></i> Manage Itinerary</a>
        <?php
endif; ?>
        <a href="tours.php" class="btn btn-secondary">Back to List</a>
    </div>
</div>

<?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php
endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php
endif; ?>

<div class="card">
    <form method="POST" enctype="multipart/form-data">
        
        <!-- Section 1: Basic Information -->
        <div class="form-section">
            <h3>Basic Information</h3>
            
            <div class="form-group">
                <label class="form-label">Tour Name</label>
                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($tour['name'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Sub Heading (Top Banner Text)</label>
                <textarea name="sub_heading" class="form-control" rows="2"><?php echo htmlspecialchars($tour['sub_heading'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Categories</label>
                <div style="max-height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 15px; border-radius: 6px; background: #fff; display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px;">
                    <?php foreach ($all_categories as $cat): ?>
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; margin: 0; cursor: pointer;">
                            <input type="checkbox" name="category_ids[]" value="<?php echo $cat['id']; ?>" 
                                <?php echo in_array($cat['id'], $selected_categories) ? 'checked' : ''; ?>
                                style="width: auto; margin: 0;">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </label>
                    <?php
endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Section 2: Key Details -->
        <div class="form-section">
            <h3>Key Details</h3>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Price / Range</label>
                    <input type="text" name="price" class="form-control" value="<?php echo htmlspecialchars($tour['price'] ?? ''); ?>" placeholder="e.g. $1200 per person">
                </div>
                <div class="form-group">
                    <label class="form-label">Duration</label>
                    <input type="text" name="duration" class="form-control" placeholder="e.g. 14 Days" value="<?php echo htmlspecialchars($tour['duration'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Tour Type</label>
                    <input type="text" name="tour_type" class="form-control" placeholder="e.g. Wellness" value="<?php echo htmlspecialchars($tour['tour_type'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Min People</label>
                    <input type="text" name="min_people" class="form-control" placeholder="e.g. 2 People" value="<?php echo htmlspecialchars($tour['min_people'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Location Count</label>
                    <input type="number" name="location_count" class="form-control" value="<?php echo htmlspecialchars($tour['location_count'] ?? ''); ?>">
                </div>
                <div class="form-group" style="display: flex; align-items: flex-end;">
                     <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 600; color: #28a745; margin-bottom: 0; padding: 10px 0;">
                        <input type="checkbox" name="is_featured" style="width: 20px; height: 20px; margin: 0;" <?php echo($tour && $tour['is_featured']) ? 'checked' : ''; ?>>
                        Mark as Featured
                    </label>
                </div>
            </div>
        </div>

        <!-- Section 3: Media -->
        <div class="form-section">
            <h3>Media & Map</h3>
            
            <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                <div class="form-group">
                    <label class="form-label">Main Background Image</label>
                    <?php if (isset($tour['thumbnail']) && $tour['thumbnail']): ?>
                        <div style="margin-bottom: 10px;">
                            <img src="../<?php echo $tour['thumbnail']; ?>" height="100" style="border-radius: 6px; border: 1px solid #ddd; padding: 2px;">
                        </div>
                    <?php
endif; ?>
                    <input type="file" name="thumbnail" accept="image/*" class="form-control" style="padding: 9px;">
                    <small style="color: #666; margin-top: 5px; display: block;">Recommended Size: 1920x1080px</small>
                </div>
                
                <div class="form-group">
                     <label class="form-label">Map Embed Code (Iframe)</label>
                     <textarea name="map_embed_code" class="form-control" rows="5" style="font-family: monospace;"><?php echo htmlspecialchars($tour['map_embed_code'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Section 4: Detailed Content -->
        <div class="form-section">
            <h3>Detailed Content</h3>
            
            <div class="form-group">
                <label class="form-label">Tour Description (Before Itinerary)</label>
                <textarea name="long_description" id="long_description" class="form-control"><?php echo htmlspecialchars($tour['long_description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Journey Highlights</label>
                <textarea name="highlights" id="highlights" class="form-control"><?php echo htmlspecialchars($tour['highlights'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label" style="color: #e91e63;">Insightful Tips (Pink Box)</label>
                <textarea name="insightful_tips" id="insightful_tips" class="form-control"><?php echo htmlspecialchars($tour['insightful_tips'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">FAQ Content</label>
                <textarea name="faq_content" id="faq_content" class="form-control"><?php echo htmlspecialchars($tour['faq_content'] ?? ''); ?></textarea>
            </div>
        </div>

        <!-- Section 5: SEO Optimizations -->
        <div class="form-section">
            <h3 style="color: #007bff;"><i class="fas fa-search"></i> SEO Optimizations</h3>
            
            <div class="form-group">
                <label class="form-label">SEO Meta Title</label>
                <input type="text" name="seo_title" class="form-control" value="<?php echo htmlspecialchars($tour['seo_title'] ?? ''); ?>" placeholder="Custom title for search engines (leave empty to use tour name)">
            </div>
            
            <div class="form-group">
                <label class="form-label">SEO Meta Description</label>
                <textarea name="seo_description" class="form-control" rows="3" placeholder="Brief summary for search results..."><?php echo htmlspecialchars($tour['seo_description'] ?? ''); ?></textarea>
            </div>
            
             <div class="form-group">
                <label class="form-label">SEO Keywords</label>
                <input type="text" name="seo_keywords" class="form-control" value="<?php echo htmlspecialchars($tour['seo_keywords'] ?? ''); ?>" placeholder="hiking, wellness, sri lanka, etc.">
            </div>
        </div>

        <!-- Hidden old fields just in case -->
        <input type="hidden" name="short_description" value="<?php echo htmlspecialchars($tour['short_description'] ?? ''); ?>">

        <div style="position: sticky; bottom: 0; background: #fff; padding: 20px; border-top: 1px solid #ddd; box-shadow: 0 -5px 20px rgba(0,0,0,0.05); border-radius: 0 0 8px 8px; margin: 0 -20px -20px -20px; z-index: 10;">
             <button type="submit" class="btn" style="width: 100%; font-size: 16px; padding: 12px; font-weight: 600;">
                <i class="fas fa-save"></i> Save Tour Details
             </button>
        </div>
    </form>
</div>

<!-- TinyMCE -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js"></script>
<script>
    const editors = ['#long_description', '#highlights', '#insightful_tips', '#faq_content'];
    tinymce.init({
        selector: editors.join(','),
        height: 300,
        plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
        branding: false,
        elementpath: false,
        images_upload_handler: function (blobInfo, progress) {
            return new Promise((resolve, reject) => {
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
            });
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
