<?php
include '../config.php';
session_start();

// Ensure admin is logged in
if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$admin_name = $_SESSION['name'];
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

// Add new user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $name = trim($_POST['name']);
    $uid = trim($_POST['uid']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (!empty($name) && !empty($uid) && !empty($email) && !empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $sql = "INSERT INTO users (uid, name, email, password, role) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $uid, $name, $email, $hashed_password, $role);

        if ($stmt->execute()) {
            $success = "✅ User '{$name}' added successfully!";
        } else {
            $error = "❌ Error: User with this UID or Email may already exist.";
        }
        $stmt->close();
    } else {
        $error = "⚠️ Please fill all fields.";
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    if ($delete_id === $_SESSION['uid']) {
        $error = "❌ You cannot delete your own account.";
    } else {
        $sql = "DELETE FROM users WHERE uid = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $delete_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $success = "🗑️ User deleted successfully.";
        } else {
            $error = "❌ Error deleting user.";
        }
        $stmt->close();
    }
}

// Handle role change
if (isset($_GET['role']) && isset($_GET['id'])) {
    $newRole = $_GET['role'];
    $id = $_GET['id'];
    if ($id === $_SESSION['uid']) {
        $error = "❌ You cannot change your own role.";
    } else {
        $sql = "UPDATE users SET role=? WHERE uid=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $newRole, $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $success = "🔄 Role updated successfully.";
        } else {
            $error = "❌ Error: Role not updated.";
        }
        $stmt->close();
    }
}

// Fetch all users
$users = [];
$result = $conn->query("SELECT uid, name, email, role, created_at FROM users ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - Eventra</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <style>
        :root { --sidebar-bg: #1a253c; --primary-color: #4f46e5; }
        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f6fb;
        }
        .sidebar {
            height: 100vh; background: var(--sidebar-bg); color: #fff;
            position: fixed; left: 0; top: 0; width: 250px;
            display: flex; flex-direction: column;
        }
        .sidebar-header { padding: 1.5rem; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header h2 { font-weight: 700; margin: 0; font-size: 1.5rem; }
        .sidebar-nav { flex-grow: 1; padding: 1rem; }
        .sidebar-nav a {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.8rem 1rem; margin: 0.3rem 0; color: #e0e0e0;
            text-decoration: none; border-radius: 0.5rem; transition: all 0.3s;
        }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: var(--primary-color); color: #fff; }
        .sidebar-footer { padding: 1rem; border-top: 1px solid rgba(255,255,255,0.1); }
        .main-content { margin-left: 250px; padding: 2rem; }
        .card { border: none; border-radius: 1rem; box-shadow: 0 6px 20px rgba(0,0,0,0.07); }
        .table thead th {
            background-color: #f8f9fa;
            border-bottom-width: 1px;
            font-weight: 600;
            color: #555;
        }
        .table-hover tbody tr:hover { background-color: #f8f9fa; }
        .avatar {
            width: 40px; height: 40px;
            border-radius: 50%; color: #fff;
            display: inline-flex; align-items: center; justify-content: center;
            font-weight: 600; margin-right: 1rem;
        }
        .action-icon {
            color: #6c757d;
            text-decoration: none;
            margin: 0 0.3rem;
            transition: color 0.2s;
        }
        .action-icon.text-danger:hover { color: #dc3545 !important; }
        .action-icon.text-warning:hover { color: #ffc107 !important; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header"><h2><i class="fa-solid fa-shield-halved"></i> Eventra</h2></div>
        <nav class="sidebar-nav">
            <a href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="manage_users.php" class="active"><i class="fa-solid fa-users"></i> Manage Users</a>
            <a href="manage_events.php"><i class="fa-solid fa-calendar-days"></i> Manage Events</a>
            <a href="manage_gallery.php"><i class="fa-solid fa-image"></i> Event Gallery</a>
            <a href="manage_feedback.php"><i class="fa-solid fa-comments"></i> Feedback</a>
        </nav>
        <div class="sidebar-footer"><a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></div>
    </div>

    <main class="main-content">
        <h2 class="h3 mb-4 fw-bold">👥 User Management</h2>

        <div id="alert-container">
            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show"><?= $success ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show"><?= $error ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
        </div>

        <div class="accordion mb-4" id="addUserAccordion">
            <div class="accordion-item border-0 shadow-sm" style="border-radius: 1rem;">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed fw-medium" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                        <i class="fa-solid fa-plus me-2"></i> Add a New User
                    </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse" data-bs-parent="#addUserAccordion">
                    <div class="accordion-body">
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="row g-3">
                                <div class="col-md-3"><input type="text" name="name" class="form-control" placeholder="Full Name" required></div>
                                <div class="col-md-2"><input type="text" name="uid" class="form-control" placeholder="UID" required></div>
                                <div class="col-md-3"><input type="email" name="email" class="form-control" placeholder="Email Address" required></div>
                                <div class="col-md-2"><input type="password" name="password" class="form-control" placeholder="Password" required></div>
                                <div class="col-md-1"><select name="role" class="form-select"><option value="student">Student</option><option value="admin">Admin</option></select></div>
                                <div class="col-md-1"><button type="submit" name="add_user" class="btn btn-primary w-100">Add</button></div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                    <h5 class="card-title fw-bold mb-0">📋 User List</h5>
                    <div class="d-flex flex-wrap align-items-center">
                        <div class="input-group me-2" style="max-width: 250px;">
                            <span class="input-group-text"><i class="fa-solid fa-search"></i></span>
                            <input type="text" id="user-search" class="form-control" placeholder="Search name or email...">
                        </div>
                        <div class="btn-group" id="role-filter">
                            <button type="button" class="btn btn-outline-secondary active" data-role="all">All</button>
                            <button type="button" class="btn btn-outline-secondary" data-role="admin">Admins</button>
                            <button type="button" class="btn btn-outline-secondary" data-role="student">Students</button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>User</th><th>Email</th><th>Role</th><th>Created At</th><th class="text-end">Actions</th></tr></thead>
                        <tbody id="user-table-body">
                            <?php if (!empty($users)): ?>
                                <?php foreach ($users as $user): ?>
                                    <tr data-name="<?= strtolower(htmlspecialchars($user['name'])) ?>" data-email="<?= strtolower(htmlspecialchars($user['email'])) ?>" data-role="<?= $user['role'] ?>">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?= generateAvatar($user['name']) ?>
                                                <span class="fw-medium"><?= htmlspecialchars($user['name']) ?></span>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td>
                                            <span class="badge fs-6 rounded-pill <?= $user['role'] === 'admin' ? 'text-bg-danger' : 'text-bg-primary' ?>">
                                                <i class="fa-solid <?= $user['role'] === 'admin' ? 'fa-shield-halved' : 'fa-user-graduate' ?> me-1"></i>
                                                <?= ucfirst($user['role']) ?>
                                            </span>
                                        </td>
                                        <td><?= date("M j, Y", strtotime($user['created_at'])) ?></td>
                                        <td class="text-end">
                                            <a href="?role=<?= $user['role'] === 'admin' ? 'student' : 'admin' ?>&id=<?= $user['uid'] ?>" class="action-icon text-warning" data-bs-toggle="tooltip" title="Change Role"><i class="fa-solid fa-user-pen"></i></a>
                                            <a href="?delete=<?= $user['uid'] ?>" class="action-icon text-danger" data-bs-toggle="tooltip" title="Delete User" onclick="return confirm('Are you sure you want to delete this user?');"><i class="fa-solid fa-trash-can"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr id="no-users-row"><td colspan="5" class="text-center text-muted p-4">No users found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Enable Bootstrap tooltips
    new bootstrap.Tooltip(document.body, { selector: "[data-bs-toggle='tooltip']" });

    // 2. Auto-dismiss alerts
    const alert = document.querySelector('#alert-container .alert');
    if (alert) {
        setTimeout(() => {
            new bootstrap.Alert(alert).close();
        }, 5000); // 5 seconds
    }

    // 3. Client-side form validation
    const form = document.querySelector('.needs-validation');
    form.addEventListener('submit', event => {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    }, false);

    // 4. Live Search and Filtering
    const searchInput = document.getElementById('user-search');
    const filterButtons = document.querySelectorAll('#role-filter button');
    const tableBody = document.getElementById('user-table-body');
    const allRows = tableBody.querySelectorAll('tr');
    let currentRoleFilter = 'all';

    function filterUsers() {
        const searchTerm = searchInput.value.toLowerCase();
        
        allRows.forEach(row => {
            if (row.id === 'no-users-row') return; // Skip the 'no users' row

            const name = row.dataset.name;
            const email = row.dataset.email;
            const role = row.dataset.role;

            const matchesSearch = name.includes(searchTerm) || email.includes(searchTerm);
            const matchesRole = currentRoleFilter === 'all' || role === currentRoleFilter;

            if (matchesSearch && matchesRole) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    searchInput.addEventListener('input', filterUsers);

    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            currentRoleFilter = button.dataset.role;
            filterUsers();
        });
    });
});
</script>
</body>
</html>