<?php
session_start();
include '../config.php';

// Check if user is logged in
if (!isset($_SESSION['uid'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['uid'];

// Fetch tickets with event details
$sql = "SELECT er.id AS reg_id, er.role, er.registered_at, er.ticket_number,
               e.event_name, e.location, e.status, e.image_url, e.created_at
        FROM event_registrations er
        JOIN events e ON er.event_id = e.id
        WHERE er.user_id = ?
        ORDER BY e.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Tickets - Eventra</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
  <style>
    body { background: #f4f7fc; font-family: 'Poppins', sans-serif; }
    .ticket-card {
      border: none; border-radius: 1rem; overflow: hidden;
      box-shadow: 0 10px 25px rgba(0,0,0,0.08);
      transition: transform .3s ease, box-shadow .3s ease;
    }
    .ticket-card:hover { transform: translateY(-5px); box-shadow: 0 18px 35px rgba(0,0,0,0.12); }
    .ticket-img { width: 100%; height: 200px; object-fit: cover; }
    .ticket-info { padding: 1.5rem; }
    .ticket-title { font-size: 1.25rem; font-weight: 600; color: #1a253c; }
    .ticket-meta { font-size: 0.9rem; color: #555; margin-bottom: 0.5rem; }
    .ticket-number {
      display: inline-block; padding: 0.4rem 0.8rem;
      background: linear-gradient(90deg, #4f46e5, #7c3aed);
      color: #fff; border-radius: 0.5rem; font-weight: 500;
      font-size: 0.95rem;
    }
  </style>
</head>
<body>

<div class="container py-5">
  <h1 class="text-center mb-5 fw-bold">🎟️ My Tickets</h1>

  <div class="row g-4">
    <?php if ($result && $result->num_rows > 0): ?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <div class="col-md-6 col-lg-4">
          <div class="card ticket-card">
            <img src="<?= htmlspecialchars($row['image_url']) ?>" alt="Event Thumbnail" class="ticket-img">
            <div class="ticket-info">
              <h5 class="ticket-title"><?= htmlspecialchars($row['event_name']) ?></h5>
              <p class="ticket-meta"><i class="fa-regular fa-map"></i> <?= htmlspecialchars($row['location']) ?></p>
              <p class="ticket-meta"><i class="fa-regular fa-calendar"></i> <?= date("F j, Y", strtotime($row['created_at'])) ?></p>
              <p class="ticket-meta"><i class="fa-solid fa-user-tag"></i> Role: <strong><?= ucfirst($row['role']) ?></strong></p>
              <p class="ticket-meta"><i class="fa-regular fa-clock"></i> Registered: <?= date("F j, Y g:i A", strtotime($row['registered_at'])) ?></p>
              <?php if (!empty($row['ticket_number'])): ?>
                <div class="ticket-number">Ticket #<?= htmlspecialchars($row['ticket_number']) ?></div>
              <?php else: ?>
                <p class="text-muted mt-2">No ticket generated (Participants don’t require tickets)</p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="col-12 text-center py-5">
        <h4 class="text-muted">No Tickets Found</h4>
        <p class="text-secondary">You haven’t registered for any events yet.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
