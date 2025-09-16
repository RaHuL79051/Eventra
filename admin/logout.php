<?php
// Step 1: Start the session to access it.
session_start();

// Step 2: Unset all of the session variables.
$_SESSION = array();

// Step 3: Destroy the session.
session_destroy();

// Note: No redirect is needed here, as the rest of the page will be displayed to the user.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logged Out | Eventra</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow: hidden;
        }
        .logout-card {
            max-width: 500px;
            width: 100%;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 1.5rem;
            padding: 3rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            text-align: center;
            animation: fadeIn 0.8s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .icon-container {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, #1e88e5, #4f46e5);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: #fff;
            font-size: 2.5rem;
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.3);
            animation: popIn 0.6s 0.2s ease-out backwards;
        }
        @keyframes popIn {
            from { transform: scale(0); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        h1 {
            font-weight: 700;
            color: #333;
            font-size: 2rem;
            margin-bottom: 0.75rem;
        }
        .subtitle {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 2.5rem;
        }
        .button-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        .btn-custom {
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
            border: none;
        }
        .btn-primary-custom {
            background: linear-gradient(90deg, #4f46e5, #7c3aed);
            color: #fff;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.2);
        }
        .btn-primary-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.3);
            color: #fff;
        }
        .btn-secondary-custom {
            background-color: #e9ecef;
            color: #495057;
        }
        .btn-secondary-custom:hover {
            background-color: #dee2e6;
            transform: translateY(-3px);
        }
    </style>
</head>
<body>
    <div class="logout-card">
        <div class="icon-container">
            <i class="fa-solid fa-check"></i>
        </div>
        <h1>Logged Out Successfully</h1>
        <p class="subtitle">Thank you for visiting Eventra. We hope to see you again soon!</p>
        
        <div class="button-group">
            <!-- IMPORTANT: Change href to your actual landing page (e.g., index.php) -->
            <a href="../index.html" class="btn-custom btn-primary-custom">
                Go to Homepage
            </a>
            <!-- IMPORTANT: Change href to your actual login page (e.g., login.php) -->
            <a href="../login.php" class="btn-custom btn-secondary-custom">
                Back to Login
            </a>
        </div>
    </div>
</body>
</html>