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

// Function to generate a simple, color-coded avatar from a name
function generateAvatar($name) {
    $words = explode(" ", $name);
    $initials = "";
    $initials .= isset($words[0][0]) ? strtoupper($words[0][0]) : '';
    $initials .= count($words) > 1 ? strtoupper(end($words)[0]) : '';
    if (empty($initials)) $initials = 'U';

    $colors = ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#3b82f6', '#8b5cf6'];
    $colorIndex = crc32($name) % count($colors);
    $bgColor = $colors[$colorIndex];
    
    return "<div class='avatar' style='background-color: {$bgColor};'>{$initials}</div>";
}

// Handle delete request
if (isset($_GET['delete'])) {
    $fid = intval($_GET['delete']);
    $sql = "DELETE FROM event_feedback WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $fid);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $success = "🗑️ Feedback deleted successfully.";
    } else {
        $error = "❌ Error deleting feedback or feedback not found.";
    }
    $stmt->close();
}

// Fetch feedback with user & event details
$sql = "SELECT f.id, f.uid, u.name AS user_name, e.event_name, f.issue, f.description, f.submitted_at
        FROM event_feedback f
        JOIN users u ON f.uid = u.uid
        JOIN events e ON f.event_id = e.id
        ORDER BY f.submitted_at DESC";
$result = $conn->query($sql);

$feedbacks = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $feedbacks[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Feedback - Eventra</title>
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
        .avatar {
            width: 40px; height: 40px;
            border-radius: 50%; color: #fff;
            display: inline-flex; align-items: center; justify-content: center;
            font-weight: 600;
        }
        .accordion-item {
            border: none;
            margin-bottom: 1rem;
            border-radius: 0.75rem !important;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
        }
        .accordion-button {
            border-radius: 0.75rem !important;
            background-color: #fff;
            color: var(--sidebar-bg);
            font-weight: 600;
        }
        .accordion-button:not(.collapsed) {
            background-color: #f8f9fa;
            box-shadow: none;
        }
        .accordion-button:focus { box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.2); }
        .accordion-button::after {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%231a253c'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header"><h2><i class="fa-solid fa-shield-halved"></i> Eventra</h2></div>
        <nav class="sidebar-nav">
            <a href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="manage_users.php"><i class="fa-solid fa-users"></i> Manage Users</a>
            <a href="manage_events.php"><i class="fa-solid fa-calendar-days"></i> Manage Events</a>
            <a href="manage_gallery.php"><i class="fa-solid fa-image"></i> Event Gallery</a>
            <a href="manage_feedback.php" class="active"><i class="fa-solid fa-comments"></i> Feedback</a>
        </nav>
        <div class="sidebar-footer"><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></div>
    </div>

    <main class="main-content">
        <h2 class="h3 mb-4 fw-bold">💬 Manage Feedback</h2>
        
        <div id="alert-container">
            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show"><?= $success ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show"><?= $error ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title fw-bold mb-0">📋 Feedback Inbox</h5>
                    <div class="input-group" style="max-width: 300px;">
                        <span class="input-group-text"><i class="fa-solid fa-search"></i></span>
                        <input type="text" id="feedback-search" class="form-control" placeholder="Search user, event, or issue...">
                    </div>
                </div>

                <?php if (!empty($feedbacks)): ?>
                    <div class="accordion" id="feedbackAccordion">
                        <?php foreach ($feedbacks as $index => $fb): 
                            $searchable_text = strtolower(htmlspecialchars($fb['user_name'] . ' ' . $fb['event_name'] . ' ' . $fb['issue']));
                        ?>
                            <div class="accordion-item" data-search-text="<?= $searchable_text ?>">
                                <h2 class="accordion-header" id="heading<?= $index ?>">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>">
                                        <div class="d-flex justify-content-between w-100 align-items-center pe-2">
                                            <div class="d-flex align-items-center">
                                                <?= generateAvatar($fb['user_name']) ?>
                                                <div class="ms-3">
                                                    <div class="fw-bold"><?= htmlspecialchars($fb['user_name']) ?></div>
                                                    <small class="text-muted"><?= htmlspecialchars($fb['event_name']) ?></small>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge text-bg-warning"><?= htmlspecialchars($fb['issue']) ?></span>
                                                <div class="small text-muted mt-1"><?= date("M j, Y", strtotime($fb['submitted_at'])) ?></div>
                                            </div>
                                        </div>
                                    </button>
                                </h2>
                                <div id="collapse<?= $index ?>" class="accordion-collapse collapse" data-bs-parent="#feedbackAccordion">
                                    <div class="accordion-body">
                                        <p><?= nl2br(htmlspecialchars($fb['description'])) ?></p>
                                        <hr>
                                        <div class="text-end">
                                            <a href="?delete=<?= $fb['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this feedback?');">
                                                <i class="fa-solid fa-trash-can me-1"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fa-regular fa-folder-open fa-4x text-muted mb-3"></i>
                        <h4 class="text-secondary">Inbox is Empty</h4>
                        <p class="text-muted">No feedback has been submitted yet.</p>
                    </div>
                <?php endif; ?>
                 <div class="col-12 text-center py-4" id="no-results" style="display: none;"><p class="text-muted">No feedback found matching your search.</p></div>
            </div>
        </div>
    </main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Auto-dismiss alerts
    const alert = document.querySelector('#alert-container .alert');
    if (alert) {
        setTimeout(() => new bootstrap.Alert(alert).close(), 5000);
    }

    // 2. Live Search for Accordion
    const searchInput = document.getElementById('feedback-search');
    const allItems = document.querySelectorAll('#feedbackAccordion .accordion-item');
    const noResultsMsg = document.getElementById('no-results');

    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        let visibleCount = 0;
        
        allItems.forEach(item => {
            const textToSearch = item.dataset.searchText || '';
            if (textToSearch.includes(searchTerm)) {
                item.style.display = 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        if (allItems.length > 0) {
            noResultsMsg.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    });
});
</script>
</body>
</html>