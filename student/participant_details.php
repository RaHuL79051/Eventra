<?php
include '../config.php';
session_start();

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Ensure event_id is passed and valid
if (!isset($_GET['event_id'])) {
    die("Invalid Event!");
}
$event_id = intval($_GET['event_id']);
$user_id = $_SESSION['user_id'];

// Fetch Event Details to display name
$stmt = $conn->prepare("SELECT event_name FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    die("Event not found!");
}
$event = $result->fetch_assoc();
$stmt->close();


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get details from form
    $full_name = $_POST['full_name'];
    $phone_number = $_POST['phone_number'];
    $organization = $_POST['organization'];
    $role = 'participant'; // Role is fixed here

    // Use a transaction to ensure both inserts succeed or fail together
    $conn->begin_transaction();

    try {
        // 1. Generate Ticket and insert into event_registrations
        $ticket_number = strtoupper('EVT' . $event_id . '-' . bin2hex(random_bytes(5)));
        
        $stmt1 = $conn->prepare("INSERT INTO event_registrations (event_id, user_id, role, ticket_number) VALUES (?, ?, ?, ?)");
        $stmt1->bind_param("iiss", $event_id, $user_id, $role, $ticket_number);
        $stmt1->execute();

        // 2. Get the ID of the new registration
        $registration_id = $conn->insert_id;

        // 3. Insert into participant_details
        $stmt2 = $conn->prepare("INSERT INTO participant_details (registration_id, full_name, phone_number, organization) VALUES (?, ?, ?, ?)");
        $stmt2->bind_param("isss", $registration_id, $full_name, $phone_number, $organization);
        $stmt2->execute();

        // If both queries are successful, commit the transaction
        $conn->commit();
        
        // Set session variables and redirect back to the main event page
        $_SESSION['registration_success'] = "You have successfully registered for the event!";
        $_SESSION['new_ticket'] = $ticket_number;
        header('Location: event_register.php?event_id=' . $event_id); // Change 'register_event.php' to your main file name
        exit();

    } catch (mysqli_sql_exception $exception) {
        $conn->rollback(); // Rollback the transaction on error
        $errorMessage = "Error during registration: " . $exception->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Participant Details for <?= htmlspecialchars($event['event_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gradient-to-br from-indigo-50 via-white to-cyan-50 min-h-screen flex items-center justify-center p-4">

<div class="max-w-xl w-full mx-auto bg-white p-8 md:p-12 rounded-2xl shadow-xl">
    <h1 class="text-3xl font-bold text-gray-800 mb-2">Participant Details</h1>
    <p class="text-gray-600 mb-8">For the event: <strong><?= htmlspecialchars($event['event_name']) ?></strong></p>

    <?php if (isset($errorMessage)): ?>
        <p class="text-red-500 text-center mb-4"><?= htmlspecialchars($errorMessage) ?></p>
    <?php endif; ?>
    
    <form method="POST" class="space-y-6">
        <div>
            <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
            <input type="text" name="full_name" id="full_name" required class="mt-1 block w-full p-2.5 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <div>
            <label for="phone_number" class="block text-sm font-medium text-gray-700">Phone Number</label>
            <input type="tel" name="phone_number" id="phone_number" class="mt-1 block w-full p-2.5 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <div>
            <label for="organization" class="block text-sm font-medium text-gray-700">Organization / College</label>
            <input type="text" name="organization" id="organization" class="mt-1 block w-full p-2.5 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        
        <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-transform transform hover:scale-105">
            Complete Registration
        </button>
    </form>
</div>

</body>
</html>