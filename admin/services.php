<?php
require_once '../config/db.php';

$success = '';
$error = '';
$edit_mode = false;
$service_to_edit = null;

// Handle Delete (Move Logic Up)
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("SELECT icon FROM services WHERE id = ?");
    $stmt->execute([$id]);
    $service = $stmt->fetch();

    if ($service) {
        $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
        if ($stmt->execute([$id])) {
            if ($service['icon'] && file_exists("../" . $service['icon'])) {
                unlink("../" . $service['icon']);
            }
            header("Location: services.php?msg=deleted");
            exit;
        }
        else {
            $error = "Failed to delete service.";
        }
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $short_desc = $_POST['short_description'];
    $long_desc = $_POST['long_description'];
    $display_order = $_POST['display_order'] ?? 0;

    // SEO Meta
    $seo_title = $_POST['seo_title'];
    $seo_description = $_POST['seo_description'];
    $seo_keywords = $_POST['seo_keywords'];

    $id = $_POST['id'] ?? null;

    // Image Upload
    $icon_path = null;
    if (isset($_FILES['icon']) && $_FILES['icon']['error'] == 0) {
        $target_dir = "../assets/images/services/";
        if (!file_exists($target_dir))
            mkdir($target_dir, 0777, true);

        $file_name = time() . '_' . basename($_FILES["icon"]["name"]);
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["icon"]["tmp_name"], $target_file)) {
            $icon_path = "assets/images/services/" . $file_name;
        }
        else {
            $error = "Failed to upload icon.";
        }
    }

    if (!$error) {
        if ($id) {
            // Update
            if ($icon_path) {
                // Delete old icon
                $stmt = $pdo->prepare("SELECT icon FROM services WHERE id = ?");
                $stmt->execute([$id]);
                $old_svc = $stmt->fetch();
                if ($old_svc['icon'] && file_exists("../" . $old_svc['icon'])) {
                    unlink("../" . $old_svc['icon']);
                }

                $stmt = $pdo->prepare("UPDATE services SET name=?, short_description=?, long_description=?, icon=?, display_order=?, seo_title=?, seo_description=?, seo_keywords=? WHERE id=?");
                $result = $stmt->execute([$name, $short_desc, $long_desc, $icon_path, $display_order, $seo_title, $seo_description, $seo_keywords, $id]);
            }
            else {
                $stmt = $pdo->prepare("UPDATE services SET name=?, short_description=?, long_description=?, display_order=?, seo_title=?, seo_description=?, seo_keywords=? WHERE id=?");
                $result = $stmt->execute([$name, $short_desc, $long_desc, $display_order, $seo_title, $seo_description, $seo_keywords, $id]);
            }

            if ($result) {
                header("Location: services.php?msg=updated");
                exit;
            }
            else {
                $error = "Database error.";
            }

        }
        else {
            // Create
            $full_icon_path = $icon_path ? $icon_path : null;

            $stmt = $pdo->prepare("INSERT INTO services (name, short_description, long_description, icon, display_order, seo_title, seo_description, seo_keywords) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $short_desc, $long_desc, $full_icon_path, $display_order, $seo_title, $seo_description, $seo_keywords])) {
                header("Location: services.php?msg=added");
                exit;
            }
            else {
                $error = "Database error.";
            }
        }
    }
}

// Check for Edit Mode
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$edit_id]);
    $service_to_edit = $stmt->fetch();
    if ($service_to_edit)
        $edit_mode = true;
}

// Handle Messages
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'updated')
        $success = "Service updated successfully!";
    if ($_GET['msg'] == 'added')
        $success = "Service added successfully!";
    if ($_GET['msg'] == 'deleted')
        $success = "Service deleted successfully!";
}

// Include Header AFTER logic
include 'includes/header.php';

// Fetch Services
$services = $pdo->query("SELECT * FROM services ORDER BY display_order ASC")->fetchAll();
?>

<div class="header">
    <h2>Manage Services</h2>
    <?php if ($edit_mode): ?>
        <a href="services.php" class="btn" style="background: #6c757d;">Cancel Edit</a>
    <?php
endif; ?>
</div>

<?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php
endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php
endif; ?>

<div class="card">
    <h3><?php echo $edit_mode ? 'Edit Service' : 'Add New Service'; ?></h3>
    <form method="POST" enctype="multipart/form-data">
        <?php if ($edit_mode): ?>
            <input type="hidden" name="id" value="<?php echo $service_to_edit['id']; ?>">
        <?php
endif; ?>

        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Service Name</label>
            <input type="text" name="name" value="<?php echo $edit_mode ? htmlspecialchars($service_to_edit['name']) : ''; ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" required>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Display Order</label>
                <input type="number" name="display_order" value="<?php echo $edit_mode ? htmlspecialchars($service_to_edit['display_order']) : '0'; ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Icon / Thumbnail Image</label>
                <?php if ($edit_mode && $service_to_edit['icon']): ?>
                    <div style="margin-bottom: 5px;">
                        <img src="../<?php echo $service_to_edit['icon']; ?>" width="40" style="vertical-align: middle; margin-right: 10px;">
                        <small>Current Icon</small>
                    </div>
                <?php
endif; ?>
                <input type="file" name="icon" accept="image/*" style="padding: 5px;">
            </div>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Short Description (Shown on Home)</label>
            <textarea name="short_description" rows="3" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"><?php echo $edit_mode ? htmlspecialchars($service_to_edit['short_description']) : ''; ?></textarea>
        </div>
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Long Description (Optional - for detail page)</label>
            <textarea name="long_description" id="long_description" rows="10" style="width: 100%;"><?php echo $edit_mode ? htmlspecialchars($service_to_edit['long_description']) : ''; ?></textarea>
        </div>

        <div style="background: #f0f8ff; padding: 20px; border-radius: 8px; border: 1px solid #cce5ff; margin-bottom: 20px;">
            <h4 style="margin-top: 0; color: #004085;"><i class="fas fa-search"></i> SEO Optimizations</h4>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">SEO Meta Title</label>
                <input type="text" name="seo_title" value="<?php echo $edit_mode ? htmlspecialchars($service_to_edit['seo_title']) : ''; ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" placeholder="Custom title for search engines...">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">SEO Meta Description</label>
                <textarea name="seo_description" rows="2" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" placeholder="Brief summary for search results..."><?php echo $edit_mode ? htmlspecialchars($service_to_edit['seo_description']) : ''; ?></textarea>
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">SEO Keywords</label>
                <input type="text" name="seo_keywords" value="<?php echo $edit_mode ? htmlspecialchars($service_to_edit['seo_keywords']) : ''; ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" placeholder="e.g. transport, airport transfer, taxi sri lanka">
            </div>
        </div>
        
        <button type="submit" class="btn" style="padding: 12px 24px; font-size: 16px;">
            <?php echo $edit_mode ? 'Update Service' : 'Add Service'; ?>
        </button>
    </form>
</div>

<!-- TinyMCE -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js"></script>
<script>
    tinymce.init({
        selector: '#long_description',
        height: 400,
        plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
        branding: false,
        elementpath: false,
        images_upload_handler: function (blobInfo, progress) {
            return new Promise((resolve, reject) => {
                var reader = new FileReader();
                reader.readAsDataURL(blobInfo.blob());
                reader.onload = function () {
                    resolve(reader.result);
                };
                reader.onerror = function (error) {
                    reject('Image upload failed: ' + error.message);
                };
            });
        }
    });
</script>

<div class="card">
    <h3>Existing Services</h3>
    <?php if (count($services) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Order</th>
                <th>Icon</th>
                <th>Name</th>
                <th>Short Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($services as $svc): ?>
            <tr>
                <td><?php echo $svc['display_order']; ?></td>
                <td>
                    <?php if ($svc['icon']): ?>
                        <img src="../<?php echo $svc['icon']; ?>" width="40" height="40" style="object-fit: cover; border-radius: 4px;">
                    <?php
        else: ?>
                        <i class="fas fa-concierge-bell" style="font-size: 24px; color: #ccc;"></i>
                    <?php
        endif; ?>
                </td>
                <td><?php echo htmlspecialchars($svc['name']); ?></td>
                <td><?php echo htmlspecialchars(mb_strimwidth($svc['short_description'], 0, 50, "...")); ?></td>
                <td>
                    <a href="?edit=<?php echo $svc['id']; ?>" style="color: #00bcd4; margin-right: 10px;"><i class="fas fa-edit"></i></a>
                    <a href="?delete=<?php echo $svc['id']; ?>" onclick="return confirm('Delete this service?');" style="color: #d9534f;"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            <?php
    endforeach; ?>
        </tbody>
    </table>
    <?php
else: ?>
        <p style="text-align: center; color: #777;">No services added yet.</p>
    <?php
endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
