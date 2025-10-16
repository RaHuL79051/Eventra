<?php
session_start();

// Ensure the user is logged in. If not, redirect them to the login page.
if (!isset($_SESSION['uid'])) { // Assuming 'uid' is set on login
    header("Location: login.php");
    exit();
}

// Get the student's name from the session. Provide a fallback if not set.
$student_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'Student';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventra - Student Dashboard</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="stylesheet" href="https://use.typekit.net/yjp3aho.css">
    
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://kit.fontawesome.com/b5b6622958.js" crossorigin="anonymous"></script>
    
    <style>
      * {
        font-family: 'Inter', 'sofia-pro', sans-serif;
      }
      
      /* Custom cursor */
      .custom-cursor {
        position: fixed;
        top: 0;
        left: 0;
        width: 20px;
        height: 20px;
        background: rgba(59, 130, 246, 0.5);
        border-radius: 50%;
        pointer-events: none;
        z-index: 9999;
        transition: all 0.1s ease;
        transform: translate(-50%, -50%);
      }
      
      .cursor-hover {
        width: 40px;
        height: 40px;
        background: rgba(59, 130, 246, 0.3);
        border: 2px solid rgba(59, 130, 246, 0.6);
      }
      
      /* Animation classes */
      .fade-in {
        opacity: 0;
        transform: translateY(30px);
        animation: fadeInUp 0.8s ease forwards;
      }
      
      .fade-in-delay-1 {
        animation-delay: 0.2s;
      }
      
      .fade-in-delay-2 {
        animation-delay: 0.4s;
      }
      
      .fade-in-delay-3 {
        animation-delay: 0.6s;
      }
      
      @keyframes fadeInUp {
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }
      
      /* Card animations */
      .event-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        transform: perspective(1000px) rotateX(0deg);
      }
      
      .event-card:hover {
        transform: perspective(1000px) rotateX(5deg) translateY(-8px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
      }
      
      /* Gradient backgrounds */
      .gradient-bg {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      }
      
      .gradient-card {
        background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
      }
      
      /* Status badges */
      .status-active {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
      }
      
      .status-upcoming {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
      }
      
      .status-completed {
        background: linear-gradient(135deg, #6b7280, #4b5563);
        color: white;
      }
      
      /* Loading skeleton */
      .skeleton {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
      }
      
      @keyframes loading {
        0% {
          background-position: 200% 0;
        }
        100% {
          background-position: -200% 0;
        }
      }
      
      /* Floating animation */
      .float {
        animation: float 3s ease-in-out infinite;
      }
      
      @keyframes float {
        0%, 100% {
          transform: translateY(0px);
        }
        50% {
          transform: translateY(-10px);
        }
      }
    </style>
</head>
<body class="bg-gray-50 min-h-screen relative overflow-x-hidden">
    <div class="custom-cursor" data-id="custom-cursor"></div>

    <header data-id="main-header" class="gradient-bg text-white shadow-2xl sticky top-0 z-40 fade-in">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                
                <div data-id="logo-section" class="flex items-center space-x-3">
                    <a href="dashboard.php" class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-md">
                            <i class="fa-regular fa-calendar-check w-6 h-6 text-purple-600" style="font-size: 1.5rem;"></i>
                        </div>
                        <h1 class="text-2xl font-bold">Eventra</h1>
                    </a>
                </div>

                <nav class="hidden md:flex items-center space-x-8 text-sm font-medium">
                    <a href="dashboard.php" class="hover:text-purple-200 transition-colors duration-300">Home</a>
                    <a href="event_gallery.php" class="hover:text-purple-200 transition-colors duration-300">Event Gallery</a>
                    <a href="tickets.php" class="hover:text-purple-200 transition-colors duration-300">Your Tickets</a>
                    <a href="feedback.php" class="hover:text-purple-200 transition-colors duration-300">Feedback Form</a>
                    <a href="logout.php" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition-all duration-300 backdrop-blur-sm">
                        Logout
                    </a>
                </nav>

                <button id="menu-btn" class="md:hidden text-2xl z-50">
                    <i class="fa-solid fa-bars"></i>
                </button>
                
            </div>
        </div>

        <div id="mobile-menu" class="hidden md:hidden absolute top-0 left-0 w-full h-screen bg-purple-800/95 backdrop-blur-lg">
            <nav class="flex flex-col items-center justify-center h-full space-y-8 text-xl font-medium">
                <a href="dashboard.php" class="hover:text-purple-200">Home</a>
                <a href="gallery.php" class="hover:text-purple-200">Event Gallery</a>
                <a href="tickets.php" class="hover:text-purple-200">Your Tickets</a>
                <a href="feedback.php" class="hover:text-purple-200">Feedback Form</a>
                <a href="logout.php" class="mt-4 bg-white/20 hover:bg-white/30 px-6 py-3 rounded-lg">
                    Logout
                </a>
            </nav>
        </div>
    </header>
    <main class="container mx-auto px-4 py-8">
        <section data-id="welcome-section" class="mb-12 fade-in fade-in-delay-1">
            <div class="text-center max-w-4xl mx-auto">
                <h2 data-id="welcome-title" class="text-4xl md:text-5xl font-bold text-gray-800 mb-4">
                    Hello <?= htmlspecialchars($student_name) ?>! 👋
                </h2>
                <p data-id="welcome-description" class="text-xl text-gray-600 leading-relaxed">
                    Here are the latest events happening around your campus! Discover amazing opportunities to learn, network, and have fun.
                </p>
            </div>
        </section>

        <section data-id="events-section" class="mb-16 fade-in fade-in-delay-2">
                <div class="flex items-center justify-between mb-8">
                <h3 data-id="events-title" class="text-3xl font-bold text-gray-800">
                    Campus Events
                </h3>
                <div data-id="events-filter" class="flex space-x-2">
                    <button class="filter-btn active px-4 py-2 rounded-lg bg-blue-600 text-white" data-filter="all">All</button>
                    <button class="filter-btn px-4 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300" data-filter="active">Active</button>
                    <button class="filter-btn px-4 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300" data-filter="upcoming">Upcoming</button>
                    <button class="filter-btn px-4 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300" data-filter="completed">Completed</button>
                </div>
                </div>

                <div data-id="events-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php
                    include '../config.php';

                    $sql = "SELECT id, event_name, location, description, status, image_url 
                            FROM events 
                            ORDER BY id DESC";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $status = strtolower($row['status']);
                            switch ($status) {
                                case 'active': $statusClass = "bg-green-100 text-green-700"; break;
                                case 'upcoming': $statusClass = "bg-yellow-100 text-yellow-700"; break;
                                case 'completed': $statusClass = "bg-gray-200 text-gray-700"; break;
                                default: $statusClass = "bg-blue-100 text-blue-700";
                            }

                            $imageUrl = (!empty($row['image_url'])) 
                                ? $row['image_url'] 
                                : 'https://images.unsplash.com/photo-1501281668745-f7f57925c3b4?w=400&h=300&fit=crop';

                            // --- FIX IS APPLIED HERE ---
                            echo "
                                <div class='event-card rounded-xl overflow-hidden shadow-lg transform hover:scale-105 transition duration-300 bg-white' data-status='{$status}'>
                                    <img src='{$imageUrl}' alt='{$row['event_name']}' class='w-full h-48 object-cover'>
                                    <div class='p-6'>
                                        <span class='px-3 py-1 rounded-full text-sm font-semibold {$statusClass} inline-block mb-3 capitalize'>
                                            {$row['status']}
                                        </span>
                                        <h4 class='text-xl font-bold text-gray-800 mb-2'>{$row['event_name']}</h4>
                                        <p class='text-gray-600 mb-3 line-clamp-3'>{$row['description']}</p>
                                        <div class='flex items-center text-gray-500 text-sm mb-3'>
                                            <i class='fa-solid fa-location-dot w-4 h-4 mr-2' style='font-size: 1.6rem; padding: 4px 4px 5px 0px;'></i>
                                            <b style='padding-top: 15px;'>{$row['location']}</b>
                                        </div>
                                        <a href='event_register.php?event_id={$row['id']}' 
                                           class='mt-3 inline-block px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition'>
                                           Register Now
                                        </a>
                                    </div>
                                </div>
                            ";
                        }
                    } else {
                        echo "
                            <div data-id='no-events' class='text-center py-16 col-span-3'>
                                <div class='w-24 h-24 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center'>
                                    <i data-lucide='calendar-x' class='w-12 h-12 text-gray-400'></i>
                                </div>
                                <h4 class='text-xl font-semibold text-gray-600 mb-2'>No Events Available</h4>
                                <p class='text-gray-500'>Check back later for exciting events!</p>
                            </div>
                        ";
                    }
                    $conn->close();
                    ?>
                </div>

        </section>

        <section data-id="why-attend-section" class="mb-16 fade-in fade-in-delay-3">
            <div class="gradient-card rounded-2xl p-8 shadow-xl">
                <h3 data-id="why-attend-title" class="text-3xl font-bold text-gray-800 text-center mb-12">
                    Why Attend Events? 🎉
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div data-id="benefit-1" class="text-center float">
                        <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                            <i class="fa-solid fa-users w-8 h-8 text-white" style="font-size: 1.7rem; padding: 4px 4px 5px 0px;"></i>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-800 mb-2">Network & Connect</h4>
                        <p class="text-gray-600">Meet like-minded students and build lasting friendships</p>
                    </div>

                    <div data-id="benefit-2" class="text-center float" style="animation-delay: 0.5s;">
                        <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-r from-green-500 to-teal-600 rounded-full flex items-center justify-center">
                            <i class="fa-solid fa-brain w-8 h-8 text-white" style="font-size: 1.7rem; padding: 4px 4px 5px 0px;"></i>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-800 mb-2">Learn & Grow</h4>
                        <p class="text-gray-600">Acquire new skills and expand your knowledge base</p>
                    </div>

                    <div data-id="benefit-3" class="text-center float" style="animation-delay: 1s;">
                        <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-r from-yellow-500 to-orange-600 rounded-full flex items-center justify-center">
                            <i class="fa-solid fa-trophy w-8 h-8 text-white" style="font-size: 1.7rem; padding: 4px 4px 5px 0px;"></i>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-800 mb-2">Win Prizes</h4>
                        <p class="text-gray-600">Participate in competitions and win amazing rewards</p>
                    </div>

                    <div data-id="benefit-4" class="text-center float" style="animation-delay: 1.5s;">
                        <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-r from-pink-500 to-rose-600 rounded-full flex items-center justify-center">
                            <i class="fa-regular fa-heart w-8 h-8 text-white" style="font-size: 1.7rem; padding: 4px 4px 5px 0px;"></i>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-800 mb-2">Have Fun</h4>
                        <p class="text-gray-600">Enjoy memorable experiences and create lasting memories</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer data-id="main-footer" class="bg-gray-800 text-white py-8 fade-in">
        <div class="container mx-auto px-4 text-center">
            <div class="flex items-center justify-center space-x-2 mb-4">
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                    <i data-lucide="calendar" class="w-4 h-4 text-white"></i>
                </div>
                <span class="text-xl font-semibold">Eventra</span>
            </div>
            <p data-id="copyright" class="text-gray-400">
                © 2024 Eventra - Student Event Management Portal. All rights reserved.
            </p>
        </div>
    </footer>

    <div id="badge-container" data-source="components/badge.html"></div>

    <script>
        const menuBtn = document.getElementById('menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        const menuIcon = menuBtn.querySelector('i');

        menuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
            
            if (mobileMenu.classList.contains('hidden')) {
                menuIcon.classList.remove('fa-xmark');
                menuIcon.classList.add('fa-bars');
            } else {
                menuIcon.classList.remove('fa-bars');
                menuIcon.classList.add('fa-xmark');
            }
        });
    </script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('.filter-btn');
            const eventCards = document.querySelectorAll('.event-card');

            filterButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const filter = button.getAttribute('data-filter');

                    // Update button styles
                    filterButtons.forEach(btn => {
                        btn.classList.remove('active', 'bg-blue-600', 'text-white');
                        btn.classList.add('bg-gray-200', 'text-gray-700', 'hover:bg-gray-300');
                    });
                    button.classList.add('active', 'bg-blue-600', 'text-white');
                    button.classList.remove('bg-gray-200', 'text-gray-700', 'hover:bg-gray-300');

                    // Filter the event cards
                    eventCards.forEach(card => {
                        const cardStatus = card.getAttribute('data-status');
                        if (filter === 'all' || filter === cardStatus) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>