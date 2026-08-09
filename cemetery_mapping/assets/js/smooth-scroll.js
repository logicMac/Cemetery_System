/**
 * Smooth Scroll Implementation
 * Custom easeInOutCubic smooth scrolling
 */

// EaseInOutCubic timing function
function easeInOutCubic(t) {
    return t < 0.5 ? 4 * t * t * t : (t - 1) * (2 * t - 2) * (2 * t - 2) + 1;
}

/**
 * Smooth scroll to target position
 * @param {number} targetPosition - Target scroll position
 * @param {number} duration - Animation duration in milliseconds
 */
function smoothScrollTo(targetPosition, duration = 800) {
    const startPosition = window.pageYOffset;
    const distance = targetPosition - startPosition;
    let startTime = null;

    function animation(currentTime) {
        if (startTime === null) startTime = currentTime;
        const timeElapsed = currentTime - startTime;
        const progress = Math.min(timeElapsed / duration, 1);
        const ease = easeInOutCubic(progress);
        
        window.scrollTo(0, startPosition + distance * ease);
        
        if (timeElapsed < duration) {
            requestAnimationFrame(animation);
        }
    }

    requestAnimationFrame(animation);
}

/**
 * Smooth scroll to element
 * @param {string|HTMLElement} target - Target element or selector
 * @param {number} offset - Offset from top in pixels
 */
function scrollToElement(target, offset = 0) {
    const element = typeof target === 'string' ? document.querySelector(target) : target;
    if (!element) return;
    
    const targetPosition = element.getBoundingClientRect().top + window.pageYOffset - offset;
    smoothScrollTo(targetPosition);
}

// Handle anchor links
document.addEventListener('DOMContentLoaded', function() {
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href === '#') return;
            
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                scrollToElement(target, 80);
            }
        });
    });
});

// Parallax scrolling effect
function initParallax() {
    const parallaxElements = document.querySelectorAll('[data-parallax]');
    
    if (parallaxElements.length === 0) return;
    
    function updateParallax() {
        const scrolled = window.pageYOffset;
        
        parallaxElements.forEach(element => {
            const speed = parseFloat(element.getAttribute('data-parallax')) || 0.5;
            const yPos = -(scrolled * speed);
            element.style.transform = `translateY(${yPos}px)`;
        });
    }
    
    window.addEventListener('scroll', updateParallax, { passive: true });
    updateParallax();
}

// Scroll-triggered animations
function initScrollAnimations() {
    const animatedElements = document.querySelectorAll('.fade-in-up, .fade-in');
    
    if (animatedElements.length === 0) return;
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });
    
    animatedElements.forEach(element => observer.observe(element));
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initParallax();
    initScrollAnimations();
});

// Export functions for use in other scripts
window.smoothScroll = {
    to: smoothScrollTo,
    toElement: scrollToElement
};
