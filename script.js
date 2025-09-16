// CU EventWeave - JavaScript Functions
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initScrollAnimations();
    initSkillBars();
    initContactForm();
    initCurrentYear();
    initSmoothScrolling();
    
    console.log('CU EventWeave loaded successfully!');
});

// Scroll Animations
function initScrollAnimations() {
    const animatedElements = document.querySelectorAll('.scroll-animate');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('in-view');
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });
    
    animatedElements.forEach((element) => {
        observer.observe(element);
    });
}

// Skill Bar Animations
function initSkillBars() {
    const skillBars = document.querySelectorAll('.skill-progress');
    
    const skillObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                const skillBar = entry.target;
                const skillPercentage = skillBar.getAttribute('data-skill');
                
                setTimeout(() => {
                    skillBar.style.width = skillPercentage + '%';
                }, 500);
                
                skillObserver.unobserve(skillBar);
            }
        });
    }, {
        threshold: 0.5
    });
    
    skillBars.forEach((bar) => {
        skillObserver.observe(bar);
    });
}

// Contact Form Handling
function initContactForm() {
    const contactForm = document.getElementById('contactForm');
    
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const formData = new FormData(contactForm);
            const data = {
                name: formData.get('name'),
                email: formData.get('email'),
                subject: formData.get('subject'),
                message: formData.get('message')
            };
            
            // Basic validation
            if (!data.name || !data.email || !data.subject || !data.message) {
                showNotification('Please fill in all required fields.', 'error');
                return;
            }
            
            if (!isValidEmail(data.email)) {
                showNotification('Please enter a valid email address.', 'error');
                return;
            }
            
            // Simulate form submission
            showNotification('Message sent successfully! We\'ll get back to you soon.', 'success');
            contactForm.reset();
            
            console.log('Form submitted:', data);
        });
    }
}

// Email validation
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Notification system
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${getNotificationIcon(type)}"></i>
            <span>${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        background: ${getNotificationColor(type)};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.3);
        animation: slideInRight 0.3s ease-out;
        max-width: 400px;
    `;
    
    // Add to document
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

function getNotificationIcon(type) {
    switch (type) {
        case 'success': return 'fa-check-circle';
        case 'error': return 'fa-exclamation-circle';
        case 'warning': return 'fa-exclamation-triangle';
        default: return 'fa-info-circle';
    }
}

function getNotificationColor(type) {
    switch (type) {
        case 'success': return '#10b981';
        case 'error': return '#ef4444';
        case 'warning': return '#f97316';
        default: return '#3b82f6';
    }
}

// Add notification animations to CSS
const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .notification-content {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .notification-close {
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        padding: 0.25rem;
        border-radius: 0.25rem;
        transition: background-color 0.2s;
    }
    
    .notification-close:hover {
        background: rgba(255, 255, 255, 0.2);
    }
`;
document.head.appendChild(notificationStyles);

// Set current year in footer
function initCurrentYear() {
    const currentYearElement = document.getElementById('currentYear');
    if (currentYearElement) {
        currentYearElement.textContent = new Date().getFullYear();
    }
}

// Smooth scrolling for anchor links
function initSmoothScrolling() {
    const links = document.querySelectorAll('a[href^="#"]');
    
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            if (href === '#') {
                e.preventDefault();
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }
            
            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                const offsetTop = target.offsetTop - 80; // Account for any fixed header
                window.scrollTo({ top: offsetTop, behavior: 'smooth' });
            }
        });
    });
}

// Button click handlers
document.addEventListener('click', function(e) {
    const target = e.target.closest('button');
    if (!target) return;
    
    const buttonText = target.textContent.trim();
    console.log(`Button clicked: ${buttonText}`);
    switch (buttonText) {
        case 'Explore Events':
            showNotification('Please Login Before Checking the Event Page...', 'error');
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 1000);
            break;
            
        case 'Sign In.':
            showNotification('Redirecting to Login Page....', 'info');
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 1000);
            break;
            
        case 'LinkedIn':
            window.open('https://linkedin.com', '_blank');
            break;
            
        case 'GitHub':
            window.open('https://github.com', '_blank');
            break;
            
        case 'Email':
            window.location.href = 'mailto:rahul.saxena@cu.edu.in';
            break;
            
        case 'Get In Touch':
            document.getElementById('contact').scrollIntoView({ behavior: 'smooth' });
            break;
            
        case 'Download Resume':
            showNotification('Resume download would start here...', 'info');
            break;
            
        case 'Subscribe':
            handleNewsletterSubscription();
            break;
            
        default:
            // Generic button feedback
            if (target.classList.contains('btn')) {
                target.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    target.style.transform = '';
                }, 150);
            }
    }
});

// Newsletter subscription
function handleNewsletterSubscription() {
    const newsletterInput = document.querySelector('.newsletter-input');
    const email = newsletterInput.value.trim();
    
    if (!email) {
        showNotification('Please enter your email address.', 'error');
        return;
    }
    
    if (!isValidEmail(email)) {
        showNotification('Please enter a valid email address.', 'error');
        return;
    }
    
    showNotification('Successfully subscribed to our newsletter!', 'success');
    newsletterInput.value = '';
}

// Add hover effects for interactive elements
document.addEventListener('mouseover', function(e) {
    const target = e.target.closest('.card, .btn, .social-link');
    if (target && target.classList.contains('card')) {
        target.style.transform = 'translateY(-5px)';
    }
});

document.addEventListener('mouseout', function(e) {
    const target = e.target.closest('.card');
    if (target && target.classList.contains('card')) {
        target.style.transform = '';
    }
});

// Parallax effect for hero section (optional enhancement)
window.addEventListener('scroll', function() {
    const scrolled = window.pageYOffset;
    const hero = document.querySelector('.hero-section');
    
    if (hero && scrolled < hero.offsetHeight) {
        const heroImage = hero.querySelector('.hero-image');
        if (heroImage) {
            heroImage.style.transform = `translateY(${scrolled * 0.5}px)`;
        }
    }
});

// Loading animation
window.addEventListener('load', function() {
    document.body.classList.add('loaded');
    
    // Trigger hero animation
    setTimeout(() => {
        const heroText = document.querySelector('.hero-text');
        if (heroText) {
            heroText.style.opacity = '1';
            heroText.style.transform = 'translateY(0)';
        }
    }, 300);
});

// Error handling
window.addEventListener('error', function(e) {
    console.error('JavaScript error:', e.error);
});

// Performance monitoring
if ('performance' in window) {
    window.addEventListener('load', function() {
        setTimeout(() => {
            const perfData = performance.timing;
            const loadTime = perfData.loadEventEnd - perfData.navigationStart;
            console.log(`Page loaded in ${loadTime}ms`);
        }, 0);
    });
}

// Accessibility improvements
document.addEventListener('keydown', function(e) {
    // Skip to main content shortcut
    if (e.key === 'Tab' && e.shiftKey && document.activeElement === document.body) {
        const mainContent = document.querySelector('main') || document.querySelector('#about');
        if (mainContent) {
            mainContent.focus();
            e.preventDefault();
        }
    }
});

// Mobile menu handling (if needed in future)
function toggleMobileMenu() {
    const mobileMenu = document.querySelector('.mobile-menu');
    if (mobileMenu) {
        mobileMenu.classList.toggle('open');
    }
}

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Export functions for potential external use
window.CUEventWeave = {
    showNotification,
    initScrollAnimations,
    toggleMobileMenu,
    debounce,
    throttle
};

// Get the button element by its ID
const scrollToTopBtn = document.getElementById("scrollToTopBtn");

// When the user scrolls down 300px from the top of the document, show the button
window.onscroll = function() {
    scrollFunction();
};

function scrollFunction() {
  // Check if the scroll position is greater than 300 pixels
  if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
    scrollToTopBtn.classList.add("show");
  } else {
    scrollToTopBtn.classList.remove("show");
  }
}

// When the user clicks on the button, scroll to the top of the document smoothly
scrollToTopBtn.addEventListener("click", function() {
  window.scrollTo({
    top: 0,
    behavior: 'smooth' // This enables the smooth scrolling effect
  });
});