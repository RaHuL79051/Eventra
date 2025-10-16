<?php
include '../config.php';
session_start();

// Ensure admin is logged in
if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$success = "";
$error = "";

// --- (All your PHP logic for Add, Update, and Delete remains exactly the same) ---

// --- Handle Add Event ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event'])) {
    $event_name = trim($_POST['event_name']);
    $location = trim($_POST['location']);
    $description = trim($_POST['description']);
    $status = $_POST['status'];
    $event_date = $_POST['event_date'];

    if (!empty($event_name) && !empty($location) && !empty($event_date)) {
        $imagePath = "../uploads/events/default.jpg";

        if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] == 0) {
            $uploadDir = "../uploads/events/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $ext = pathinfo($_FILES['event_image']['name'], PATHINFO_EXTENSION);
            $safeName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $event_name));
            $fileName = $safeName . "_" . time() . "." . $ext;
            $targetFile = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['event_image']['tmp_name'], $targetFile)) {
                $imagePath = $targetFile;
            } else {
                $error = "❌ Failed to upload image.";
            }
        }

        if (empty($error)) {
            $sql = "INSERT INTO events (event_name, location, description, status, image_url, event_date) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $event_name, $location, $description, $status, $imagePath, $event_date);
            if ($stmt->execute()) {
                $success = "✅ Event '{$event_name}' added successfully!";
            } else {
                $error = "❌ Database Error: " . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        $error = "⚠️ Please fill all required fields.";
    }
}

// --- Handle Update Event ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_event'])) {
    $event_id = intval($_POST['event_id']);
    $event_name = trim($_POST['event_name']);
    $location = trim($_POST['location']);
    $description = trim($_POST['description']);
    $status = $_POST['status'];
    $event_date = $_POST['event_date'];
    $current_image_path = $_POST['current_image_path'];
    $imagePath = $current_image_path;

    if (isset($_FILES['edit_event_image']) && $_FILES['edit_event_image']['error'] == 0) {
        $uploadDir = "../uploads/events/";
        $ext = pathinfo($_FILES['edit_event_image']['name'], PATHINFO_EXTENSION);
        $safeName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $event_name));
        $fileName = $safeName . "_" . time() . "." . $ext;
        $targetFile = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['edit_event_image']['tmp_name'], $targetFile)) {
            $imagePath = $targetFile;
            if ($current_image_path !== "../uploads/events/default.jpg") {
                @unlink($current_image_path);
            }
        } else {
            $error = "❌ Failed to upload new image.";
        }
    }

    if (empty($error)) {
        $sql = "UPDATE events SET event_name=?, location=?, description=?, status=?, image_url=?, event_date=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssi", $event_name, $location, $description, $status, $imagePath, $event_date, $event_id);
        if ($stmt->execute()) {
            $success = "🔄 Event '{$event_name}' updated successfully!";
        } else {
            $error = "❌ Database Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// --- Handle Delete ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("SELECT image_url FROM events WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $image_to_delete = $row['image_url'];
        if ($image_to_delete !== "../uploads/events/default.jpg") {
            @unlink($image_to_delete);
        }
    }
    $stmt->close();

    $sql = "DELETE FROM events WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $success = "🗑️ Event deleted successfully.";
    } else {
        $error = "❌ Error deleting event.";
    }
    $stmt->close();
}

// --- Fetch All Events for display ---
$events = [];
$result = $conn->query("SELECT * FROM events ORDER BY event_date DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
}

// --- Fetch Event Counts by Status ---
$statusCounts = ['Upcoming' => 0, 'Active' => 0, 'Completed' => 0];
$countResult = $conn->query("SELECT status, COUNT(*) as count FROM events GROUP BY status");
if ($countResult) {
    while ($row = $countResult->fetch_assoc()) {
        if (isset($statusCounts[$row['status']])) {
            $statusCounts[$row['status']] = $row['count'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Events - Eventra</title>
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
        .stat-card-sm { background: #fff; padding: 1rem; border-radius: 0.75rem; }
        .event-card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .event-card:hover { transform: translateY(-5px); box-shadow: 0 12px 25px rgba(0,0,0,0.1); }
        .event-card-img { height: 200px; object-fit: cover; }
        .status-ribbon {
            position: absolute; top: 10px; right: -8px;
            padding: 0.3rem 1rem; font-size: 0.8rem; font-weight: 600;
            color: white; box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            border-radius: 0.25rem 0 0 0.25rem;
        }
        .image-preview { width: 100px; height: 60px; border-radius: 0.5rem; border: 2px dashed #ddd; object-fit: cover; }
        .table-list-img { width: 80px; height: 50px; object-fit: cover; border-radius: 0.5rem; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header"><h2><i class="fa-solid fa-shield-halved"></i> Eventra</h2></div>
        <nav class="sidebar-nav">
            <a href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="manage_users.php"><i class="fa-solid fa-users"></i> Manage Users</a>
            <a href="manage_events.php" class="active"><i class="fa-solid fa-calendar-days"></i> Manage Events</a>
            <a href="manage_gallery.php"><i class="fa-solid fa-image"></i> Event Gallery</a>
            <a href="manage_feedback.php"><i class="fa-solid fa-comments"></i> Feedback</a>
        </nav>
        <div class="sidebar-footer"><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></div>
    </div>

    <main class="main-content">
        <h2 class="h3 mb-4 fw-bold">📅 Event Management</h2>

        <div id="alert-container">
        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show"><?= $success ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php elseif (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show"><?= $error ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        </div>

        <div class="row g-3 mb-4">
            <div class="col"><div class="stat-card-sm"><h6 class="text-muted mb-1">Upcoming</h6><h4 class="fw-bold mb-0"><?= $statusCounts['Upcoming'] ?></h4></div></div>
            <div class="col"><div class="stat-card-sm"><h6 class="text-muted mb-1">Active</h6><h4 class="fw-bold mb-0"><?= $statusCounts['Active'] ?></h4></div></div>
            <div class="col"><div class="stat-card-sm"><h6 class="text-muted mb-1">Completed</h6><h4 class="fw-bold mb-0"><?= $statusCounts['Completed'] ?></h4></div></div>
        </div>

        <div class="accordion mb-4" id="addEventAccordion">
            <div class="accordion-item border-0 shadow-sm" style="border-radius: 1rem;"><h2 class="accordion-header"><button class="accordion-button collapsed fw-medium" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne"><i class="fa-solid fa-plus me-2"></i> Create a New Event</button></h2><div id="collapseOne" class="accordion-collapse collapse" data-bs-parent="#addEventAccordion"><div class="accordion-body"><form method="POST" enctype="multipart/form-data"><div class="row g-3">
                <div class="col-md-6"><input type="text" name="event_name" class="form-control" placeholder="Event Name" required></div><div class="col-md-6"><input type="text" name="location" class="form-control" placeholder="Location" required></div>
                <div class="col-md-6"><input type="date" name="event_date" class="form-control" required></div><div class="col-md-6"><select name="status" class="form-select"><option value="Upcoming">Upcoming</option><option value="Active">Active</option><option value="Completed">Completed</option></select></div>
                <div class="col-12"><textarea name="description" class="form-control" rows="3" placeholder="Event Description..."></textarea></div>
                <div class="col-md-6 d-flex align-items-center"><input type="file" name="event_image" class="form-control" onchange="previewImage(event, 'addImagePreview')"><img id="addImagePreview" src="../uploads/events/default.jpg" class="ms-3 image-preview"></div>
                <div class="col-md-6"><button type="submit" name="add_event" class="btn btn-primary w-100">Add New Event</button></div>
            </div></form></div></div></div>
        </div>

        <div class="card mb-4"><div class="card-body d-flex flex-wrap justify-content-between align-items-center">
            <div class="btn-group" id="view-toggle"><button type="button" class="btn btn-outline-primary active" data-view="grid"><i class="fa-solid fa-th"></i> Grid</button><button type="button" class="btn btn-outline-primary" data-view="list"><i class="fa-solid fa-list"></i> List</button></div>
            <div class="d-flex flex-wrap align-items-center">
                <input type="text" id="event-search" class="form-control me-2" placeholder="Search events..." style="max-width: 250px;">
                <div class="btn-group" id="status-filter"><button type="button" class="btn btn-outline-secondary active" data-status="all">All</button><button type="button" class="btn btn-outline-secondary" data-status="upcoming">Upcoming</button><button type="button" class="btn btn-outline-secondary" data-status="active">Active</button><button type="button" class="btn btn-outline-secondary" data-status="completed">Completed</button></div>
            </div>
        </div></div>

        <div id="event-grid" class="row g-4">
            <?php if (!empty($events)): foreach ($events as $event): ?>
                <div class="col-lg-4 col-md-6 event-item" data-name="<?= strtolower(htmlspecialchars($event['event_name'])) ?>" data-status="<?= strtolower($event['status']) ?>">
                    <div class="card h-100 event-card"><div class="position-relative"><img src="<?= htmlspecialchars($event['image_url']) ?>" class="card-img-top event-card-img" alt="<?= htmlspecialchars($event['event_name']) ?>"><div class="status-ribbon <?php switch ($event['status']) { case 'Completed': echo 'bg-success'; break; case 'Active': echo 'bg-primary'; break; default: echo 'bg-warning text-dark'; } ?>"><?= htmlspecialchars($event['status']) ?></div></div><div class="card-body d-flex flex-column"><h5 class="card-title fw-bold"><?= htmlspecialchars($event['event_name']) ?></h5><p class="text-muted small mb-2"><i class="fa-solid fa-location-dot me-2"></i><?= htmlspecialchars($event['location']) ?></p><p class="text-muted small mb-3"><i class="fa-solid fa-calendar me-2"></i><?= date("F j, Y", strtotime($event['event_date'])) ?></p><p class="card-text small flex-grow-1"><?= substr(htmlspecialchars($event['description']), 0, 100) . (strlen($event['description']) > 100 ? '...' : '') ?></p></div><div class="card-footer bg-white border-0 text-end"><button class="btn btn-sm btn-outline-primary edit-btn" data-bs-toggle="modal" data-bs-target="#editEventModal" data-id="<?= $event['id'] ?>" data-name="<?= htmlspecialchars($event['event_name']) ?>" data-location="<?= htmlspecialchars($event['location']) ?>" data-date="<?= $event['event_date'] ?>" data-status="<?= $event['status'] ?>" data-description="<?= htmlspecialchars($event['description']) ?>" data-image_url="<?= htmlspecialchars($event['image_url']) ?>"><i class="fa-solid fa-pencil"></i> Edit</button><a href="?delete=<?= $event['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this event?')"><i class="fa-solid fa-trash-can"></i> Delete</a></div></div>
                </div>
            <?php endforeach; endif; ?>
        </div>
        
        <div id="event-list" class="card d-none"><div class="card-body"><div class="table-responsive"><table class="table table-hover align-middle">
            <thead><tr><th>Image</th><th>Event Name</th><th>Location</th><th>Date</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            <?php if (!empty($events)): foreach ($events as $event): ?>
                <tr class="event-item" data-name="<?= strtolower(htmlspecialchars($event['event_name'])) ?>" data-status="<?= strtolower($event['status']) ?>">
                    <td><img src="<?= htmlspecialchars($event['image_url']) ?>" class="table-list-img"></td><td class="fw-medium"><?= htmlspecialchars($event['event_name']) ?></td><td><?= htmlspecialchars($event['location']) ?></td><td><?= date("M j, Y", strtotime($event['event_date'])) ?></td>
                    <td><span class="badge rounded-pill <?php switch ($event['status']) { case 'Completed': echo 'text-bg-success'; break; case 'Active': echo 'text-bg-primary'; break; default: echo 'text-bg-warning'; } ?>"><?= htmlspecialchars($event['status']) ?></span></td>
                    <td class="text-end"><button class="btn btn-sm btn-outline-primary edit-btn" data-bs-toggle="modal" data-bs-target="#editEventModal" data-id="<?= $event['id'] ?>" data-name="<?= htmlspecialchars($event['event_name']) ?>" data-location="<?= htmlspecialchars($event['location']) ?>" data-date="<?= $event['event_date'] ?>" data-status="<?= $event['status'] ?>" data-description="<?= htmlspecialchars($event['description']) ?>" data-image_url="<?= htmlspecialchars($event['image_url']) ?>"><i class="fa-solid fa-pencil"></i></button><a href="?delete=<?= $event['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')"><i class="fa-solid fa-trash-can"></i></a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table></div></div></div>

        <div class="col-12" id="no-results" style="display: none;"><div class="alert alert-warning text-center">No events match your search or filter.</div></div>
        <?php if (empty($events)): ?><div class="col-12"><div class="alert alert-info text-center">No events found. Start by adding one above!</div></div><?php endif; ?>
    </main>

    <div class="modal fade" id="editEventModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><form method="POST" enctype="multipart/form-data">
        <div class="modal-header"><h5 class="modal-title fw-bold">Edit Event</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><input type="hidden" name="event_id" id="edit_event_id"><input type="hidden" name="current_image_path" id="edit_current_image_path"><div class="row g-3">
            <div class="col-md-6"><label class="form-label">Event Name</label><input type="text" name="event_name" id="edit_event_name" class="form-control" required></div><div class="col-md-6"><label class="form-label">Location</label><input type="text" name="location" id="edit_location" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">Event Date</label><input type="date" name="event_date" id="edit_event_date" class="form-control" required></div><div class="col-md-6"><label class="form-label">Status</label><select name="status" id="edit_status" class="form-select"><option value="Upcoming">Upcoming</option><option value="Active">Active</option><option value="Completed">Completed</option></select></div>
            <div class="col-12"><label class="form-label">Description</label><textarea name="description" id="edit_description" class="form-control" rows="3"></textarea></div>
            <div class="col-12 d-flex align-items-center"><div class="flex-grow-1"><label class="form-label">New Image (optional)</label><input type="file" name="edit_event_image" class="form-control" onchange="previewImage(event, 'editImagePreview')"></div><img id="editImagePreview" src="" class="ms-3 image-preview"></div>
        </div></div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" name="update_event" class="btn btn-primary">Save Changes</button></div>
    </form></div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const alert = document.querySelector('#alert-container .alert');
    if (alert) { setTimeout(() => new bootstrap.Alert(alert).close(), 5000); }

    const editModal = document.getElementById('editEventModal');
    editModal.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget;
        document.getElementById('edit_event_id').value = button.dataset.id;
        document.getElementById('edit_event_name').value = button.dataset.name;
        document.getElementById('edit_location').value = button.dataset.location;
        document.getElementById('edit_event_date').value = button.dataset.date;
        document.getElementById('edit_status').value = button.dataset.status;
        document.getElementById('edit_description').value = button.dataset.description;
        document.getElementById('editImagePreview').src = button.dataset.image_url;
        document.getElementById('edit_current_image_path').value = button.dataset.image_url;
    });

    const searchInput = document.getElementById('event-search');
    const filterButtons = document.querySelectorAll('#status-filter button');
    const viewToggleButtons = document.querySelectorAll('#view-toggle button');
    const gridView = document.getElementById('event-grid');
    const listView = document.getElementById('event-list');
    const noResultsMsg = document.getElementById('no-results');
    let currentStatusFilter = 'all';

    function filterAndSearch() {
        const searchTerm = searchInput.value.toLowerCase();
        let visibleCount = 0;
        const allItems = document.querySelectorAll('.event-item');

        allItems.forEach(item => {
            const name = item.dataset.name || '';
            const status = item.dataset.status || '';
            const matchesSearch = name.includes(searchTerm);
            const matchesStatus = currentStatusFilter === 'all' || status === currentStatusFilter;
            
            if (matchesSearch && matchesStatus) {
                item.style.display = '';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });
        noResultsMsg.style.display = visibleCount === 0 ? '' : 'none';
    }

    searchInput.addEventListener('input', filterAndSearch);
    
    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            currentStatusFilter = button.dataset.status;
            filterAndSearch();
        });
    });

    viewToggleButtons.forEach(button => {
        button.addEventListener('click', () => {
            viewToggleButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            const view = button.dataset.view;
            if (view === 'grid') {
                gridView.classList.remove('d-none');
                listView.classList.add('d-none');
            } else {
                gridView.classList.add('d-none');
                listView.classList.remove('d-none');
            }
        });
    });
});

function previewImage(event, previewId) {
    if (event.target.files && event.target.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) { document.getElementById(previewId).src = e.target.result; };
        reader.readAsDataURL(event.target.files[0]);
    }
}
</script>
</body>
</html>