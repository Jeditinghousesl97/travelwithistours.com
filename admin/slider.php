<?php
require_once '../config/db.php';

$success = '';
$error = '';
$edit_mode = false;
$slide_to_edit = null;

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

function ensure_hero_slides_media_columns(PDO $pdo): void
{
    $column_rows = $pdo->query("SHOW COLUMNS FROM hero_slides")->fetchAll(PDO::FETCH_ASSOC);
    $columns = array_column($column_rows, 'Field');

    if (!in_array('slide_type', $columns, true)) {
        $pdo->exec("ALTER TABLE hero_slides ADD COLUMN slide_type VARCHAR(20) NOT NULL DEFAULT 'image' AFTER image_path");
    }

    if (!in_array('video_url', $columns, true)) {
        $pdo->exec("ALTER TABLE hero_slides ADD COLUMN video_url TEXT NULL AFTER slide_type");
    }

    foreach ($column_rows as $column) {
        if (($column['Field'] ?? '') !== 'image_path') {
            continue;
        }

        if (($column['Null'] ?? 'NO') === 'YES') {
            break;
        }

        $column_type = $column['Type'] ?? 'VARCHAR(255)';
        $default_sql = '';

        if (array_key_exists('Default', $column)) {
            if ($column['Default'] === null) {
                $default_sql = ' DEFAULT NULL';
            } else {
                $default_sql = " DEFAULT " . $pdo->quote((string) $column['Default']);
            }
        }

        $pdo->exec("ALTER TABLE hero_slides MODIFY image_path {$column_type} NULL{$default_sql}");
        break;
    }
}

function upload_hero_media(array $file, string $target_dir, array $allowed_ext, string $prefix): ?string
{
    if (!isset($file['error']) || $file['error'] !== 0) {
        return null;
    }

    if (!file_exists($target_dir)) {
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

function delete_local_media(?string $path): void
{
    if (!$path) {
        return;
    }

    $full_path = "../" . ltrim($path, '/');
    if (file_exists($full_path)) {
        unlink($full_path);
    }
}

ensure_hero_slides_media_columns($pdo);

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
    $error = "The uploaded request was too large for the server. Current upload limit: " . format_bytes_human($effective_upload_limit) . ". Please use a smaller file, host the video externally, or increase the PHP upload limits.";
}

// Handle Delete (Logic First)
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("SELECT image_path, slide_type, video_url FROM hero_slides WHERE id = ?");
    $stmt->execute([$id]);
    $slide = $stmt->fetch();

    if ($slide) {
        $stmt = $pdo->prepare("DELETE FROM hero_slides WHERE id = ?");
        if ($stmt->execute([$id])) {
            delete_local_media($slide['image_path']);
            if (($slide['slide_type'] ?? 'image') === 'video' && !empty($slide['video_url']) && str_starts_with($slide['video_url'], 'assets/images/hero/')) {
                delete_local_media($slide['video_url']);
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
    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $button_text = trim($_POST['button_text'] ?? '');
    $button_link = trim($_POST['button_link'] ?? '');
    $display_order = $_POST['display_order'] ?? 0;
    $slide_type = $_POST['slide_type'] ?? 'image';
    $external_video_url = trim($_POST['video_url'] ?? '');

    $id = $_POST['id'] ?? null;

    $existing_slide = null;
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM hero_slides WHERE id = ?");
        $stmt->execute([$id]);
        $existing_slide = $stmt->fetch();
    }

    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_INI_SIZE) {
        $error = "The image exceeds the server upload limit of " . format_bytes_human($effective_upload_limit) . ".";
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image_path = upload_hero_media($_FILES['image'], "../assets/images/hero/", ['jpg', 'jpeg', 'png', 'webp', 'gif'], 'image_');
        if (!$image_path) {
            $error = "Failed to upload image.";
        }
    }

    $uploaded_video_path = null;
    if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] == UPLOAD_ERR_INI_SIZE) {
        $error = "The video exceeds the server upload limit of " . format_bytes_human($effective_upload_limit) . ". Please upload a smaller file or use an external/direct video URL instead.";
    } elseif (isset($_FILES['video_file']) && $_FILES['video_file']['error'] == 0) {
        $uploaded_video_path = upload_hero_media($_FILES['video_file'], "../assets/images/hero/", ['mp4', 'webm', 'ogg', 'mov', 'm4v'], 'video_');
        if (!$uploaded_video_path) {
            $error = "Failed to upload video. Allowed formats: mp4, webm, ogg, mov, m4v.";
        }
    }

    if (!$error) {
        $video_url = null;
        if ($slide_type === 'image') {
            $video_url = null;
            if ($uploaded_video_path) {
                delete_local_media($uploaded_video_path);
                $uploaded_video_path = null;
            }
        } elseif ($slide_type === 'video') {
            $video_url = $uploaded_video_path ?: ($external_video_url ?: ($existing_slide['video_url'] ?? null));
            if (!$video_url) {
                $error = "Please upload a video file or provide a direct video URL.";
            }
        } elseif (in_array($slide_type, ['youtube', 'embed'], true)) {
            $video_url = $external_video_url ?: ($existing_slide['video_url'] ?? null);
            if ($uploaded_video_path) {
                delete_local_media($uploaded_video_path);
                $uploaded_video_path = null;
            }
            if (!$video_url) {
                $error = "Please provide a video URL for this slide type.";
            }
        } else {
            $error = "Invalid slide type selected.";
        }
    }

    if (!$error) {
        $final_image_path = $image_path ?: ($existing_slide['image_path'] ?? null);

        if ($id) {
            // Update
            if ($image_path && !empty($existing_slide['image_path']) && $existing_slide['image_path'] !== $image_path) {
                delete_local_media($existing_slide['image_path']);
            }

            if (($existing_slide['slide_type'] ?? 'image') === 'video' && !empty($existing_slide['video_url']) && $existing_slide['video_url'] !== $video_url && str_starts_with($existing_slide['video_url'], 'assets/images/hero/')) {
                delete_local_media($existing_slide['video_url']);
            }

            $stmt = $pdo->prepare("UPDATE hero_slides SET image_path=?, slide_type=?, video_url=?, title=?, subtitle=?, button_text=?, button_link=?, display_order=? WHERE id=?");
            $result = $stmt->execute([$final_image_path, $slide_type, $video_url, $title, $subtitle, $button_text, $button_link, $display_order, $id]);

            if ($result) {
                header("Location: slider.php?msg=updated");
                exit;
            } else {
                $error = "Database error during update.";
            }
        } else {
            // Create
            if ($slide_type === 'image' && !$image_path) {
                $error = "Please select an image for a new image slide.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO hero_slides (image_path, slide_type, video_url, title, subtitle, button_text, button_link, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$final_image_path, $slide_type, $video_url, $title, $subtitle, $button_text, $button_link, $display_order])) {
                    header("Location: slider.php?msg=added");
                    exit;
                }
                else {
                    $error = "Database error.";
                }
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
                    <label class="form-label">Slide Type</label>
                    <select name="slide_type" id="slide_type" class="form-control">
                        <?php $current_slide_type = $edit_mode ? ($slide_to_edit['slide_type'] ?? 'image') : 'image'; ?>
                        <option value="image" <?php echo $current_slide_type === 'image' ? 'selected' : ''; ?>>Image</option>
                        <option value="youtube" <?php echo $current_slide_type === 'youtube' ? 'selected' : ''; ?>>YouTube Video</option>
                        <option value="video" <?php echo $current_slide_type === 'video' ? 'selected' : ''; ?>>Self Hosted / Direct Video URL</option>
                        <option value="embed" <?php echo $current_slide_type === 'embed' ? 'selected' : ''; ?>>Other Embed Link</option>
                    </select>
                    <small style="color: #666; display: block; margin-top: 5px;">Use Image for normal slides, YouTube for YouTube links, Video for uploaded/direct `.mp4`/`.webm`, and Embed for other iframe-ready links.</small>
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

                <div class="form-group media-field-group media-url-group">
                    <label class="form-label">Video / Embed URL</label>
                    <input type="text" name="video_url" class="form-control" placeholder="YouTube link, direct video URL, or embed URL" value="<?php echo $edit_mode ? htmlspecialchars($slide_to_edit['video_url'] ?? '') : ''; ?>">
                    <small style="color: #666; display: block; margin-top: 5px;">Examples: `https://youtu.be/...`, direct `.mp4` URL, or an embeddable player URL.</small>
                </div>

                <div class="form-group media-field-group video-upload-group">
                    <label class="form-label">Upload Video File</label>
                    <input type="file" name="video_file" class="form-control" accept="video/mp4,video/webm,video/ogg,video/quicktime,.mp4,.webm,.ogg,.mov,.m4v" style="padding: 8px;">
                    <small style="color: #666; display: block; margin-top: 5px;">Optional. If uploaded, it will be used for `Self Hosted / Direct Video URL` slides. Current server upload limit: <?php echo htmlspecialchars(format_bytes_human($effective_upload_limit)); ?>.</small>
                </div>
            </div>

            <!-- Right Side: Media Upload / Preview -->
            <div class="form-section" style="background: #fcfcfc; border: 1px dashed #ddd; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 25px;">
                <label class="form-label" style="align-self: flex-start;">Slide Image / Poster (Recommended: 1920x800px)</label>
                
                <?php if ($edit_mode && $slide_to_edit['image_path']): ?>
                    <div style="margin: 15px 0; text-align: center;">
                        <img src="../<?php echo $slide_to_edit['image_path']; ?>" style="width: 100%; max-width: 300px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border: 2px solid #fff;">
                        <p style="font-size: 11px; color: #888; margin-top: 8px;">Existing Image / Poster</p>
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
                    <input type="file" name="image" class="form-control" accept="image/*" style="padding: 8px;">
                    <small style="color: #666; display: block; margin-top: 5px;">Required for image slides. Optional as a poster/fallback for video slides.</small>
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
                    <th style="background: transparent; padding-bottom: 15px;">Type</th>
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
                            <?php $slide_type = $slide['slide_type'] ?? 'image'; ?>
                            <?php if (!empty($slide['image_path'])): ?>
                                <img src="../<?php echo htmlspecialchars($slide['image_path']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php elseif ($slide_type === 'youtube'): ?>
                                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #111; color: #fff; font-size: 24px;">
                                    <i class="fab fa-youtube"></i>
                                </div>
                            <?php else: ?>
                                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #111; color: #fff; font-size: 24px;">
                                    <i class="fas fa-video"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td style="padding: 20px; border-top: 1px solid #eee; border-bottom: 1px solid #eee;">
                        <span style="font-size: 12px; color: #555; background: #f0f0f0; padding: 6px 10px; border-radius: 12px; text-transform: capitalize;">
                            <?php echo htmlspecialchars($slide_type); ?>
                        </span>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const slideTypeSelect = document.getElementById('slide_type');
        const mediaUrlGroup = document.querySelector('.media-url-group');
        const videoUploadGroup = document.querySelector('.video-upload-group');

        function toggleMediaFields() {
            const type = slideTypeSelect ? slideTypeSelect.value : 'image';
            const isImage = type === 'image';
            const isVideo = type === 'video';

            if (mediaUrlGroup) {
                mediaUrlGroup.style.display = isImage ? 'none' : 'block';
            }

            if (videoUploadGroup) {
                videoUploadGroup.style.display = isVideo ? 'block' : 'none';
            }
        }

        if (slideTypeSelect) {
            slideTypeSelect.addEventListener('change', toggleMediaFields);
            toggleMediaFields();
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
