<?php
require_once '../config/db.php';

$success = '';
$error = '';

// Auto-Update Database Schema
try {
    $pdo->query("SELECT alt_text, is_featured FROM gallery LIMIT 1");
}
catch (Exception $e) {
    try {
        $pdo->exec("ALTER TABLE gallery ADD COLUMN alt_text VARCHAR(255) DEFAULT ''");
    }
    catch (Exception $ex) {
    }
    try {
        $pdo->exec("ALTER TABLE gallery ADD COLUMN is_featured TINYINT(1) DEFAULT 0");
    }
    catch (Exception $ex) {
    }
}

// Bulk upload is now handled via AJAX in ajax_gallery_upload.php
// for better stability and progress tracking.

// Handle Bulk Update (save alt text & featured status)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_gallery'])) {
    if (isset($_POST['gallery_items'])) {
        foreach ($_POST['gallery_items'] as $id => $data) {
            $alt = $data['alt_text'] ?? '';
            $featured = isset($data['is_featured']) ? 1 : 0;

            $stmt = $pdo->prepare("UPDATE gallery SET alt_text = ?, is_featured = ? WHERE id = ?");
            $stmt->execute([$alt, $featured, $id]);
        }
        header("Location: gallery.php?msg=updated");
        exit;
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("SELECT image_path FROM gallery WHERE id = ?");
    $stmt->execute([$id]);
    $img = $stmt->fetch();

    if ($img) {
        $stmt = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
        $stmt->execute([$id]);
        if (file_exists("../" . $img['image_path'])) {
            unlink("../" . $img['image_path']);
        }
        header("Location: gallery.php?msg=deleted");
        exit;
    }
}

// Handle Messages
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'uploaded')
        $success = "Images uploaded successfully!";
    if ($_GET['msg'] == 'deleted')
        $success = "Image deleted successfully!";
    if ($_GET['msg'] == 'updated')
        $success = "Gallery details updated successfully!";
}

include 'includes/header.php';

// Fetch Images
$images = $pdo->query("SELECT * FROM gallery ORDER BY is_featured DESC, created_at DESC")->fetchAll();
?>

<div class="header">
    <h2>Manage Gallery</h2>
</div>

<?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php
endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php
endif; ?>

<!-- Bulk Upload Form -->
<div class="card">
    <h3 style="margin-top:0;"><i class="fas fa-images"></i> Upload Images (Bulk)</h3>
    
    <div id="upload-container">
        <div style="display: flex; gap: 15px; align-items: flex-start; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 250px;">
                <label class="form-label">Select Images (Multiple allowed)</label>
                <input type="file" id="image-input" accept="image/*" multiple class="form-control" style="margin-bottom: 0;">
                <small style="color: #666; margin-top: 5px; display: block;">Hold CTRL (Windows) or CMD (Mac) to select multiple files.</small>
            </div>
            <div style="padding-top: 25px;">
                <button type="button" id="upload-btn" class="btn" style="height: 45px; padding: 0 30px;">
                    <i class="fas fa-upload"></i> Start Upload
                </button>
            </div>
        </div>

        <!-- Progress Container (Hidden by default) -->
        <div id="progress-wrapper" style="display: none; margin-top: 25px; padding: 20px; background: #f8f9fa; border-radius: 12px; border: 1px solid #eee;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-weight: 600; font-size: 14px;">
                <span id="progress-status" style="color: #333;">Preparing upload...</span>
                <span id="progress-percent" style="color: var(--primary-color);">0%</span>
            </div>
            <div style="width: 100%; height: 10px; background: #e9ecef; border-radius: 5px; overflow: hidden; margin-bottom: 10px;">
                <div id="progress-bar" style="width: 0%; height: 100%; background: linear-gradient(90deg, #00bcd4, #00acc1); transition: width 0.3s ease;"></div>
            </div>
            <div id="upload-log" style="font-size: 12px; color: #666; max-height: 60px; overflow-y: auto;"></div>
        </div>
    </div>
</div>

<script>
document.getElementById('upload-btn').addEventListener('click', async function() {
    const input = document.getElementById('image-input');
    const files = input.files;
    
    if (files.length === 0) {
        alert('Please select at least one image.');
        return;
    }

    const wrapper = document.getElementById('progress-wrapper');
    const status = document.getElementById('progress-status');
    const percent = document.getElementById('progress-percent');
    const bar = document.getElementById('progress-bar');
    const log = document.getElementById('upload-log');
    const btn = this;

    // Reset UI
    wrapper.style.display = 'block';
    log.innerHTML = '';
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';

    let successCount = 0;
    let failCount = 0;

    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const currentProgress = Math.round((i / files.length) * 100);
        
        status.innerText = `Uploading: ${file.name} (${i + 1}/${files.length})`;
        percent.innerText = `${currentProgress}%`;
        bar.style.width = `${currentProgress}%`;

        const formData = new FormData();
        formData.append('file', file);

        try {
            const response = await fetch('ajax_gallery_upload.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                successCount++;
                log.innerHTML += `<div style="color: #28a745;"><i class="fas fa-check"></i> ${file.name} uploaded.</div>`;
            } else {
                failCount++;
                log.innerHTML += `<div style="color: #dc3545;"><i class="fas fa-times"></i> ${file.name} failed: ${result.error}</div>`;
            }
        } catch (error) {
            failCount++;
            log.innerHTML += `<div style="color: #dc3545;"><i class="fas fa-exclamation-triangle"></i> ${file.name} error.</div>`;
        }
        
        log.scrollTop = log.scrollHeight;
    }

    // Final state
    bar.style.width = '100%';
    percent.innerText = '100%';
    status.innerText = `Update Complete! ${successCount} successful, ${failCount} failed.`;
    
    btn.innerHTML = '<i class="fas fa-check-circle"></i> Done';
    setTimeout(() => {
        window.location.href = 'gallery.php?msg=uploaded&count=' + successCount;
    }, 1500);
});
</script>

<!-- Gallery Grid Form -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3>Gallery Images</h3>
        <button type="button" onclick="document.getElementById('gallery-form').submit();" class="btn" style="background: #28a745;">Save Changes</button>
    </div>

    <form method="POST" id="gallery-form">
        <input type="hidden" name="update_gallery" value="1">
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
            <?php foreach ($images as $img): ?>
            <div style="position: relative; border: 1px solid #eee; border-radius: 8px; overflow: hidden; background: #fff; padding: 10px;">
                <!-- Image -->
                <div style="height: 180px; overflow: hidden; border-radius: 4px; margin-bottom: 10px; position: relative;">
                    <img src="../<?php echo $img['image_path']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <a href="?delete=<?php echo $img['id']; ?>" onclick="return confirm('Delete this image?');" 
                       style="position: absolute; top: 5px; right: 5px; background: rgba(220, 53, 69, 0.9); color: white; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; border-radius: 50%; text-decoration: none; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                       <i class="fas fa-trash-alt" style="font-size: 14px;"></i>
                    </a>
                </div>

                <!-- Controls -->
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <div>
                        <label style="font-size: 12px; font-weight: 600; margin-bottom: 2px; display: block;">Alt Text (SEO)</label>
                        <input type="text" name="gallery_items[<?php echo $img['id']; ?>][alt_text]" value="<?php echo htmlspecialchars($img['alt_text'] ?? ''); ?>" placeholder="Image description" style="width: 100%; padding: 5px; font-size: 13px; box-sizing: border-box;">
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 8px; margin-top: 5px;">
                        <input type="checkbox" id="feat_<?php echo $img['id']; ?>" name="gallery_items[<?php echo $img['id']; ?>][is_featured]" value="1" <?php echo($img['is_featured']) ? 'checked' : ''; ?> style="width: auto; margin: 0;">
                        <label for="feat_<?php echo $img['id']; ?>" style="font-size: 13px; margin: 0; cursor: pointer; color: #28a745; font-weight: 600;">Mark as Featured</label>
                    </div>
                </div>
            </div>
            <?php
endforeach; ?>
        </div>
    </form>
    
    <?php if (count($images) == 0): ?>
        <p style="text-align: center; color: #777; padding: 40px;">No images found. Upload some above!</p>
    <?php
endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
