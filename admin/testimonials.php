<?php
require_once '../config/db.php';
require_once 'includes/auth_session.php';
include 'includes/header.php';

// Prepare variables for edit mode
$edit_mode = false;
$edit_data = [];

// Handle Edit Fetch
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM testimonials WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_data = $stmt->fetch();
    if ($edit_data) {
        $edit_mode = true;
    }
}

// Handle Add/Update/Delete/Duplicate
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['save_testimonial'])) {
        $name = $_POST['name'];
        $location = $_POST['location'];
        $rating = $_POST['rating'];
        $text = $_POST['text'];
        $link = $_POST['link']; // New link field
        $image = isset($_POST['current_image']) ? $_POST['current_image'] : '';

        // Handle Image Upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = array('jpg', 'jpeg', 'png', 'gif', 'webp');
            $filename = $_FILES['image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                $target_dir = "../uploads/testimonials/";
                if (!file_exists($target_dir))
                    mkdir($target_dir, 0777, true);

                $new_filename = time() . "_" . basename($filename);
                $image = "uploads/testimonials/" . $new_filename;
                move_uploaded_file($_FILES["image"]["tmp_name"], $target_dir . $new_filename);
            }
            else {
            // Invalid file type. Keep old image or handle error. 
            // For now, we just don't update the image variable, so it keeps old value or empty.
            }
        }

        if (isset($_POST['id']) && !empty($_POST['id'])) {
            // Update
            $id = $_POST['id'];
            $stmt = $pdo->prepare("UPDATE testimonials SET name=?, location=?, rating=?, text=?, link=?, image=? WHERE id=?");
            $stmt->execute([$name, $location, $rating, $text, $link, $image, $id]);
            $msg = "Testimonial updated!";
        }
        else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO testimonials (name, location, rating, text, link, image) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $location, $rating, $text, $link, $image]);
            $msg = "Testimonial added!";
        }
        // Redirect to clear post data
        echo "<script>window.location.href='testimonials.php';</script>";
        exit;
    }
    elseif (isset($_POST['delete_testimonial'])) {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM testimonials WHERE id = ?");
        $stmt->execute([$id]);
        echo "<script>window.location.href='testimonials.php';</script>";
        exit;
    }
    elseif (isset($_POST['duplicate_testimonial'])) {
        $id = $_POST['id'];
        // Copy existing record securely
        $stmt = $pdo->prepare("SELECT name, location, rating, text, link, image FROM testimonials WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if ($row) {
            $stmtInsert = $pdo->prepare("INSERT INTO testimonials (name, location, rating, text, link, image) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtInsert->execute([$row['name'], $row['location'], $row['rating'], $row['text'], $row['link'], $row['image']]);
        }

        echo "<script>window.location.href='testimonials.php';</script>";
        exit;
    }
}

// Fetch Testimonials
$testimonials = $pdo->query("SELECT * FROM testimonials ORDER BY created_at DESC")->fetchAll();
?>

<div class="header">
    <h2>Manage Testimonials</h2>
</div>

<div class="card">
    <h3><?php echo $edit_mode ? 'Edit Testimonial' : 'Add New Testimonial'; ?></h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="save_testimonial" value="1">
        <?php if ($edit_mode): ?>
            <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
            <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($edit_data['image']); ?>">
        <?php
endif; ?>

        <label>Guest Name</label>
        <input type="text" name="name" required style="width: 100%; padding: 8px; margin-bottom: 10px;" value="<?php echo htmlspecialchars($edit_data['name'] ?? ''); ?>">
        
        <label>Location (e.g. UK)</label>
        <input type="text" name="location" required style="width: 100%; padding: 8px; margin-bottom: 10px;" value="<?php echo htmlspecialchars($edit_data['location'] ?? ''); ?>">
        
        <label>Rating (1-5)</label>
        <input type="number" name="rating" min="1" max="5" value="<?php echo htmlspecialchars($edit_data['rating'] ?? '5'); ?>" required style="width: 100%; padding: 8px; margin-bottom: 10px;">
        
        <label>Review Text</label>
        <textarea name="text" rows="4" required style="width: 100%; padding: 8px; margin-bottom: 10px;"><?php echo htmlspecialchars($edit_data['text'] ?? ''); ?></textarea>
        
        <label>Link (Optional)</label>
        <input type="text" name="link" style="width: 100%; padding: 8px; margin-bottom: 10px;" value="<?php echo htmlspecialchars($edit_data['link'] ?? ''); ?>" placeholder="https://...">

        <label>Guest Image (Optional)</label>
        <?php if ($edit_mode && !empty($edit_data['image'])): ?>
            <div style="margin-bottom: 10px;"><img src="../<?php echo htmlspecialchars($edit_data['image']); ?>" width="50"></div>
        <?php
endif; ?>
        <input type="file" name="image" style="margin-bottom: 10px;">
        
        <button type="submit" class="btn"><?php echo $edit_mode ? 'Update Testimonial' : 'Add Testimonial'; ?></button>
        <?php if ($edit_mode): ?>
            <a href="testimonials.php" class="btn" style="background: #777; margin-left: 10px;">Cancel</a>
        <?php
endif; ?>
    </form>
</div>

<div class="card">
    <h3>Existing Testimonials</h3>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f4f4f4; text-align: left;">
                <th style="padding: 10px;">Guest</th>
                <th style="padding: 10px;">Location</th>
                <th style="padding: 10px;">Rating</th>
                <th style="padding: 10px;">Review</th>
                <th style="padding: 10px;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($testimonials as $testimonial): ?>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px;">
                    <?php if ($testimonial['image']): ?>
                        <img src="../<?php echo htmlspecialchars($testimonial['image']); ?>" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                    <?php
    endif; ?>
                    <?php echo htmlspecialchars($testimonial['name']); ?>
                </td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($testimonial['location']); ?></td>
                <td style="padding: 10px;"><?php echo str_repeat('★', $testimonial['rating']); ?></td>
                <td style="padding: 10px;">
                    <?php echo htmlspecialchars(substr($testimonial['text'], 0, 50)) . '...'; ?>
                    <?php if ($testimonial['link']): ?><br><a href="<?php echo htmlspecialchars($testimonial['link']); ?>" target="_blank" style="font-size:12px;">View Link</a><?php
    endif; ?>
                </td>
                <td style="padding: 10px;">
                    <a href="testimonials.php?edit=<?php echo $testimonial['id']; ?>" class="btn" style="padding: 5px 10px; font-size: 12px; margin-right: 5px;">Edit</a>
                    
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="duplicate_testimonial" value="1">
                        <input type="hidden" name="id" value="<?php echo $testimonial['id']; ?>">
                        <button type="submit" class="btn" style="padding: 5px 10px; font-size: 12px; background: #17a2b8; margin-right: 5px;">Copy</button>
                    </form>

                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="delete_testimonial" value="1">
                        <input type="hidden" name="id" value="<?php echo $testimonial['id']; ?>">
                        <button type="submit" class="btn-danger" style="padding: 5px 10px; background: #dc3545; color: #fff; border: none; cursor: pointer;" onclick="return confirm('Delete this testimonial?')">Delete</button>
                    </form>
                </td>
            </tr>
            <?php
endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
