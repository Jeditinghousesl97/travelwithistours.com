<?php
require_once '../config/db.php';

$success = '';
$error = '';
$edit_mode = false;
$slide_to_edit = null;

// Handle Delete (Logic First)
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("SELECT image_path FROM hero_slides WHERE id = ?");
    $stmt->execute([$id]);
    $slide = $stmt->fetch();

    if ($slide) {
        $stmt = $pdo->prepare("DELETE FROM hero_slides WHERE id = ?");
        if ($stmt->execute([$id])) {
            if (file_exists("../" . $slide['image_path'])) {
                unlink("../" . $slide['image_path']);
            }
            header("Location: slider.php?msg=deleted");
            exit;
        }
        else {
            $error = "Failed to delete slide.";
        }
    }
}

// Handle Form Submission (Add or Update)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $subtitle = $_POST['subtitle'];
    $button_text = $_POST['button_text'];
    $button_link = $_POST['button_link'];
    $display_order = $_POST['display_order'] ?? 0;

    $id = $_POST['id'] ?? null;

    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../assets/images/hero/";
        if (!file_exists($target_dir))
            mkdir($target_dir, 0777, true);

        $file_name = time() . '_' . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_path = "assets/images/hero/" . $file_name;
        }
        else {
            $error = "Failed to upload image.";
        }
    }

    if (!$error) {
        if ($id) {
            // Update
            if ($image_path) {
                // Fetch old image to delete
                $stmt = $pdo->prepare("SELECT image_path FROM hero_slides WHERE id = ?");
                $stmt->execute([$id]);
                $old_slide = $stmt->fetch();
                if ($old_slide && file_exists("../" . $old_slide['image_path'])) {
                    unlink("../" . $old_slide['image_path']);
                }

                $stmt = $pdo->prepare("UPDATE hero_slides SET image_path=?, title=?, subtitle=?, button_text=?, button_link=?, display_order=? WHERE id=?");
                $result = $stmt->execute([$image_path, $title, $subtitle, $button_text, $button_link, $display_order, $id]);
            }
            else {
                $stmt = $pdo->prepare("UPDATE hero_slides SET title=?, subtitle=?, button_text=?, button_link=?, display_order=? WHERE id=?");
                $result = $stmt->execute([$title, $subtitle, $button_text, $button_link, $display_order, $id]);
            }

            if ($result) {
                header("Location: slider.php?msg=updated");
                exit;
            }
            else
                $error = "Database error during update.";

        }
        else {
            // Create
            if ($image_path) {
                $stmt = $pdo->prepare("INSERT INTO hero_slides (image_path, title, subtitle, button_text, button_link, display_order) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$image_path, $title, $subtitle, $button_text, $button_link, $display_order])) {
                    header("Location: slider.php?msg=added");
                    exit;
                }
                else {
                    $error = "Database error.";
                }
            }
            else {
                $error = "Please select an image for new slide.";
            }
        }
    }
}

// Check Edit Mode
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM hero_slides WHERE id = ?");
    $stmt->execute([$edit_id]);
    $slide_to_edit = $stmt->fetch();
    if ($slide_to_edit) {
        $edit_mode = true;
    }
}

// Handle Messages
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'updated')
        $success = "Slide updated successfully!";
    if ($_GET['msg'] == 'added')
        $success = "Slide added successfully!";
    if ($_GET['msg'] == 'deleted')
        $success = "Slide deleted successfully!";
}

// Include Header AFTER logic
include 'includes/header.php';

// Fetch Slides
$slides = $pdo->query("SELECT * FROM hero_slides ORDER BY display_order ASC")->fetchAll();
?>

<div class="header">
    <h2>Manage Hero Slider</h2>
    <?php if ($edit_mode): ?>
        <a href="slider.php" class="btn btn-secondary">
            <i class="fas fa-times"></i> Cancel Edit
        </a>
    <?php
endif; ?>
</div>

<?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php
endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php
endif; ?>

<div class="card">
    <h3 style="margin-top: 0; margin-bottom: 25px; font-size: 18px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
        <i class="fas <?php echo $edit_mode ? 'fa-edit' : 'fa-plus-circle'; ?>" style="color: var(--primary-color);"></i>
        <?php echo $edit_mode ? 'Edit Slide' : 'Add New Slide'; ?>
    </h3>
    
    <form method="POST" enctype="multipart/form-data">
        <?php if ($edit_mode): ?>
            <input type="hidden" name="id" value="<?php echo $slide_to_edit['id']; ?>">
        <?php
endif; ?>

        <div class="form-grid">
            <!-- Left Side: Basic Info -->
            <div class="form-section" style="background: none; border: none; padding: 0; margin-bottom: 0;">
                <div class="form-group">
                    <label class="form-label">Slide Title</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Discover Sri Lanka" value="<?php echo $edit_mode ? htmlspecialchars($slide_to_edit['title']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Subtitle</label>
                    <input type="text" name="subtitle" class="form-control" placeholder="e.g. The Pearl of the Indian Ocean" value="<?php echo $edit_mode ? htmlspecialchars($slide_to_edit['subtitle']) : ''; ?>">
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Button Text</label>
                        <input type="text" name="button_text" class="form-control" value="<?php echo $edit_mode ? htmlspecialchars($slide_to_edit['button_text']) : 'Explore Our Tours'; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Button Link</label>
                        <input type="text" name="button_link" class="form-control" value="<?php echo $edit_mode ? htmlspecialchars($slide_to_edit['button_link']) : '#tours'; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Display Order</label>
                    <input type="number" name="display_order" class="form-control" value="<?php echo $edit_mode ? htmlspecialchars($slide_to_edit['display_order']) : '0'; ?>" style="width: 120px;">
                </div>
            </div>

            <!-- Right Side: Image Upload -->
            <div class="form-section" style="background: #fcfcfc; border: 1px dashed #ddd; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 25px;">
                <label class="form-label" style="align-self: flex-start;">Slide Image (Recommended: 1920x800px)</label>
                
                <?php if ($edit_mode && $slide_to_edit['image_path']): ?>
                    <div style="margin: 15px 0; text-align: center;">
                        <img src="../<?php echo $slide_to_edit['image_path']; ?>" style="width: 100%; max-width: 300px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border: 2px solid #fff;">
                        <p style="font-size: 11px; color: #888; margin-top: 8px;">Existing Image</p>
                    </div>
                <?php
else: ?>
                    <div style="margin: 30px 0; color: #ccc; text-align: center;">
                        <i class="fas fa-image" style="font-size: 60px; display: block; margin-bottom: 10px;"></i>
                        <p style="font-size: 13px;">No image selected</p>
                    </div>
                <?php
endif; ?>

                <div style="width: 100%;">
                    <input type="file" name="image" class="form-control" accept="image/*" <?php echo $edit_mode ? '' : 'required'; ?> style="padding: 8px;">
                    <small style="color: #666; display: block; margin-top: 5px;">Upload a high-quality landscape image.</small>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 30px; text-align: right;">
            <button type="submit" class="btn" style="padding: 12px 40px; font-weight: 600;">
                <i class="fas fa-save" style="margin-right: 8px;"></i> <?php echo $edit_mode ? 'Update Slide' : 'Add New Slide'; ?>
            </button>
        </div>
    </form>
</div>

<div class="card">
    <h3 style="margin-top: 0; margin-bottom: 25px; font-size: 18px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
        <i class="fas fa-layer-group" style="color: var(--primary-color);"></i> Existing Slides
    </h3>
    
    <?php if (count($slides) > 0): ?>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: separate; border-spacing: 0 10px;">
            <thead>
                <tr style="background: transparent;">
                    <th style="background: transparent; padding-bottom: 15px;">Order</th>
                    <th style="background: transparent; padding-bottom: 15px;">Preview</th>
                    <th style="background: transparent; padding-bottom: 15px;">Slide Info</th>
                    <th style="background: transparent; padding-bottom: 15px;">Call to Action</th>
                    <th style="background: transparent; padding-bottom: 15px; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($slides as $slide): ?>
                <tr style="background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.02); transition: 0.3s;">
                    <td style="padding: 20px; border-top: 1px solid #eee; border-left: 1px solid #eee; border-bottom: 1px solid #eee; border-radius: 8px 0 0 8px; width: 60px; text-align: center;">
                        <span style="font-weight: 700; color: #555;"><?php echo $slide['display_order']; ?></span>
                    </td>
                    <td style="padding: 20px; border-top: 1px solid #eee; border-bottom: 1px solid #eee; width: 140px;">
                        <div style="width: 120px; height: 60px; border-radius: 6px; overflow: hidden; border: 1px solid #eee;">
                            <img src="../<?php echo $slide['image_path']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    </td>
                    <td style="padding: 20px; border-top: 1px solid #eee; border-bottom: 1px solid #eee;">
                        <div style="font-weight: 600; color: #333; font-size: 16px; margin-bottom: 4px;"><?php echo htmlspecialchars($slide['title']); ?></div>
                        <div style="font-size: 13px; color: #888;"><?php echo htmlspecialchars($slide['subtitle']); ?></div>
                    </td>
                    <td style="padding: 20px; border-top: 1px solid #eee; border-bottom: 1px solid #eee;">
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <span style="font-size: 12px; color: #555; background: #f0f0f0; padding: 2px 8px; border-radius: 10px; width: fit-content;"><?php echo htmlspecialchars($slide['button_text']); ?></span>
                            <span style="font-size: 11px; color: #00bcd4;"><?php echo htmlspecialchars($slide['button_link']); ?></span>
                        </div>
                    </td>
                    <td style="padding: 20px; border-top: 1px solid #eee; border-right: 1px solid #eee; border-bottom: 1px solid #eee; border-radius: 0 8px 8px 0; text-align: right;">
                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                            <a href="?edit=<?php echo $slide['id']; ?>" class="btn-action" style="color: #00bcd4; background: #e0f7fa; width: 35px; height: 35px; border-radius: 8px; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: 0.2s;">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="?delete=<?php echo $slide['id']; ?>" onclick="return confirm('Delete this slide?');" class="btn-action" style="color: #f44336; background: #ffebee; width: 35px; height: 35px; border-radius: 8px; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: 0.2s;">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php
    endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
else: ?>
        <div style="text-align: center; padding: 60px 20px; color: #999;">
            <i class="fas fa-images" style="font-size: 50px; display: block; margin-bottom: 15px; opacity: 0.3;"></i>
            <p>No slides have been added yet. Start by adding one above!</p>
        </div>
    <?php
endif; ?>
</div>

<style>
    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
</style>

<?php include 'includes/footer.php'; ?>
