<?php
include '../config.php'; // your DB connection

// Fetch all events
$sql = "SELECT * FROM event_gallery ORDER BY event_date DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventra Gallery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f7fc;
        }
        .back-to-dashboard {
            position: absolute;
            top: 2rem;
            left: 2rem;
            z-index: 10;
        }
        .gallery-header {
            font-weight: 700;
            text-align: center;
            font-size: 2.8rem;
            color: #1a253c;
            margin-bottom: 3rem;
            position: relative;
        }
        .gallery-header::after {
            content: '';
            display: block;
            margin: 0.6rem auto 0;
            width: 80px;
            height: 4px;
            border-radius: 2px;
            background: linear-gradient(90deg, #4f46e5, #7c3aed);
        }
        .event-card {
            border: none;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            opacity: 0;
            transform: translateY(30px);
            will-change: transform, opacity;
        }
        .event-card.is-visible {
            opacity: 1;
            transform: translateY(0px);
        }
        .event-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 18px 35px rgba(0,0,0,0.12);
        }
        .card-img-container {
            position: relative;
            height: 280px;
        }
        .gallery-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .card-img-overlay {
            background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0) 80%);
            display: flex;
            align-items: flex-end;
            padding: 1.5rem;
        }
        .card-title {
            color: #fff;
            font-size: 1.25rem;
            font-weight: 600;
        }
        .card-text {
            color: #e0e0e0;
            font-size: 0.9rem;
        }
        .modal-content {
            border-radius: 1rem;
            border: none;
            background-color: #f8f9fa;
        }
        .modal-carousel-container {
            position: relative;
        }
        .image-counter {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: rgba(0,0,0,0.5);
            color: #fff;
            padding: 0.25rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.85rem;
            z-index: 5;
        }
        .modal-carousel-img {
            max-height: 65vh;
            object-fit: contain;
        }
        .thumbnail-strip {
            padding: 1rem;
            background-color: #e9ecef;
            display: flex;
            overflow-x: auto;
            gap: 0.75rem;
        }
        .thumbnail-item {
            cursor: pointer;
            width: 80px;
            height: 60px;
            border-radius: 0.5rem;
            object-fit: cover;
            border: 2px solid transparent;
            transition: border-color 0.3s ease, opacity 0.3s ease;
            opacity: 0.6;
        }
        .thumbnail-item.active, .thumbnail-item:hover {
            border-color: #4f46e5;
            opacity: 1;
        }
        
        /* --- NEW RULE TO SLOW DOWN CAROUSEL --- */
        .carousel-item {
            transition: transform 1.2s ease-in-out !important;
        }

    </style>
</head>
<body>

    <a href="dashboard.php" class="btn btn-primary back-to-dashboard shadow-sm">
        <i class="fa-solid fa-arrow-left me-2"></i>Back to Dashboard
    </a>

    <div class="container py-5">
        <h1 class="gallery-header mt-5">Event Gallery</h1>
    
        <div class="row g-4">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): 
                    $eventName = htmlspecialchars($row['event_name']);
                    $eventDate = date("F j, Y", strtotime($row['event_date']));
                    $thumbnail = htmlspecialchars($row['thumbnail']);
                    $eventId = $row['id'];
                    $images = array_filter(explode(",", $row['image_urls']));
                ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card event-card" data-bs-toggle="modal" data-bs-target="#galleryModal<?= $eventId ?>">
                            <div class="card-img-container">
                                <img src="<?= $thumbnail ?>" class="gallery-img" alt="Thumbnail for <?= $eventName ?>">
                                <div class="card-img-overlay">
                                    <div>
                                        <h5 class="card-title"><?= $eventName ?></h5>
                                        <p class="card-text mb-0"><i class="fa-regular fa-calendar-alt me-2"></i><?= $eventDate ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="galleryModal<?= $eventId ?>" tabindex="-1">
                        <div class="modal-dialog modal-xl modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header border-0">
                                    <h5 class="modal-title fw-bold"><?= $eventName ?> - Gallery</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-0">
                                    <?php if (!empty($images)): ?>
                                        <div id="carousel<?= $eventId ?>" class="carousel slide" data-bs-ride="carousel" data-bs-interval="false">
                                            <div class="modal-carousel-container">
                                                <div class="carousel-inner">
                                                    <?php foreach ($images as $index => $img): ?>
                                                        <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                                            <div class="image-counter"><?= $index + 1 ?> / <?= count($images) ?></div>
                                                            <img src="<?= htmlspecialchars(trim($img)) ?>" 
                                                                 class="d-block w-100 modal-carousel-img" 
                                                                 alt="Photo <?= $index+1 ?> of <?= $eventName ?>">
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <button class="carousel-control-prev" type="button" data-bs-target="#carousel<?= $eventId ?>" data-bs-slide="prev">
                                                    <span class="carousel-control-prev-icon"></span>
                                                </button>
                                                <button class="carousel-control-next" type="button" data-bs-target="#carousel<?= $eventId ?>" data-bs-slide="next">
                                                    <span class="carousel-control-next-icon"></span>
                                                </button>
                                            </div>
                                            
                                            <div class="thumbnail-strip">
                                                <?php foreach ($images as $index => $img): ?>
                                                    <img src="<?= htmlspecialchars(trim($img)) ?>" 
                                                         class="thumbnail-item <?= $index === 0 ? 'active' : '' ?>" 
                                                         data-bs-target="#carousel<?= $eventId ?>" 
                                                         data-bs-slide-to="<?= $index ?>">
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="d-flex flex-column align-items-center justify-content-center py-5 text-center text-muted">
                                            <i class="fa-regular fa-image fa-3x mb-3"></i>
                                            <h5>No images uploaded yet</h5>
                                            <p class="mb-0">Please check back later to see photos from this event.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <h4 class="text-muted">No Event Galleries Found</h4>
                    <p class="text-secondary">Please check back later for amazing event photos!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const cards = document.querySelectorAll('.event-card');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    cards.forEach(card => observer.observe(card));

    const carousels = document.querySelectorAll('.carousel');
    carousels.forEach(carousel => {
        const carouselId = carousel.id;
        const thumbnails = document.querySelectorAll(`.thumbnail-item[data-bs-target="#${carouselId}"]`);
        
        carousel.addEventListener('slide.bs.carousel', event => {
            const activeIndex = event.to;
            thumbnails.forEach(thumb => thumb.classList.remove('active'));
            if (thumbnails[activeIndex]) {
                thumbnails[activeIndex].classList.add('active');
            }
        });
    });
});
</script>
</body>
</html>