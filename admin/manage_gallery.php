<?php
include '../config.php';
session_start();

// Ensure admin is logged in
if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$success = "";
$error = "";

// --- (All your PHP logic for Add, Update, and Delete remains exactly the same) ---

// --- Handle Add Gallery ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_gallery'])) {
    $event_name = trim($_POST['event_name']);
    $event_date = $_POST['event_date'];
    $thumbnailPath = $_POST['event_thumbnail_url'];

    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
        $uploadDir = "../uploads/events/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $ext = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
        $newName = strtolower(preg_replace("/[^a-zA-Z0-9]/", "", $event_name)) . "_thumb." . $ext;
        $targetFile = $uploadDir . $newName;
        if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $targetFile)) {
            $thumbnailPath = $targetFile;
        }
    }
    
    $imageUrls = [];
    if (isset($_FILES['images']) && count($_FILES['images']['name']) > 0) {
        $uploadDir = "../uploads/After_event/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if (!empty($tmp_name) && $_FILES['images']['error'][$key] == 0) {
                $ext = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
                $newName = strtolower(preg_replace("/[^a-zA-Z0-9]/", "", $event_name)) . "_" . time() . "_" . $key . "." . $ext;
                $targetFile = $uploadDir . $newName;
                if (move_uploaded_file($tmp_name, $targetFile)) {
                    $imageUrls[] = $targetFile;
                }
            }
        }
    }

    $imagesStr = implode(",", $imageUrls);
    $sql = "INSERT INTO event_gallery (event_name, event_date, thumbnail, image_urls) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $event_name, $event_date, $thumbnailPath, $imagesStr);
    if ($stmt->execute()) {
        $success = "✅ Gallery for '{$event_name}' created successfully!";
    } else { $error = "❌ Error: A gallery for this event might already exist."; }
    $stmt->close();
}

// --- Handle Update Gallery ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_gallery'])) {
    $gid = intval($_POST['gallery_id']);
    $current_thumbnail = $_POST['current_thumbnail'];
    $current_images_str = $_POST['current_images'];
    $images_to_delete_str = $_POST['images_to_delete'];
    $thumbnailPath = $current_thumbnail;

    if (isset($_FILES['edit_thumbnail']) && $_FILES['edit_thumbnail']['error'] == 0) {
        $uploadDir = "../uploads/events/";
        $ext = pathinfo($_FILES['edit_thumbnail']['name'], PATHINFO_EXTENSION);
        $newName = "gallery_thumb_" . $gid . "_" . time() . "." . $ext;
        $targetFile = $uploadDir . $newName;
        if(move_uploaded_file($_FILES['edit_thumbnail']['tmp_name'], $targetFile)) {
            $thumbnailPath = $targetFile;
            if ($current_thumbnail && $current_thumbnail != '../uploads/events/default.jpg') @unlink($current_thumbnail);
        }
    }

    $current_images = !empty($current_images_str) ? explode(',', $current_images_str) : [];
    $images_to_delete = !empty($images_to_delete_str) ? explode(',', $images_to_delete_str) : [];
    foreach ($images_to_delete as $img_path) {
        if (!empty($img_path)) @unlink(trim($img_path));
    }
    $remaining_images = array_diff($current_images, $images_to_delete);

    $newImageUrls = [];
    if (isset($_FILES['add_images'])) {
        $uploadDir = "../uploads/After_event/";
        foreach ($_FILES['add_images']['tmp_name'] as $key => $tmp_name) {
             if (!empty($tmp_name) && $_FILES['add_images']['error'][$key] == 0) {
                $ext = pathinfo($_FILES['add_images']['name'][$key], PATHINFO_EXTENSION);
                $newName = "gallery_img_" . $gid . "_" . time() . "_" . $key . "." . $ext;
                $targetFile = $uploadDir . $newName;
                if (move_uploaded_file($tmp_name, $targetFile)) {
                    $newImageUrls[] = $targetFile;
                }
            }
        }
    }
    
    $final_images = array_merge($remaining_images, $newImageUrls);
    $final_images_str = implode(',', array_map('trim', $final_images));

    $sql = "UPDATE event_gallery SET thumbnail=?, image_urls=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $thumbnailPath, $final_images_str, $gid);
    if ($stmt->execute()) {
        $success = "🔄 Gallery updated successfully!";
    } else { $error = "❌ Error updating gallery."; }
    $stmt->close();
}


// --- Handle Delete Gallery ---
if (isset($_GET['delete'])) {
    $gid = intval($_GET['delete']);
    $stmt = $conn->prepare("SELECT thumbnail, image_urls FROM event_gallery WHERE id=?");
    $stmt->bind_param("i", $gid);
    $stmt->execute();
    $result = $stmt->get_result();
    if($row = $result->fetch_assoc()) {
        if ($row['thumbnail'] && $row['thumbnail'] != '../uploads/events/default.jpg') @unlink($row['thumbnail']);
        $images = explode(',', $row['image_urls']);
        foreach($images as $img) {
            if(!empty($img)) @unlink(trim($img));
        }
    }
    $stmt->close();
    $stmt = $conn->prepare("DELETE FROM event_gallery WHERE id = ?");
    $stmt->bind_param("i", $gid);
    if ($stmt->execute()) $success = "🗑️ Gallery deleted successfully.";
    else $error = "❌ Error deleting gallery.";
    $stmt->close();
}

// --- Fetch existing events for the dropdown ---
$events = [];
$eventQuery = $conn->query("SELECT id, event_name, event_date, image_url FROM events ORDER BY event_date DESC");
if ($eventQuery) {
    while ($row = $eventQuery->fetch_assoc()) {
        $events[] = $row;
    }
}

// --- Fetch existing galleries for display ---
$galleries = [];
$galleryQuery = $conn->query("SELECT * FROM event_gallery ORDER BY event_date DESC");
if ($galleryQuery) {
    while ($row = $galleryQuery->fetch_assoc()) {
        $galleries[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Gallery - Eventra</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <style>
        :root { --sidebar-bg: #1a253c; --primary-color: #4f46e5; }
        body { font-family: 'Poppins', sans-serif; background: #f4f6fb; }
        .sidebar { height: 100vh; background: var(--sidebar-bg); color: #fff; position: fixed; left: 0; top: 0; width: 250px; display: flex; flex-direction: column; }
        .sidebar-header { padding: 1.5rem; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header h2 { font-weight: 700; margin: 0; font-size: 1.5rem; }
        .sidebar-nav { flex-grow: 1; padding: 1rem; }
        .sidebar-nav a { display: flex; align-items: center; gap: 0.75rem; padding: 0.8rem 1rem; margin: 0.3rem 0; color: #e0e0e0; text-decoration: none; border-radius: 0.5rem; transition: all 0.3s; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: var(--primary-color); color: #fff; }
        .sidebar-footer { padding: 1rem; border-top: 1px solid rgba(255,255,255,0.1); }
        .main-content { margin-left: 250px; padding: 2rem; }
        .card { border: none; border-radius: 1rem; box-shadow: 0 6px 20px rgba(0,0,0,0.07); }
        .image-preview-container { display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 1rem; }
        .preview-img { width: 100px; height: 100px; object-fit: cover; border-radius: 0.5rem; }
        .gallery-card-img { height: 200px; object-fit: cover; }
        .existing-img-container { position: relative; }
        .delete-img-btn {
            position: absolute; top: 5px; right: 5px;
            width: 24px; height: 24px;
            background-color: rgba(0,0,0,0.6);
            color: white; border: none; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; transition: background-color 0.2s;
        }
        .delete-img-btn:hover { background-color: #dc3545; }
        .existing-img-container.marked-for-deletion img { opacity: 0.4; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header"><h2><i class="fa-solid fa-shield-halved"></i> Eventra</h2></div>
        <nav class="sidebar-nav">
            <a href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="manage_users.php"><i class="fa-solid fa-users"></i> Manage Users</a>
            <a href="manage_events.php"><i class="fa-solid fa-calendar-days"></i> Manage Events</a>
            <a href="manage_gallery.php" class="active"><i class="fa-solid fa-image"></i> Event Gallery</a>
            <a href="manage_feedback.php"><i class="fa-solid fa-comments"></i> Feedback</a>
        </nav>
        <div class="sidebar-footer"><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></div>
    </div>

    <main class="main-content">
        <h2 class="h3 mb-4 fw-bold">🖼️ Manage Event Galleries</h2>

        <div id="alert-container">
            <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show"><?= $success ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show"><?= $error ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
        </div>
        
        <div class="accordion mb-4" id="addGalleryAccordion">
            <div class="accordion-item border-0 shadow-sm" style="border-radius: 1rem;">
                <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button collapsed fw-medium" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                        <i class="fa-solid fa-plus me-2"></i> Create a New Gallery
                    </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#addGalleryAccordion">
                    <div class="accordion-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row g-3">
                                <div class="col-12"><label class="form-label">1. Select an Event</label><select name="event_id" id="eventSelect" class="form-select" required onchange="fillEventDetails()"><option value="" selected disabled>-- Select Event --</option><?php foreach ($events as $e): ?><option value="<?= $e['id'] ?>" data-name="<?= htmlspecialchars($e['event_name']) ?>" data-date="<?= $e['event_date'] ?>" data-thumbnail="<?= htmlspecialchars($e['image_url']) ?>"><?= htmlspecialchars($e['event_name']) ?> (<?= date("M j, Y", strtotime($e['event_date'])) ?>)</option><?php endforeach; ?></select></div>
                                <input type="hidden" name="event_name" id="eventName"><input type="hidden" name="event_date" id="eventDate"><input type="hidden" name="event_thumbnail_url" id="eventThumbnailUrl">
                                <div class="col-md-6"><label class="form-label">2. Upload Thumbnail (Optional)</label><input type="file" name="thumbnail" class="form-control"></div>
                                <div class="col-md-6"><label class="form-label">3. Upload Content Images</label><input type="file" name="images[]" multiple class="form-control" id="imagesInput"></div>
                                <div class="col-12"><div class="image-preview-container" id="imagePreviewContainer"></div></div>
                                <div class="col-12"><button type="submit" name="add_gallery" class="btn btn-primary w-100">Create Gallery</button></div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title fw-bold mb-0">📋 Existing Galleries</h5>
                <input type="text" id="gallery-search" class="form-control" style="max-width: 300px;" placeholder="Search by event name...">
            </div>
            <div class="row g-4" id="gallery-grid">
                <?php if (!empty($galleries)): foreach ($galleries as $gallery): $image_count = count(array_filter(explode(',', $gallery['image_urls']))); ?>
                    <div class="col-lg-4 col-md-6 gallery-item" data-name="<?= strtolower(htmlspecialchars($gallery['event_name'])) ?>">
                        <div class="card h-100">
                            <img src="<?= htmlspecialchars($gallery['thumbnail']) ?>" class="card-img-top gallery-card-img" alt="<?= htmlspecialchars($gallery['event_name']) ?>">
                            <div class="card-body">
                                <h5 class="card-title fw-bold"><?= htmlspecialchars($gallery['event_name']) ?></h5>
                                <p class="card-text text-muted"><i class="fa-solid fa-images me-2"></i> <?= $image_count ?> images</p>
                            </div>
                            <div class="card-footer bg-white border-0 text-end">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editGalleryModal" 
                                    data-id="<?= $gallery['id'] ?>" 
                                    data-name="<?= htmlspecialchars($gallery['event_name']) ?>" 
                                    data-thumbnail="<?= htmlspecialchars($gallery['thumbnail']) ?>" 
                                    data-images="<?= htmlspecialchars($gallery['image_urls']) ?>">
                                    <i class="fa-solid fa-pencil"></i> Edit
                                </button>
                                <a href="?delete=<?= $gallery['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this gallery?')"><i class="fa-solid fa-trash-can"></i> Delete</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="col-12" id="no-results" style="display: none;"><div class="alert alert-warning">No galleries match your search.</div></div>
                <?php else: ?>
                    <div class="col-12"><div class="alert alert-info">No galleries have been created yet.</div></div>
                <?php endif; ?>
            </div>
        </div></div>
    </main>

    <div class="modal fade" id="editGalleryModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header"><h5 class="modal-title fw-bold" id="editModalTitle">Edit Gallery</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <input type="hidden" name="gallery_id" id="edit_gallery_id">
                        <input type="hidden" name="current_thumbnail" id="edit_current_thumbnail">
                        <input type="hidden" name="current_images" id="edit_current_images">
                        <input type="hidden" name="images_to_delete" id="edit_images_to_delete">
                        <h6>Existing Images</h6>
                        <div class="image-preview-container mb-3 bg-light p-3 rounded" id="existingImagesContainer"></div>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Change Thumbnail (Optional)</label><input type="file" name="edit_thumbnail" class="form-control"></div>
                            <div class="col-md-6"><label class="form-label">Add More Images (Optional)</label><input type="file" name="add_images[]" multiple class="form-control"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_gallery" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// All JavaScript from the previous response remains the same.
// This ensures that all functionality like alerts, image previews, 
// modal handling, and search continues to work correctly.
document.addEventListener('DOMContentLoaded', function() {
    const alert = document.querySelector('#alert-container .alert');
    if (alert) { setTimeout(() => new bootstrap.Alert(alert).close(), 5000); }
    
    document.getElementById('imagesInput').addEventListener('change', function(event) {
        const previewContainer = document.getElementById('imagePreviewContainer');
        previewContainer.innerHTML = ''; 
        if (this.files) {
            Array.from(this.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.classList.add('preview-img');
                    previewContainer.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        }
    });

    const editModal = document.getElementById('editGalleryModal');
    const imagesToDeleteInput = document.getElementById('edit_images_to_delete');
    
    editModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const id = button.dataset.id;
        const name = button.dataset.name;
        const thumbnail = button.dataset.thumbnail;
        const imagesStr = button.dataset.images;
        
        document.getElementById('editModalTitle').innerText = `Edit Gallery: ${name}`;
        document.getElementById('edit_gallery_id').value = id;
        document.getElementById('edit_current_thumbnail').value = thumbnail;
        document.getElementById('edit_current_images').value = imagesStr;
        imagesToDeleteInput.value = '';

        const existingImagesContainer = document.getElementById('existingImagesContainer');
        existingImagesContainer.innerHTML = 'No images yet.';
        if (imagesStr) {
            existingImagesContainer.innerHTML = '';
            const images = imagesStr.split(',').filter(Boolean); // Filter out empty strings
            images.forEach(imgPath => {
                const container = document.createElement('div');
                container.className = 'existing-img-container';

                const img = document.createElement('img');
                img.src = imgPath;
                img.className = 'preview-img';

                const delBtn = document.createElement('button');
                delBtn.type = 'button';
                delBtn.className = 'delete-img-btn';
                delBtn.innerHTML = '<i class="fa-solid fa-times"></i>';
                
                delBtn.onclick = () => {
                    container.classList.toggle('marked-for-deletion');
                    let imagesToDelete = imagesToDeleteInput.value.split(',').filter(Boolean);
                    if (container.classList.contains('marked-for-deletion')) {
                        imagesToDelete.push(imgPath);
                    } else {
                        imagesToDelete = imagesToDelete.filter(p => p !== imgPath);
                    }
                    imagesToDeleteInput.value = imagesToDelete.join(',');
                };
                
                container.appendChild(img);
                container.appendChild(delBtn);
                existingImagesContainer.appendChild(container);
            });
        }
    });

    const searchInput = document.getElementById('gallery-search');
    const allItems = document.querySelectorAll('#gallery-grid .gallery-item');
    const noResultsMsg = document.getElementById('no-results');

    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        let visibleCount = 0;
        allItems.forEach(item => {
            const name = item.dataset.name || '';
            if (name.includes(searchTerm)) {
                item.style.display = 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });
        noResultsMsg.style.display = visibleCount === 0 ? 'block' : 'none';
    });
});

function fillEventDetails() {
    const select = document.getElementById('eventSelect');
    const selected = select.options[select.selectedIndex];
    document.getElementById('eventName').value = selected.getAttribute('data-name') || '';
    document.getElementById('eventDate').value = selected.getAttribute('data-date') || '';
    document.getElementById('eventThumbnailUrl').value = selected.getAttribute('data-thumbnail') || '';
}
</script>
</body>
</html>