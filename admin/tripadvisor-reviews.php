<?php
require_once '../config/db.php';
require_once 'includes/auth_session.php';
require_once '../includes/tripadvisor_reviews.php';

ensure_tripadvisor_reviews_schema($pdo);

include 'includes/header.php';

$edit_mode = false;
$edit_data = [];
$widget_embed = '';

$widget_stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
$widget_stmt->execute(['tripadvisor_widget_embed']);
$widget_embed = (string) ($widget_stmt->fetchColumn() ?: '');

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM tripadvisor_reviews WHERE id = ?");
    $stmt->execute([(int) $_GET['edit']]);
    $edit_data = $stmt->fetch() ?: [];
    $edit_mode = !empty($edit_data);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_widget_embed'])) {
        $widget_embed = $_POST['tripadvisor_widget_embed'] ?? '';
        $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute(['tripadvisor_widget_embed', $widget_embed]);

        echo "<script>window.location.href='tripadvisor-reviews.php?msg=widget_saved';</script>";
        exit;
    }

    if (isset($_POST['save_tripadvisor_review'])) {
        $reviewer_name = trim($_POST['reviewer_name'] ?? '');
        $reviewer_location = trim($_POST['reviewer_location'] ?? '');
        $review_title = trim($_POST['review_title'] ?? '');
        $rating = max(1, min(5, (int) ($_POST['rating'] ?? 5)));
        $review_text = trim($_POST['review_text'] ?? '');
        $trip_date = trim($_POST['trip_date'] ?? '');
        $review_link = trim($_POST['review_link'] ?? '');
        $display_order = (int) ($_POST['display_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $reviewer_image = $_POST['current_image'] ?? '';

        if (isset($_FILES['reviewer_image']) && (int) $_FILES['reviewer_image']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['reviewer_image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed, true)) {
                $target_dir = "../uploads/tripadvisor-reviews/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }

                $new_filename = time() . "_" . preg_replace('/[^A-Za-z0-9._-]/', '-', basename($filename));
                $reviewer_image = "uploads/tripadvisor-reviews/" . $new_filename;
                move_uploaded_file($_FILES['reviewer_image']['tmp_name'], $target_dir . $new_filename);
            }
        }

        if (!empty($_POST['id'])) {
            $stmt = $pdo->prepare(
                "UPDATE tripadvisor_reviews
                SET reviewer_name = ?, reviewer_location = ?, review_title = ?, rating = ?, review_text = ?, trip_date = ?, review_link = ?, reviewer_image = ?, display_order = ?, is_active = ?
                WHERE id = ?"
            );
            $stmt->execute([
                $reviewer_name,
                $reviewer_location,
                $review_title,
                $rating,
                $review_text,
                $trip_date,
                $review_link,
                $reviewer_image,
                $display_order,
                $is_active,
                (int) $_POST['id'],
            ]);
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO tripadvisor_reviews
                (reviewer_name, reviewer_location, review_title, rating, review_text, trip_date, review_link, reviewer_image, display_order, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $reviewer_name,
                $reviewer_location,
                $review_title,
                $rating,
                $review_text,
                $trip_date,
                $review_link,
                $reviewer_image,
                $display_order,
                $is_active,
            ]);
        }

        echo "<script>window.location.href='tripadvisor-reviews.php';</script>";
        exit;
    }

    if (isset($_POST['delete_tripadvisor_review'])) {
        $review_id = (int) $_POST['id'];
        $photo_stmt = $pdo->prepare("SELECT review_photos FROM tripadvisor_reviews WHERE id = ?");
        $photo_stmt->execute([$review_id]);
        $review_photos_json = $photo_stmt->fetchColumn();

        $stmt = $pdo->prepare("DELETE FROM tripadvisor_reviews WHERE id = ?");
        $stmt->execute([$review_id]);

        if ($stmt->rowCount() > 0) {
            tripadvisor_delete_review_photos(is_string($review_photos_json) ? $review_photos_json : null, dirname(__DIR__));
        }

        echo "<script>window.location.href='tripadvisor-reviews.php';</script>";
        exit;
    }

    if (isset($_POST['duplicate_tripadvisor_review'])) {
        $stmt = $pdo->prepare(
            "INSERT INTO tripadvisor_reviews
            (reviewer_name, reviewer_location, review_title, rating, review_text, trip_date, review_link, reviewer_image, review_photos, submission_source, display_order, is_active)
            SELECT reviewer_name, reviewer_location, review_title, rating, review_text, trip_date, review_link, reviewer_image, NULL, 'admin', display_order, is_active
            FROM tripadvisor_reviews
            WHERE id = ?"
        );
        $stmt->execute([(int) $_POST['id']]);

        echo "<script>window.location.href='tripadvisor-reviews.php';</script>";
        exit;
    }
}

$reviews = $pdo->query("SELECT * FROM tripadvisor_reviews ORDER BY display_order ASC, created_at DESC")->fetchAll();
?>

<div class="header">
    <h2>Manage TripAdvisor Reviews</h2>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'widget_saved'): ?>
    <div class="alert alert-success">TripAdvisor widget embed saved successfully.</div>
<?php endif; ?>

<div class="card">
    <h3>TripAdvisor Widget Embed</h3>
    <form method="POST">
        <input type="hidden" name="save_widget_embed" value="1">
        <label class="form-label">Paste TripAdvisor widget HTML / embed code</label>
        <textarea name="tripadvisor_widget_embed" class="form-control" rows="8" placeholder="&lt;div&gt;...TripAdvisor widget code...&lt;/div&gt;"><?php echo htmlspecialchars($widget_embed); ?></textarea>
        <small style="display: block; color: #666; margin-top: 8px;">This will appear in the right-hand column of the homepage TripAdvisor section.</small>
        <button type="submit" class="btn" style="margin-top: 15px;">Save Widget Code</button>
    </form>
</div>

<div class="card">
    <h3><?php echo $edit_mode ? 'Edit TripAdvisor Review' : 'Add TripAdvisor Review'; ?></h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="save_tripadvisor_review" value="1">
        <?php if ($edit_mode): ?>
            <input type="hidden" name="id" value="<?php echo (int) $edit_data['id']; ?>">
            <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($edit_data['reviewer_image'] ?? ''); ?>">
        <?php endif; ?>

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Reviewer Name</label>
                <input type="text" name="reviewer_name" class="form-control" required value="<?php echo htmlspecialchars($edit_data['reviewer_name'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Location</label>
                <input type="text" name="reviewer_location" class="form-control" placeholder="London, United Kingdom" value="<?php echo htmlspecialchars($edit_data['reviewer_location'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Review Title</label>
                <input type="text" name="review_title" class="form-control" required value="<?php echo htmlspecialchars($edit_data['review_title'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Trip Date</label>
                <input type="text" name="trip_date" class="form-control" placeholder="February 2026" value="<?php echo htmlspecialchars($edit_data['trip_date'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Rating (1-5)</label>
                <input type="number" name="rating" class="form-control" min="1" max="5" required value="<?php echo htmlspecialchars((string) ($edit_data['rating'] ?? '5')); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Display Order</label>
                <input type="number" name="display_order" class="form-control" value="<?php echo htmlspecialchars((string) ($edit_data['display_order'] ?? '0')); ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Review Text</label>
            <textarea name="review_text" class="form-control" rows="7" required placeholder="Paste the review text here. Emojis are supported."><?php echo htmlspecialchars($edit_data['review_text'] ?? ''); ?></textarea>
        </div>

        <?php $edit_review_photos = tripadvisor_review_photos((string) ($edit_data['review_photos'] ?? '')); ?>
        <?php if (!empty($edit_review_photos)): ?>
            <div class="form-group">
                <label class="form-label">Guest-submitted Photos</label>
                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                    <?php foreach ($edit_review_photos as $photo): ?>
                        <a href="../<?php echo htmlspecialchars($photo); ?>" target="_blank" rel="noopener noreferrer">
                            <img src="../<?php echo htmlspecialchars($photo); ?>" alt="Guest review photo" style="width: 72px; height: 72px; border-radius: 8px; object-fit: cover;">
                        </a>
                    <?php endforeach; ?>
                </div>
                <small style="display: block; color: #666; margin-top: 8px;">These photos were uploaded with the guest review and remain attached when the text is edited.</small>
            </div>
        <?php endif; ?>

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Review Link</label>
                <input type="url" name="review_link" class="form-control" placeholder="https://www.tripadvisor.com/..." value="<?php echo htmlspecialchars($edit_data['review_link'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Reviewer Image / Avatar</label>
                <?php if ($edit_mode && !empty($edit_data['reviewer_image'])): ?>
                    <div style="margin-bottom: 10px;">
                        <img src="../<?php echo htmlspecialchars($edit_data['reviewer_image']); ?>" alt="Current avatar" style="width: 56px; height: 56px; object-fit: cover; border-radius: 50%;">
                    </div>
                <?php endif; ?>
                <input type="file" name="reviewer_image" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp">
            </div>
        </div>

        <label style="display: inline-flex; align-items: center; gap: 10px; margin-bottom: 20px; font-weight: 600; color: #555;">
            <input type="checkbox" name="is_active" value="1" <?php echo !isset($edit_data['is_active']) || (int) $edit_data['is_active'] === 1 ? 'checked' : ''; ?>>
            Show this review on the homepage
        </label>

        <div>
            <button type="submit" class="btn"><?php echo $edit_mode ? 'Update Review' : 'Add Review'; ?></button>
            <?php if ($edit_mode): ?>
                <a href="tripadvisor-reviews.php" class="btn" style="background: #6c757d; margin-left: 10px;">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <h3>Existing TripAdvisor Reviews</h3>
    <table>
        <thead>
            <tr>
                <th>Reviewer</th>
                <th>Source</th>
                <th>Title</th>
                <th>Rating</th>
                <th>Trip Date</th>
                <th>Order</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($reviews) > 0): ?>
                <?php foreach ($reviews as $review): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <?php if (!empty($review['reviewer_image'])): ?>
                                    <img src="../<?php echo htmlspecialchars($review['reviewer_image']); ?>" alt="<?php echo htmlspecialchars($review['reviewer_name']); ?>" style="width: 46px; height: 46px; border-radius: 50%; object-fit: cover;">
                                <?php else: ?>
                                    <div style="width: 46px; height: 46px; border-radius: 50%; background: #e0f7fa; color: #00838f; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                                        <?php echo htmlspecialchars(tripadvisor_reviewer_initials($review['reviewer_name'])); ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($review['reviewer_name']); ?></div>
                                    <div style="font-size: 12px; color: #777;"><?php echo htmlspecialchars($review['reviewer_location']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if (($review['submission_source'] ?? 'admin') === 'guest'): ?>
                                <span style="display: inline-block; padding: 4px 8px; border-radius: 20px; background: #eaf8f4; color: #0f7b55; font-size: 11px; font-weight: 700;">Guest</span>
                                <?php $list_photos = tripadvisor_review_photos((string) ($review['review_photos'] ?? '')); ?>
                                <?php if (!empty($list_photos)): ?>
                                    <div style="font-size: 11px; color: #777; margin-top: 5px;"><?php echo count($list_photos); ?> photo<?php echo count($list_photos) === 1 ? '' : 's'; ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="font-size: 12px; color: #777;">Admin</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-weight: 600; margin-bottom: 4px;"><?php echo htmlspecialchars($review['review_title']); ?></div>
                            <div style="font-size: 12px; color: #777;"><?php echo htmlspecialchars(function_exists('mb_strimwidth') ? mb_strimwidth($review['review_text'], 0, 80, '...') : substr($review['review_text'], 0, 80) . '...'); ?></div>
                        </td>
                        <td><?php echo str_repeat('★', (int) $review['rating']); ?></td>
                        <td><?php echo htmlspecialchars($review['trip_date']); ?></td>
                        <td><?php echo (int) $review['display_order']; ?></td>
                        <td><?php echo (int) $review['is_active'] === 1 ? 'Active' : 'Hidden'; ?></td>
                        <td>
                            <a href="tripadvisor-reviews.php?edit=<?php echo (int) $review['id']; ?>" class="btn" style="padding: 6px 10px; font-size: 12px; margin-right: 5px;">Edit</a>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="duplicate_tripadvisor_review" value="1">
                                <input type="hidden" name="id" value="<?php echo (int) $review['id']; ?>">
                                <button type="submit" class="btn" style="padding: 6px 10px; font-size: 12px; background: #17a2b8; margin-right: 5px;">Copy</button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="delete_tripadvisor_review" value="1">
                                <input type="hidden" name="id" value="<?php echo (int) $review['id']; ?>">
                                <button type="submit" class="btn btn-danger" style="padding: 6px 10px; font-size: 12px;" onclick="return confirm('Delete this TripAdvisor review?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 20px;">No TripAdvisor reviews added yet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
