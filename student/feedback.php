<?php
include '../config.php';
session_start();

// Ensure user is logged in
if (!isset($_SESSION['uid'])) { 
    header('Location: login.php');
    exit();
}

$uid = $_SESSION['uid'];
$redirect = false; 
$success = "";
$error = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = intval($_POST['event_id']);
    $issue = trim($_POST['issue']);
    $description = trim($_POST['description']);

    if (!empty($event_id) && !empty($issue) && !empty($description)) {
        $sql = "INSERT INTO event_feedback (uid, event_id, issue, description) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiss", $uid, $event_id, $issue, $description);

        if ($stmt->execute()) {
            $success = "✅ Feedback submitted successfully!";
            $redirect = true;
        } else {
            $error = "❌ Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "⚠️ Please fill in all fields.";
    }
}

// Fetch completed events
$events = [];
$sql = "SELECT id, event_name FROM events WHERE status='completed'";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event Feedback</title>
    <?php if ($redirect): ?>
        <meta http-equiv="refresh" content="3;url=dashboard.php">
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #ece9e6, #ffffff);
            min-height: 100vh;
        }
        .back-to-dashboard {
            position: absolute;
            top: 2rem;
            left: 2rem;
            z-index: 10;
        }
        .feedback-card {
            max-width: 700px;
            margin: 5rem auto;
            border-radius: 1rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            padding: 2.5rem;
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3);
            animation: fadeInDown 0.6s ease-out;
        }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .feedback-header {
            font-weight: 700;
            text-align: center;
            font-size: 2.2rem;
            margin-bottom: 2rem;
            background: linear-gradient(90deg, #4f46e5, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .form-control, .form-select {
            border-radius: 0.5rem;
            border: 1px solid #ddd;
            padding: 0.75rem 1rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
        }
        .btn-submit {
            background: linear-gradient(90deg, #4f46e5, #7c3aed);
            border: none;
            border-radius: 0.5rem;
            padding: 0.8rem;
            font-weight: 600;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(79, 70, 229, 0.2);
        }
        .btn-history {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 0.5rem;
            padding: 0.8rem;
            font-weight: 500;
            width: 100%;
            margin-top: 1rem;
            transition: background 0.3s ease;
        }
        .btn-history:hover {
            background: #f1f3f5;
        }
    </style>
</head>
<body>
    <a href="dashboard.php" class="btn btn-outline-primary back-to-dashboard">
        <i class="fa-solid fa-arrow-left me-2"></i>Back to Dashboard
    </a>

    <div class="container">
        <div class="feedback-card">
            <h2 class="feedback-header">Event Feedback Form</h2>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?= $success ?>
                    <p class="mb-0 small">Redirecting to dashboard in 3 seconds...</p>
                </div>
            <?php elseif (!empty($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label text-muted">Your User ID</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($uid) ?>" readonly>
                </div>

                <div class="mb-3">
                    <label for="event_id" class="form-label fw-medium">Select Completed Event</label>
                    <select name="event_id" id="event_id" class="form-select" required>
                        <option value="" disabled selected>-- Select an Event --</option>
                        <?php foreach ($events as $event): ?>
                            <option value="<?= $event['id'] ?>"><?= htmlspecialchars($event['event_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="issue" class="form-label fw-medium">Subject / Issue</label>
                    <input type="text" name="issue" id="issue" class="form-control" placeholder="e.g., Audio Quality, Food, etc." required>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label fw-medium">Detailed Feedback</label>
                    <textarea name="description" id="description" rows="5" class="form-control" placeholder="Please provide details about your experience..." required></textarea>
                </div>

                <button type="submit" class="btn btn-primary w-100 btn-submit">Submit Feedback</button>
            </form>

            <!-- New button for history -->
            <a href="feedback_history.php" class="btn btn-history mt-3">
                <i class="fa-solid fa-clock-rotate-left me-2"></i>View My Feedback History
            </a>
        </div>
    </div>
</body>
</html>
