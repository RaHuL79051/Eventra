<?php
include '../config.php';
session_start();

// Ensure user is logged in
if (!isset($_SESSION['uid'])) {
    die("Unauthorized access. Please login first.");
}
$uid = $_SESSION['uid'];

// Fetch user feedback with event details
$sql = "SELECT ef.id, ef.issue, ef.description, ef.submitted_at, e.event_name
        FROM event_feedback ef
        JOIN events e ON ef.event_id = e.id
        WHERE ef.uid = ?
        ORDER BY ef.submitted_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $uid);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Feedback History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .back-to-dashboard {
            position: absolute;
            top: 2rem;
            left: 2rem;
            z-index: 10;
        }
        .history-container {
            max-width: 900px;
            margin: 5rem auto;
            border-radius: 1.5rem;
            padding: 2.5rem;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        .history-header {
            text-align: center;
            margin-bottom: 3rem;
            font-weight: 700;
            font-size: 2.5rem;
            background: linear-gradient(90deg, #4f46e5, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
        }
        .timeline {
            position: relative;
            padding-left: 2rem;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 10px;
            bottom: 10px;
            width: 3px;
            background-color: #e9ecef;
            border-radius: 2px;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.6s ease-out, transform 0.6s ease-out;
        }
        .timeline-item.is-visible {
            opacity: 1;
            transform: translateY(0);
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -2rem;
            top: 12px;
            transform: translateX(-50%);
            width: 15px;
            height: 15px;
            background-color: #fff;
            border: 3px solid #4f46e5;
            border-radius: 50%;
            z-index: 1;
        }
        .feedback-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background: #fff;
        }
        .feedback-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.12);
        }
        .card-header {
            background-color: transparent;
            border-bottom: 1px solid #f0f0f0;
        }
        .event-title {
            font-weight: 600;
            font-size: 1.2rem;
            color: #1a253c;
        }
        .issue-badge {
            background: linear-gradient(90deg, #f59e0b, #facc15);
            color: white;
            font-size: 0.8rem;
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-weight: 500;
        }
        .description-text {
            color: #555;
            padding: 1rem 0;
        }
        .submitted-date {
            font-size: 0.85rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <a href="dashboard.php" class="btn btn-outline-primary back-to-dashboard">
        <i class="fa-solid fa-arrow-left me-2"></i>Back to Dashboard
    </a>
    <div class="container history-container">
        <h2 class="history-header">My Feedback History</h2>

        <?php if ($result->num_rows > 0): ?>
            <div class="timeline">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="timeline-item">
                        <div class="card feedback-card">
                            <div class="card-header d-flex justify-content-between align-items-center p-3">
                                <span class="event-title">
                                    <i class="fa-solid fa-calendar-check me-2 text-primary"></i>
                                    <?= htmlspecialchars($row['event_name']) ?>
                                </span>
                                <span class="issue-badge"><?= htmlspecialchars($row['issue']) ?></span>
                            </div>
                            <div class="card-body p-3">
                                <p class="description-text mb-2"><?= nl2br(htmlspecialchars($row['description'])) ?></p>
                            </div>
                            <div class="card-footer bg-transparent border-0 text-end p-2">
                                <small class="submitted-date">
                                    Submitted on <?= date("F j, Y, g:i A", strtotime($row['submitted_at'])) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fa-regular fa-comments fa-4x text-muted mb-3"></i>
                <h4 class="text-secondary">No Feedback Yet</h4>
                <p class="text-muted">You haven't submitted feedback for any events.</p>
                <a href="feedback_form.php" class="btn btn-primary mt-3">
                    <i class="fa-solid fa-plus me-2"></i>Submit Your First Feedback
                </a>
            </div>
        <?php endif; ?>
    </div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const items = document.querySelectorAll('.timeline-item');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1
    });

    items.forEach(item => {
        observer.observe(item);
    });
});
</script>
</body>
</html>