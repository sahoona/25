/**
 * Sidebar functionality for GP Theme
 * Handles sidebar toggle, TOC synchronization, and tools
 * 
 * @package GP_Child_Theme
 * @version 22.7.13
 */

document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    // Cache DOM elements for performance
    const sidebar = document.getElementById('gp-sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose = document.querySelector('.sidebar-close');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    const sidebarTocContainer = document.querySelector('.sidebar-toc-container');
    const mainToc = document.getElementById('gp-toc-container');
    
    if (!sidebar || !sidebarToggle) return;
    
    /**
     * Toggle sidebar visibility with proper ARIA support
     * 
     * @param {boolean} forceClose - Force close the sidebar
     */
    function toggleSidebar(forceClose = false) {
        const isOpen = sidebar.classList.contains('gp-sidebar-visible');
        
        if (forceClose || isOpen) {
            // Close sidebar
            sidebar.classList.remove('gp-sidebar-visible');
            sidebar.classList.add('gp-sidebar-hidden');
            sidebarOverlay.classList.remove('active');
            document.body.classList.remove('sidebar-open');
            
            // Update ARIA attributes
            sidebarToggle.setAttribute('aria-expanded', 'false');
            sidebar.setAttribute('aria-hidden', 'true');
            
            // Hide after transition
            setTimeout(() => {
                if (!sidebar.classList.contains('gp-sidebar-visible')) {
                    sidebar.style.display = 'none';
                    sidebarOverlay.style.display = 'none';
                }
            }, 300);
            
            // Save state
            localStorage.setItem('gp-sidebar-state', 'closed');
        } else {
            // Open sidebar
            sidebar.style.display = 'block';
            sidebarOverlay.style.display = 'block';
            
            // Force reflow
            sidebar.offsetHeight;
            
            sidebar.classList.remove('gp-sidebar-hidden');
            sidebar.classList.add('gp-sidebar-visible');
            sidebarOverlay.classList.add('active');
            document.body.classList.add('sidebar-open');
            
            // Update ARIA attributes
            sidebarToggle.setAttribute('aria-expanded', 'true');
            sidebar.setAttribute('aria-hidden', 'false');
            
            // Clone TOC to sidebar if it exists
            if (mainToc && sidebarTocContainer) {
                cloneTocToSidebar();
            }
            
            // Focus management for accessibility
            sidebar.focus();
            
            // Save state
            localStorage.setItem('gp-sidebar-state', 'open');
        }
    }
    
    /**
     * Clone main TOC to sidebar with enhanced functionality
     */
    function cloneTocToSidebar() {
        if (!mainToc || !sidebarTocContainer) return;
        
        // Clear existing TOC in sidebar
        sidebarTocContainer.innerHTML = '';
        
        // Clone the TOC
        const clonedToc = mainToc.cloneNode(true);
        clonedToc.id = 'sidebar-toc-container';
        
        // Optimize for mobile viewing
        if (window.innerWidth <= 768) {
            // Enhance mobile TOC styling
            clonedToc.style.cssText = `
                font-size: 1em !important;
                line-height: 1.6 !important;
            `;
            
            const tocItems = clonedToc.querySelectorAll('li');
            tocItems.forEach(item => {
                item.style.cssText = `
                    padding: 15px 0 15px 20px !important;
                    font-size: 1em !important;
                    border-bottom: 1px solid var(--border-secondary) !important;
                `;
            });
            
            const tocLinks = clonedToc.querySelectorAll('a');
            tocLinks.forEach(link => {
                link.style.cssText = `
                    font-size: 1em !important;
                    font-weight: 500 !important;
                    display: block !important;
                    padding: 5px 0 !important;
                `;
            });
        }
        
        // Update links to work properly
        const links = clonedToc.querySelectorAll('a[href^="#"]');
        links.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                
                if (targetElement) {
                    // Close sidebar on mobile
                    if (window.innerWidth <= 768) {
                        toggleSidebar(true);
                    }
                    
                    // Smooth scroll to target
                    targetElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    
                    // Update URL without scrolling
                    history.pushState(null, null, '#' + targetId);
                }
            });
        });
        
        sidebarTocContainer.appendChild(clonedToc);
    }
    
    /**
     * Handle sidebar tool actions
     * 
     * @param {string} action - The tool action to perform
     */
    function handleToolAction(action) {
        switch (action) {
            case 'print':
                window.print();
                break;
                
            case 'bookmark':
                if (navigator.share) {
                    navigator.share({
                        title: document.title,
                        url: window.location.href
                    }).catch(console.error);
                } else {
                    // Fallback: copy URL to clipboard
                    navigator.clipboard.writeText(window.location.href).then(() => {
                        showToast('URL copied to clipboard.');
                    }).catch(console.error);
                }
                break;
                
            case 'share':
                if (navigator.share) {
                    navigator.share({
                        title: document.title,
                        text: document.querySelector('meta[name="description"]')?.content || '',
                        url: window.location.href
                    }).catch(console.error);
                } else {
                    // Fallback: copy URL to clipboard
                    navigator.clipboard.writeText(window.location.href).then(() => {
                        showToast('URL copied to clipboard.');
                    }).catch(console.error);
                }
                break;
        }
    }
    
    /**
     * Show toast notification
     * 
     * @param {string} message - Message to display
     */
    function showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'gp-toast';
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--bg-secondary);
            color: var(--text-primary);
            padding: 12px 24px;
            border-radius: 8px;
            border: 1px solid var(--border-primary);
            z-index: 10001;
            font-size: 0.9em;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    }
    
    // Event listeners
    sidebarToggle.addEventListener('click', () => toggleSidebar());
    
    if (sidebarClose) {
        // Add both click and touchend for better mobile support
        sidebarClose.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar(true);
        });
        
        // Prevent ghost clicks on mobile
        sidebarClose.addEventListener('touchend', (e) => {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar(true);
        });
    }
    
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', () => toggleSidebar(true));
        
        // Add touch support for mobile
        sidebarOverlay.addEventListener('touchend', (e) => {
            e.preventDefault();
            toggleSidebar(true);
        });
    }
    
    // Tool button listeners
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('sidebar-tool')) {
            const action = e.target.dataset.action;
            if (action) {
                handleToolAction(action);
            }
        }
    });
    
    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        // Close sidebar with Escape key
        if (e.key === 'Escape' && sidebar.classList.contains('gp-sidebar-visible')) {
            toggleSidebar(true);
        }
        
        // Toggle sidebar with Ctrl+B
        if (e.ctrlKey && e.key === 'b') {
            e.preventDefault();
            toggleSidebar();
        }
    });
    
    // Restore sidebar state from localStorage
    const savedState = localStorage.getItem('gp-sidebar-state');
    if (savedState === 'open' && window.innerWidth > 768) {
        // Only auto-open on desktop
        toggleSidebar();
    }
    
    // Handle window resize
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            // Close sidebar on mobile
            if (window.innerWidth <= 768 && sidebar.classList.contains('gp-sidebar-visible')) {
                toggleSidebar(true);
            }
        }, 250);
    });
    
    // Update progress bar in sidebar if it exists
    if (document.getElementById('mybar')) {
        let ticking = false;
        
        function updateProgress() {
            const scrolled = window.scrollY;
            const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
            const progress = Math.min((scrolled / maxScroll) * 100, 100);
            
            // Update main progress bar
            document.getElementById('mybar').style.width = progress + '%';
            document.getElementById('mybar').setAttribute('aria-valuenow', Math.round(progress));
            
            ticking = false;
        }
        
        function requestProgressUpdate() {
            if (!ticking) {
                requestAnimationFrame(updateProgress);
                ticking = true;
            }
        }
        
        window.addEventListener('scroll', requestProgressUpdate, { passive: true });
    }
});