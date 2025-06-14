/**
 * Floating Buttons Enhancement Script
 * Adds click animations and dark mode persistence
 * 
 * @package GP_Child_Theme
 * @version 22.7.14
 */

document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    /**
     * Add click animation to floating buttons
     */
    function addButtonClickAnimation() {
        const floatingButtons = document.querySelectorAll('.floating-btn');
        
        floatingButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                // Add ripple effect
                this.classList.add('ripple');
                
                // Add clicked state
                this.classList.add('clicked');
                
                // Remove clicked state after animation
                setTimeout(() => {
                    this.classList.remove('clicked');
                }, 600);
                
                // Remove ripple effect after animation
                setTimeout(() => {
                    this.classList.remove('ripple');
                }, 600);
            });
        });
    }
    
    /**
     * Dark mode persistence to prevent white flash
     */
    function preventDarkModeFlash() {
        // Check if dark mode was previously enabled
        const isDarkMode = localStorage.getItem('darkMode') === 'true';
        
        if (isDarkMode) {
            // Apply dark mode immediately to prevent flash
            document.documentElement.classList.add('dark-mode-loading');
            document.body.style.backgroundColor = '#242526';
            
            // Wait for DOM to be ready, then apply proper dark mode
            setTimeout(() => {
                document.documentElement.classList.add('dark-mode');
                document.documentElement.classList.remove('dark-mode-loading');
            }, 50);
        }
    }
    
    /**
     * Enhanced dark mode toggle with persistence
     */
    function setupDarkModeToggle() {
        const darkModeToggle = document.getElementById('darkModeToggle');
        if (!darkModeToggle) return;
        
        darkModeToggle.addEventListener('click', function() {
            const html = document.documentElement;
            const isDarkMode = html.classList.contains('dark-mode');
            
            if (isDarkMode) {
                html.classList.remove('dark-mode');
                localStorage.setItem('darkMode', 'false');
            } else {
                html.classList.add('dark-mode');
                localStorage.setItem('darkMode', 'true');
            }
        });
    }
    
    /**
     * Scroll to top with smooth animation
     */
    function setupScrollToTop() {
        const scrollBtn = document.getElementById('scrollToTopBtn');
        if (!scrollBtn) return;
        
        scrollBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
        // Show/hide scroll button based on scroll position
        let ticking = false;
        function updateScrollButton() {
            const scrolled = window.scrollY;
            const showThreshold = 300;
            
            if (scrolled > showThreshold) {
                scrollBtn.style.opacity = '1';
                scrollBtn.style.pointerEvents = 'auto';
            } else {
                scrollBtn.style.opacity = '0';
                scrollBtn.style.pointerEvents = 'none';
            }
            
            ticking = false;
        }
        
        window.addEventListener('scroll', function() {
            if (!ticking) {
                requestAnimationFrame(updateScrollButton);
                ticking = true;
            }
        }, { passive: true });
        
        // Initial check
        updateScrollButton();
    }
    
    /**
     * Add tactile feedback to all interactive elements
     */
    function addTactileFeedback() {
        const interactiveElements = document.querySelectorAll('button, .floating-btn, a');
        
        interactiveElements.forEach(element => {
            element.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.95)';
            }, { passive: true });
            
            element.addEventListener('touchend', function() {
                this.style.transform = '';
            }, { passive: true });
        });
    }
    
    // Initialize all enhancements
    preventDarkModeFlash();
    addButtonClickAnimation();
    setupDarkModeToggle();
    setupScrollToTop();
    addTactileFeedback();
    
    // Preload critical resources on interaction
    let resourcesPreloaded = false;
    function preloadResources() {
        if (resourcesPreloaded) return;
        resourcesPreloaded = true;
        
        // Preload commonly used images or resources
        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = '/wp-content/themes/generatepress/style.css';
        document.head.appendChild(link);
    }
    
    // Preload on first user interaction
    ['mousedown', 'touchstart', 'keydown'].forEach(event => {
        document.addEventListener(event, preloadResources, { once: true, passive: true });
    });
});