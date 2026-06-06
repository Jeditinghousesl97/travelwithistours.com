<?php
require_once '../config/db.php';
require_once '../includes/short_videos.php';

$success = '';
$error = '';
$edit_mode = false;
$video_to_edit = null;

function parse_ini_size_to_bytes(string $value): int
{
    $value = trim($value);
    if ($value === '') {
        return 0;
    }

    $unit = strtolower(substr($value, -1));
    $number = (float) $value;

    return match ($unit) {
        'g' => (int) ($number * 1024 * 1024 * 1024),
        'm' => (int) ($number * 1024 * 1024),
        'k' => (int) ($number * 1024),
        default => (int) $number,
    };
}

function format_bytes_human(int $bytes): string
{
    if ($bytes >= 1024 * 1024 * 1024) {
        return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
    }

    if ($bytes >= 1024 * 1024) {
        return round($bytes / (1024 * 1024), 2) . ' MB';
    }

    if ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    }

    return $bytes . ' B';
}

function upload_short_video_media(array $file, string $target_dir, array $allowed_ext, string $prefix): ?string
{
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_ext, true)) {
        return null;
    }

    $file_name = $prefix . time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', basename($file['name']));
    $target_file = $target_dir . $file_name;

    if (!move_uploaded_file($file['tmp_name'], $target_file)) {
        return null;
    }

    return str_replace('../', '', $target_file);
}

function delete_short_video_media(?string $path): void
{
    if (!$path) {
        return;
    }

    $full_path = '../' . ltrim($path, '/');
    if (file_exists($full_path)) {
        unlink($full_path);
    }
}

ensure_short_videos_schema($pdo);

$upload_max_bytes = parse_ini_size_to_bytes((string) ini_get('upload_max_filesize'));
$post_max_bytes = parse_ini_size_to_bytes((string) ini_get('post_max_size'));
$effective_upload_limit = min(array_filter([$upload_max_bytes, $post_max_bytes])) ?: max($upload_max_bytes, $post_max_bytes);

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && empty($_POST)
    && empty($_FILES)
    && !empty($_SERVER['CONTENT_LENGTH'])
    && (int) $_SERVER['CONTENT_LENGTH'] > 0
) {
    $error = 'The uploaded request was too large for the server. Current upload limit: ' . format_bytes_human($effective_upload_limit) . '.';
}

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare('SELECT video_path, poster_path FROM short_videos WHERE id = ?');
    $stmt->execute([$id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($record) {
        $stmt = $pdo->prepare('DELETE FROM short_videos WHERE id = ?');
        if ($stmt->execute([$id])) {
            delete_short_video_media($record['video_path'] ?? null);
            delete_short_video_media($record['poster_path'] ?? null);
            header('Location: short-videos.php?msg=deleted');
            exit;
        }
        $error = 'Failed to delete short video.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_short_video'])) {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : null;
    $title = trim($_POST['title'] ?? '');
    $caption = trim($_POST['caption'] ?? '');
    $display_order = (int) ($_POST['display_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $existing_video = null;
    if ($id) {
        $stmt = $pdo->prepare('SELECT * FROM short_videos WHERE id = ?');
        $stmt->execute([$id]);
        $existing_video = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    $video_path = null;
    if (!$error && isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_INI_SIZE) {
        $error = 'The video exceeds the server upload limit of ' . format_bytes_human($effective_upload_limit) . '.';
    } elseif (!$error && isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
        $video_path = upload_short_video_media($_FILES['video_file'], '../assets/videos/shorts/', ['mp4', 'webm', 'ogg', 'mov', 'm4v'], 'short_video_');
        if (!$video_path) {
            $error = 'Failed to upload the video. Allowed formats: mp4, webm, ogg, mov, m4v.';
        }
    }

    $poster_path = null;
    if (!$error && isset($_FILES['poster_file']) && $_FILES['poster_file']['error'] === UPLOAD_ERR_INI_SIZE) {
        $error = 'The poster image exceeds the server upload limit of ' . format_bytes_human($effective_upload_limit) . '.';
    } elseif (!$error && isset($_FILES['poster_file']) && $_FILES['poster_file']['error'] === UPLOAD_ERR_OK) {
        $poster_path = upload_short_video_media($_FILES['poster_file'], '../assets/images/short-videos/', ['jpg', 'jpeg', 'png', 'webp'], 'short_video_poster_');
        if (!$poster_path) {
            $error = 'Failed to upload the poster image. Allowed formats: jpg, jpeg, png, webp.';
        }
    }

    if (!$error) {
        $final_video_path = $video_path ?: ($existing_video['video_path'] ?? null);
        $final_poster_path = $poster_path ?: ($existing_video['poster_path'] ?? null);

        if (!$final_video_path) {
            $error = 'Please upload a video clip.';
        }
    }

    if (!$error) {
        if ($id && $existing_video) {
            if ($video_path && !empty($existing_video['video_path']) && $existing_video['video_path'] !== $video_path) {
                delete_short_video_media($existing_video['video_path']);
            }

            if ($poster_path && !empty($existing_video['poster_path']) && $existing_video['poster_path'] !== $poster_path) {
                delete_short_video_media($existing_video['poster_path']);
            }

            $stmt = $pdo->prepare('UPDATE short_videos SET title = ?, caption = ?, video_path = ?, poster_path = ?, display_order = ?, is_active = ? WHERE id = ?');
            $result = $stmt->execute([$title, $caption, $final_video_path, $final_poster_path, $display_order, $is_active, $id]);

            if ($result) {
                header('Location: short-videos.php?msg=updated');
                exit;
            }

            $error = 'Database error during update.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO short_videos (title, caption, video_path, poster_path, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?)');
            $result = $stmt->execute([$title, $caption, $final_video_path, $final_poster_path, $display_order, $is_active]);

            if ($result) {
                header('Location: short-videos.php?msg=added');
                exit;
            }

            $error = 'Database error during insert.';
        }
    }
}

if (isset($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM short_videos WHERE id = ?');
    $stmt->execute([$edit_id]);
    $video_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($video_to_edit) {
        $edit_mode = true;
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added') {
        $success = 'Short video added successfully!';
    } elseif ($_GET['msg'] === 'updated') {
        $success = 'Short video updated successfully!';
    } elseif ($_GET['msg'] === 'deleted') {
        $success = 'Short video deleted successfully!';
    }
}

include 'includes/header.php';

$short_videos = $pdo->query('SELECT * FROM short_videos ORDER BY display_order ASC, created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="header">
    <h2>Manage Short Videos</h2>
    <?php if ($edit_mode): ?>
        <a href="short-videos.php" class="btn btn-secondary">
            <i class="fas fa-times"></i> Cancel Edit
        </a>
    <?php endif; ?>
</div>

<?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

<div class="card">
    <h3 style="margin-top: 0; margin-bottom: 25px; font-size: 18px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
        <i class="fas <?php echo $edit_mode ? 'fa-edit' : 'fa-video'; ?>" style="color: var(--primary-color);"></i>
        <?php echo $edit_mode ? 'Edit Short Video' : 'Add New Short Video'; ?>
    </h3>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="save_short_video" value="1">
        <?php if ($edit_mode): ?>
            <input type="hidden" name="id" value="<?php echo (int) $video_to_edit['id']; ?>">
        <?php endif; ?>

        <div class="form-grid">
            <div class="form-section" style="background: none; border: none; padding: 0; margin-bottom: 0;">
                <div class="form-group">
                    <label class="form-label">Video Title</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Ella train journey" value="<?php echo htmlspecialchars($video_to_edit['title'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Caption</label>
                    <textarea name="caption" class="form-control" rows="4" placeholder="Optional short description"><?php echo htmlspecialchars($video_to_edit['caption'] ?? ''); ?></textarea>
                </div>

                <div class="form-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                    <div class="form-group">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="display_order" class="form-control" value="<?php echo htmlspecialchars((string) ($video_to_edit['display_order'] ?? '0')); ?>">
                    </div>
                    <div class="form-group" style="display: flex; align-items: end;">
                        <label style="display: flex; align-items: center; gap: 10px; font-weight: 600;">
                            <input type="checkbox" name="is_active" value="1" <?php echo !isset($video_to_edit['is_active']) || (int) $video_to_edit['is_active'] === 1 ? 'checked' : ''; ?>>
                            Show on homepage
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Upload Short Video Clip</label>
                    <input type="file" name="video_file" class="form-control" accept="video/mp4,video/webm,video/ogg,video/quicktime,.mp4,.webm,.ogg,.mov,.m4v" style="padding: 8px;" <?php echo $edit_mode ? '' : 'required'; ?>>
                    <small style="color: #666; display: block; margin-top: 5px;">Allowed formats: mp4, webm, ogg, mov, m4v. Current upload limit: <?php echo htmlspecialchars(format_bytes_human($effective_upload_limit)); ?>.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Poster Image (Optional)</label>
                    <input type="file" name="poster_file" class="form-control" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp" style="padding: 8px;">
                    <small style="color: #666; display: block; margin-top: 5px;">Optional cover image shown before playback.</small>
                </div>
            </div>

            <div class="form-section" style="background: #fcfcfc; border: 1px dashed #ddd; display: flex; flex-direction: column; gap: 20px;">
                <div>
                    <label class="form-label">Current Preview</label>
                    <?php if (!empty($video_to_edit['video_path'])): ?>
                        <video controls muted playsinline poster="<?php echo htmlspecialchars((string) ($video_to_edit['poster_path'] ?? '')); ?>" style="width: 100%; border-radius: 12px; background: #111;">
                            <source src="../<?php echo htmlspecialchars($video_to_edit['video_path']); ?>">
                        </video>
                    <?php else: ?>
                        <div style="height: 260px; border-radius: 12px; background: linear-gradient(135deg, #f2f7f8, #e4f4f6); display: flex; align-items: center; justify-content: center; color: #7a8a8f; text-align: center; padding: 20px;">
                            Upload a short video clip to preview it here.
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($video_to_edit['poster_path'])): ?>
                    <div>
                        <label class="form-label">Current Poster</label>
                        <img src="../<?php echo htmlspecialchars($video_to_edit['poster_path']); ?>" alt="" style="width: 100%; max-height: 220px; object-fit: cover; border-radius: 12px;">
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div style="margin-top: 25px; display: flex; gap: 12px;">
            <button type="submit" class="btn" style="padding: 12px 28px;">
                <i class="fas fa-save"></i> <?php echo $edit_mode ? 'Update Video' : 'Add Video'; ?>
            </button>
            <?php if ($edit_mode): ?>
                <a href="short-videos.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <h3 style="margin-top: 0; margin-bottom: 20px;">Short Videos Library</h3>

    <?php if (count($short_videos) > 0): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
            <?php foreach ($short_videos as $video): ?>
                <div style="background: #fff; border: 1px solid #eee; border-radius: 14px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.04);">
                    <div style="position: relative; background: #111;">
                        <video muted playsinline preload="metadata" poster="<?php echo htmlspecialchars((string) ($video['poster_path'] ?? '')); ?>" style="width: 100%; height: 240px; object-fit: cover; display: block;">
                            <source src="../<?php echo htmlspecialchars($video['video_path']); ?>">
                        </video>
                        <span style="position: absolute; top: 12px; left: 12px; background: <?php echo (int) $video['is_active'] === 1 ? '#28a745' : '#6c757d'; ?>; color: #fff; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600;">
                            <?php echo (int) $video['is_active'] === 1 ? 'Live' : 'Hidden'; ?>
                        </span>
                    </div>
                    <div style="padding: 18px;">
                        <div style="display: flex; justify-content: space-between; gap: 15px; align-items: start; margin-bottom: 10px;">
                            <h4 style="margin: 0; font-size: 18px;"><?php echo htmlspecialchars($video['title']); ?></h4>
                            <span style="font-size: 12px; color: #888; white-space: nowrap;">Order: <?php echo (int) $video['display_order']; ?></span>
                        </div>
                        <?php $card_caption = (string) ($video['caption'] ?? ''); ?>
                        <p style="margin: 0 0 16px; color: #666; font-size: 14px; min-height: 44px;"><?php echo htmlspecialchars(strlen($card_caption) > 120 ? substr($card_caption, 0, 117) . '...' : $card_caption); ?></p>
                        <div style="display: flex; gap: 10px;">
                            <a href="short-videos.php?edit=<?php echo (int) $video['id']; ?>" class="btn" style="padding: 9px 14px; font-size: 13px;">
                                <i class="fas fa-pen"></i> Edit
                            </a>
                            <a href="short-videos.php?delete=<?php echo (int) $video['id']; ?>" class="btn" style="padding: 9px 14px; font-size: 13px; background: #dc3545;" onclick="return confirm('Delete this short video?');">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 50px 20px; color: #888;">
            <i class="fas fa-film" style="font-size: 42px; margin-bottom: 12px; color: #c7d3d7;"></i>
            <p style="margin: 0;">No short videos yet. Upload your first clip above.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
