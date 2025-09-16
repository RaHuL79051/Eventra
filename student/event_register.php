<?php
include '../config.php';
session_start();

// Redirect to login if user is not logged in
if (!isset($_SESSION['uid'])) {
    header('Location: login.php'); // Or your login page
    exit();
}

if (!isset($_GET['event_id'])) {
    die("Invalid Event!");
}

$event_id = intval($_GET['event_id']);
$user_id = $_SESSION['uid'];

// --- Fetch Event Details ---
$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Event not found!");
}
$event = $result->fetch_assoc();
$stmt->close();


// --- Check if user is already registered ---
$alreadyRegistered = false;
$check_stmt = $conn->prepare("SELECT role, ticket_number FROM event_registrations WHERE event_id = ? AND user_id = ?");
$check_stmt->bind_param("ii", $event_id, $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    $alreadyRegistered = true;
    $registrationDetails = $check_result->fetch_assoc();
    $existingRole = $registrationDetails['role'];
    $existingTicket = $registrationDetails['ticket_number'];
}
$check_stmt->close();


// --- Handle Registration POST request ---
// MODIFIED LOGIC BLOCK
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$alreadyRegistered && strtolower($event['status']) == 'upcoming') {
    $role = $_POST['role'];

    // If the user is a participant, redirect them to the details form
    if ($role === 'participant') {
        header('Location: participant_details.php?event_id=' . $event_id);
        exit();
    }
    
    // If the user is a viewer, register them immediately
    if ($role === 'viewer') {
        // 1. GENERATE A UNIQUE TICKET NUMBER
        $ticket_number = strtoupper('EVT' . $event_id . '-' . bin2hex(random_bytes(5)));

        // 2. INSERT INTO DATABASE
        $insert_stmt = $conn->prepare("INSERT INTO event_registrations (event_id, user_id, role, ticket_number) VALUES (?, ?, ?, ?)");
        $insert_stmt->bind_param("iiss", $event_id, $user_id, $role, $ticket_number);

        if ($insert_stmt->execute()) {
            // 3. SET SESSION VARIABLES FOR SUCCESS MESSAGE
            $_SESSION['registration_success'] = "You have successfully registered for the event!";
            $_SESSION['new_ticket'] = $ticket_number;
            
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        } else {
            $errorMessage = "Error: " . $conn->error;
        }
        $insert_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register for <?= htmlspecialchars($event['event_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gradient-to-br from-indigo-50 via-white to-cyan-50 min-h-screen flex items-center justify-center p-4">

<div class="max-w-4xl w-full mx-auto bg-white rounded-2xl shadow-xl overflow-hidden md:flex">
    
    <div class="md:w-1/2">
        <img src="<?= htmlspecialchars($event['image_url']) ?>" alt="<?= htmlspecialchars($event['event_name']) ?>" class="w-full h-64 md:h-full object-cover">
    </div>

    <div class="p-8 md:p-12 md:w-1/2 flex flex-col justify-center">
        <h1 class="text-4xl font-bold text-gray-800 mb-3"><?= htmlspecialchars($event['event_name']) ?></h1>
        
        <div class="flex items-center space-x-4 text-gray-500 mb-6">
            <div class="flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5 text-gray-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" /></svg>
                <span><?= htmlspecialchars($event['location']) ?></span>
            </div>
            <span class="text-gray-300">|</span>
            <div class="flex items-center">
                 <span class="px-3 py-1 text-sm font-medium rounded-full 
                    <?= strtolower($event['status']) == "upcoming" ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                    <?= htmlspecialchars(ucfirst($event['status'])) ?>
                </span>
            </div>
        </div>

        <p class="text-gray-600 mb-8 text-base leading-relaxed"><?= htmlspecialchars($event['description']) ?></p>

        <?php
        if (isset($_SESSION['registration_success'])): ?>
            <div class="bg-green-50 border-l-4 border-green-500 text-green-800 p-4 rounded-md text-center" role="alert">
                <p class="font-bold text-lg"><?= $_SESSION['registration_success']; ?></p>
                <p class="mt-2">Your unique ticket number is:</p>
                <p class="text-2xl font-mono bg-green-100 py-2 px-4 rounded-lg mt-2 inline-block"><?= $_SESSION['new_ticket']; ?></p>
                <p class="text-sm mt-3">Please save this ticket number. You can also find it in your dashboard.</p>
            </div>
        <?php
            unset($_SESSION['registration_success']);
            unset($_SESSION['new_ticket']);
        
        elseif ($alreadyRegistered): ?>
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-md">
                <p class="text-blue-800 font-semibold mb-2">
                    ✅ You are already registered as a <strong><?= htmlspecialchars($existingRole) ?></strong>.
                </p>
                <p class="text-sm text-blue-700">Your ticket number is:</p>
                <p class="text-xl font-mono text-blue-900 bg-blue-100 py-2 px-4 rounded-lg mt-1 inline-block">
                    <?= htmlspecialchars($existingTicket) ?>
                </p>
            </div>

        <?php elseif (strtolower($event['status']) == "upcoming"): ?>
            <form method="POST" class="space-y-6">
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700">Choose your role</label>
                    <select name="role" id="role" required class="mt-1 block w-full pl-3 pr-10 py-2.5 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md shadow-sm transition">
                        <option value="participant">Participant</option>
                        <option value="viewer">Viewer</option>
                    </select>
                </div>
                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-transform transform hover:scale-105">
                    Proceed
                </button>
            </form>
            <?php if (isset($errorMessage)): ?>
                <p class="text-red-500 text-sm mt-2"><?= htmlspecialchars($errorMessage) ?></p>
            <?php endif; ?>

        <?php else: ?>
            <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-md">
                <p class="text-red-700 font-semibold">❌ Registration for this event is now closed.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html> 