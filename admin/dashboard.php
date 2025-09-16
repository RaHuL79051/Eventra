<?php
include '../config.php';
session_start();

// Ensure admin is logged in
if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$name = $_SESSION['name'];

// --- 1. FETCH DYNAMIC STATS ---
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$totalEvents = $conn->query("SELECT COUNT(*) as count FROM events")->fetch_assoc()['count'];
$totalRegistrations = $conn->query("SELECT COUNT(*) as count FROM event_registrations")->fetch_assoc()['count'];
$totalFeedback = $conn->query("SELECT COUNT(*) as count FROM event_feedback")->fetch_assoc()['count'];

// --- 2. FETCH DATA FOR CHART (Events per month) ---
$chartLabels = [];
$chartData = [];
$chartSql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(id) as count 
             FROM events 
             GROUP BY month 
             ORDER BY month ASC 
             LIMIT 6";
$chartResult = $conn->query($chartSql);
while ($row = $chartResult->fetch_assoc()) {
    $chartLabels[] = date("M Y", strtotime($row['month'] . "-01"));
    $chartData[] = $row['count'];
}

// --- 3. FETCH RECENT FEEDBACK ---
$recentFeedback = [];
$feedbackSql = "SELECT ef.issue, e.event_name, ef.submitted_at 
                FROM event_feedback ef
                JOIN events e ON ef.event_id = e.id
                ORDER BY ef.submitted_at DESC
                LIMIT 5";
$feedbackResult = $conn->query($feedbackSql);
while($row = $feedbackResult->fetch_assoc()) {
    $recentFeedback[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Eventra</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <style>
        :root {
            --primary-color: #4f46e5;
            --secondary-color: #7c3aed;
            --bg-color: #f4f6fb;
            --card-bg: #ffffff;
            --text-color: #1a253c;
            --sidebar-bg: #1a253c;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-color);
            color: var(--text-color);
        }
        .sidebar {
            height: 100vh;
            background: var(--sidebar-bg);
            color: #fff;
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            display: flex;
            flex-direction: column;
        }
        .sidebar-header {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-header h2 {
            font-weight: 700;
            margin: 0;
            font-size: 1.5rem;
        }
        .sidebar-nav {
            flex-grow: 1;
            padding: 1rem;
        }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.8rem 1rem;
            margin: 0.3rem 0;
            color: #e0e0e0;
            text-decoration: none;
            border-radius: 0.5rem;
            transition: background 0.3s, color 0.3s;
        }
        .sidebar-nav a:hover, .sidebar-nav a.active {
            background: var(--primary-color);
            color: #fff;
        }
        .sidebar-footer {
            padding: 1rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem;
            background: var(--card-bg);
            border-radius: 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .stat-card {
            background: var(--card-bg);
            border-radius: 1rem;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            box-shadow: 0 6px 20px rgba(0,0,0,0.07);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card .icon {
            font-size: 1.8rem;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }
        .stat-card .icon.bg-primary { background: #4f46e5; }
        .stat-card .icon.bg-success { background: #10b981; }
        .stat-card .icon.bg-warning { background: #f59e0b; }
        .stat-card .icon.bg-danger { background: #ef4444; }

        .chart-container, .recent-activity {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 6px 20px rgba(0,0,0,0.07);
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <h2><i class="fa-solid fa-shield-halved"></i> Eventra</h2>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="active"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="manage_users.php"><i class="fa-solid fa-users"></i> Manage Users</a>
            <a href="manage_events.php"><i class="fa-solid fa-calendar-days"></i> Manage Events</a>
            <a href="manage_gallery.php"><i class="fa-solid fa-image"></i> Event Gallery</a>
            <a href="manage_feedback.php"><i class="fa-solid fa-comments"></i> Feedback</a>
        </nav>
        <div class="sidebar-footer">
            <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </div>

    <main class="main-content">
        <header class="dashboard-header">
            <div>
                <h1 class="h3 mb-0">Admin Dashboard</h1>
                <p class="mb-0 text-muted">Welcome back, <?= htmlspecialchars($name) ?> 👋</p>
            </div>
            <a href="manage_events.php" class="btn btn-primary"><i class="fa-solid fa-plus me-2"></i>Create Event</a>
        </header>

        <div class="row g-4 mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="stat-card">
                    <div class="icon bg-primary"><i class="fa-solid fa-users"></i></div>
                    <div>
                        <h3 class="mb-0 fw-bold"><?= $totalUsers ?></h3>
                        <p class="mb-0 text-muted">Total Users</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="stat-card">
                    <div class="icon bg-success"><i class="fa-solid fa-calendar-check"></i></div>
                    <div>
                        <h3 class="mb-0 fw-bold"><?= $totalEvents ?></h3>
                        <p class="mb-0 text-muted">Total Events</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="stat-card">
                    <div class="icon bg-warning"><i class="fa-solid fa-ticket"></i></div>
                    <div>
                        <h3 class="mb-0 fw-bold"><?= $totalRegistrations ?></h3>
                        <p class="mb-0 text-muted">Registrations</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="stat-card">
                    <div class="icon bg-danger"><i class="fa-solid fa-comments"></i></div>
                    <div>
                        <h3 class="mb-0 fw-bold"><?= $totalFeedback ?></h3>
                        <p class="mb-0 text-muted">Feedback</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="chart-container">
                    <h5 class="mb-3">Event Creation Trends</h5>
                    <div style="position: relative; height: 350px;">
                        <canvas id="eventChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="recent-activity">
                    <h5 class="mb-3">Recent Feedback</h5>
                    <ul class="list-group list-group-flush">
                        <?php if(!empty($recentFeedback)): ?>
                            <?php foreach($recentFeedback as $feedback): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <h6 class="mb-0"><?= htmlspecialchars($feedback['issue']) ?></h6>
                                        <small class="text-muted">for <?= htmlspecialchars($feedback['event_name']) ?></small>
                                    </div>
                                    <small class="text-muted"><?= date('M d', strtotime($feedback['submitted_at'])) ?></small>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item px-0 text-muted">No feedback submitted yet.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // --- Chart.js ---
    const ctx = document.getElementById('eventChart').getContext('2d');
    
    const chartLabels = <?= json_encode($chartLabels) ?>;
    const chartData = <?= json_encode($chartData) ?>;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Events Created',
                data: chartData,
                backgroundColor: 'rgba(79, 70, 229, 0.7)',
                borderColor: 'rgba(79, 70, 229, 1)',
                borderWidth: 1,
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // This is important for the wrapper fix
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
});
</script>
</body>
</html>