/**
 * GP Theme - Main JavaScript File
 * Combined functionality for all interactive features
 * Version: 22.7.14 (Complete Integration)
 */

document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    const body = document.body;
    const html = document.documentElement;
    
    // =========================================================================
    // 1. DARK MODE MANAGEMENT
    // =========================================================================
    
    /**
     * Manages dark mode functionality, using 'darkMode' localStorage key.
     */
    function initDarkMode() {
        const darkModeToggle = document.getElementById('darkModeToggle');
        if (!darkModeToggle) return;

        const htmlEl = document.documentElement;

        // Function to apply the theme based on the state
        function applyTheme(isDark) {
            if (isDark) {
                htmlEl.classList.add('dark-mode-active');
            } else {
                htmlEl.classList.remove('dark-mode-active');
            }
        }

        // Toggle button click listener
        darkModeToggle.addEventListener('click', () => {
            const isCurrentlyDark = htmlEl.classList.contains('dark-mode-active');
            const wantsDark = !isCurrentlyDark;

            applyTheme(wantsDark);

            // Save the user's explicit choice to localStorage
            localStorage.setItem('darkMode', wantsDark ? 'true' : 'false');
            
            // Add click animation
            darkModeToggle.classList.add('clicked');
            setTimeout(() => darkModeToggle.classList.remove('clicked'), 600);
        });

        // Initial check on load (This part is mostly handled by the inline script now,
        // but it's good practice to ensure the toggle button's visual state is correct if needed).
        // The inline script already sets the class, so this function's main job is the click handler.
    }
    
    // =========================================================================
    // 2. FLOATING BUTTONS & SCROLL TO TOP
    // =========================================================================
    
    /**
     * Setup floating buttons with animations
     */
    function setupFloatingButtons() {
        const floatingButtons = document.querySelectorAll('.floating-btn');
        
        floatingButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                // Add ripple effect
                this.classList.add('ripple');
                this.classList.add('clicked');
                
                // Remove effects after animation
                setTimeout(() => {
                    this.classList.remove('clicked', 'ripple');
                }, 600);
            });
        });
        
        // Scroll to top functionality
        const scrollToTopBtn = document.getElementById('scrollToTopBtn');
        if (scrollToTopBtn) {
            scrollToTopBtn.addEventListener('click', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });

            // All JavaScript logic for hiding/showing the button based on scroll has been removed.
        }
    }
    
    // =========================================================================
    // 3. SIDEBAR FUNCTIONALITY
    // =========================================================================
    
    /**
     * Setup sidebar toggle and functionality
     */
    function setupSidebar() {
        const sidebar = document.getElementById('gp-sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarClose = document.querySelector('.sidebar-close');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        
        if (!sidebar || !sidebarToggle) {
            console.log('Sidebar elements not found, skipping sidebar setup');
            return;
        }
        
        function toggleSidebar(forceClose = false) {
            const isOpen = sidebar.classList.contains('gp-sidebar-visible');
            
            if (forceClose || isOpen) {
                sidebar.classList.remove('gp-sidebar-visible');
                if (sidebarOverlay) sidebarOverlay.classList.remove('active');
                document.body.classList.remove('sidebar-open');
                
                setTimeout(() => {
                    if (!sidebar.classList.contains('gp-sidebar-visible')) {
                        sidebar.style.display = 'none';
                        if (sidebarOverlay) sidebarOverlay.style.display = 'none';
                    }
                }, 300);
            } else {
                sidebar.style.display = 'block';
                if (sidebarOverlay) sidebarOverlay.style.display = 'block';
                
                setTimeout(() => {
                    sidebar.classList.add('gp-sidebar-visible');
                    if (sidebarOverlay) sidebarOverlay.classList.add('active');
                    document.body.classList.add('sidebar-open');
                }, 10);
                
                cloneTocToSidebar();
            }
        }
        
        function cloneTocToSidebar() {
            const mainToc = document.getElementById('gp-toc-container');
            const sidebarTocContainer = document.querySelector('.sidebar-toc-container');
            
            if (!mainToc || !sidebarTocContainer) return;
            
            sidebarTocContainer.innerHTML = '';
            const clonedToc = mainToc.cloneNode(true);
            clonedToc.id = 'sidebar-toc-container';
            
            const links = clonedToc.querySelectorAll('a[href^="#"]');
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href').substring(1);
                    const targetElement = document.getElementById(targetId);
                    
                    if (targetElement) {
                        if (window.innerWidth <= 768) {
                            toggleSidebar(true);
                        }
                        
                        targetElement.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
            
            sidebarTocContainer.appendChild(clonedToc);
        }
        
        // Event listeners
        sidebarToggle.addEventListener('click', () => toggleSidebar());
        if (sidebarClose) {
            sidebarClose.addEventListener('click', () => toggleSidebar(true));
        }
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', () => toggleSidebar(true));
        }
        
        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('gp-sidebar-visible')) {
                toggleSidebar(true);
            }
        });
    }
    
    // =========================================================================
    // 4. URL COPY FUNCTIONALITY
    // =========================================================================
    
    /**
     * Setup URL copy buttons
     */
    function setupURLCopy() {
        // Top URL copy button
        document.addEventListener('click', function(e) {
            const copyBtn = e.target.closest('.gp-copy-url-btn');
            if (copyBtn) {
                e.preventDefault();
                const urlToCopy = window.location.href;
                navigator.clipboard.writeText(urlToCopy).then(() => {
                    copyBtn.classList.add('copied');
                    setTimeout(() => {
                        copyBtn.classList.remove('copied');
                    }, 2000);
                });
            }
        });
        
        // Bottom share URL button
        document.body.addEventListener('click', function(event) {
            const bottomShareButton = event.target.closest('.social-share-btn.copy-link-icon-bottom');

            if (bottomShareButton) {
                event.preventDefault();

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(window.location.href).then(() => {
                        bottomShareButton.classList.add('copied-feedback');
                        setTimeout(() => {
                            bottomShareButton.classList.remove('copied-feedback');
                        }, 2000);
                    }).catch(err => {
                        console.error('Failed to copy URL: ', err);
                    });
                }
            }
        });
    }
    
    // =========================================================================
    // 5. READING PROGRESS BAR
    // =========================================================================
    
    /**
     * Setup reading progress bar
     */
    function setupProgressBar() {
        const progressBar = document.getElementById('mybar');
        if (progressBar) {
            window.addEventListener('scroll', () => {
                const scrollPercent = (window.scrollY / (document.documentElement.scrollHeight - document.documentElement.clientHeight)) * 100;
                progressBar.style.width = scrollPercent + '%';
            });
        }
    }
    
    // =========================================================================
    // 6. TABLE OF CONTENTS
    // =========================================================================
    
    /**
     * Setup table of contents functionality
     */
    function setupTOC() {
        const tocContainer = document.getElementById('gp-toc-container');
        if (tocContainer) {
            const tocTitle = tocContainer.querySelector('.gp-toc-title');
            const tocToggle = tocContainer.querySelector('.gp-toc-toggle');
            const tocList = tocContainer.querySelector('.gp-toc-list');
            if (tocTitle && tocList) {
                tocTitle.style.cursor = 'pointer';
                tocTitle.addEventListener('click', function(e) {
                    e.preventDefault();
                    // Toggle a class to show/hide the list
                    tocList.classList.toggle('toc-list-hidden');
                    const isHidden = tocList.classList.contains('toc-list-hidden');

                    if (tocToggle) {
                        // 클래스로 아이콘 상태 변경
                        if (isHidden) {
                            tocToggle.classList.remove('show'); // Assuming 'show' means arrow down (list visible)
                        } else {
                            tocToggle.classList.add('show');    // Arrow up (list hidden)
                        }
                    }
                });
            }
        }
    }
    
    /**
     * Animates the Table of Contents (TOC) when it scrolls into view.
     */
    /*
    function setupTocAnimation() {
        const tocContainer = document.getElementById('gp-toc-container');
        if (!tocContainer) {
            // If TOC doesn't exist on the page, set its potential parent to visible
            // to prevent layout shifts or hidden states from other rules.
            // This is a defensive check.
            if (document.querySelector('.entry-header-wrapper')) {
                 document.querySelector('.entry-header-wrapper').style.opacity = 1;
            }
            return;
        }

        const observer = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target); // Important: Stop observing after it's visible
                }
            });
        }, {
            rootMargin: '0px',
            threshold: 0.05 // Triggers when at least 5% of the TOC is visible
        });

        observer.observe(tocContainer);
    }
    
    // =========================================================================
    // 7. STAR RATING SYSTEM
    // =========================================================================
    
    /**
     * Setup star rating functionality
     */
    function setupStarRating() {
        const starRatingContainer = document.querySelector('.gp-star-rating-container');
        if (starRatingContainer) {
            const postId = starRatingContainer.dataset.postId;
            const storageKey = `gp_star_rating_${postId}`;
            let currentRating = parseFloat(localStorage.getItem(storageKey)) || 0;
            let tempRating = 0;
            const starsWrapper = starRatingContainer.querySelector('.stars-wrapper');
            const starsForeground = starRatingContainer.querySelector('.stars-foreground');
            const ratingText = starRatingContainer.querySelector('.rating-text');
            const userRatingText = starRatingContainer.querySelector('.user-rating-text');
            const editRatingBtn = starRatingContainer.querySelector('.edit-rating-btn');
            const submitRatingBtn = starRatingContainer.querySelector('.submit-rating-btn');
            let initialAverage = parseFloat(ratingText.getAttribute('data-initial-average')) || 0;
            
            const updateUserRatingText = (rating) => {
                if (rating > 0) {
                    userRatingText.textContent = `You rated: ${rating.toFixed(1)}`;
                    userRatingText.style.display = 'block';
                } else {
                    userRatingText.style.display = 'none';
                }
            };
            
            if (currentRating > 0) {
                starRatingContainer.classList.add('voted');
                updateUserRatingText(currentRating);
            }
            
            const updateStars = (rating) => {
                const percentage = (rating / 5) * 100;
                starsForeground.style.width = `${percentage}%`;
            };
            
            const getRatingFromEvent = (e) => {
                const rect = starsWrapper.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const width = rect.width;
                const preciseRating = (x / width) * 5;
                const halfStarRating = Math.round(preciseRating * 2) / 2;
                return Math.max(0.5, Math.min(5, halfStarRating));
            };
            
            starsWrapper.addEventListener('mousemove', function(e) {
                if (starRatingContainer.classList.contains('voted') && !starRatingContainer.classList.contains('editing')) return;
                const hoverRating = getRatingFromEvent(e);
                updateStars(hoverRating);
            });
            
            starsWrapper.addEventListener('mouseleave', function() {
                if (starRatingContainer.classList.contains('editing')) {
                    updateStars(tempRating);
                } else {
                    updateStars(initialAverage);
                }
            });
            
            starsWrapper.addEventListener('click', function(e) {
                if (starRatingContainer.classList.contains('voted') && !starRatingContainer.classList.contains('editing')) return;
                const newRating = getRatingFromEvent(e);
                tempRating = newRating;
                updateStars(newRating);
                if (!starRatingContainer.classList.contains('editing')) {
                    submitRating(newRating);
                }
            });
            
            if (editRatingBtn) {
                editRatingBtn.addEventListener('click', function() {
                    starRatingContainer.classList.add('editing');
                    starRatingContainer.classList.remove('voted');
                    tempRating = currentRating;
                    userRatingText.style.display = 'none';
                });
            }
            
            if (submitRatingBtn) {
                submitRatingBtn.addEventListener('click', function() {
                    if (tempRating > 0) { submitRating(tempRating); }
                });
            }
            
            function submitRating(ratingToSubmit) {
                const oldRating = parseFloat(localStorage.getItem(storageKey)) || 0;
                jQuery.ajax({
                    url: gp_ajax.ajax_url, type: 'POST',
                    data: { action: 'gp_handle_star_rating', post_id: postId, new_rating: ratingToSubmit, old_rating: oldRating, nonce: gp_ajax.star_rating_nonce },
                    success: function(response) {
                        if (response.success) {
                            currentRating = ratingToSubmit;
                            localStorage.setItem(storageKey, currentRating.toString());
                            initialAverage = response.data.average;
                            const newVotes = response.data.votes;
                            ratingText.setAttribute('data-initial-average', initialAverage.toFixed(1));
                            ratingText.querySelector('span:first-child').textContent = initialAverage.toFixed(1);
                            ratingText.title = `${newVotes} votes`;
                            updateStars(ratingToSubmit);
                            updateUserRatingText(currentRating);
                            starRatingContainer.classList.remove('editing');
                            starRatingContainer.classList.add('voted');
                            starRatingContainer.classList.add('submitted');
                            setTimeout(() => { starRatingContainer.classList.remove('submitted'); }, 800);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('Star rating AJAX failed:', textStatus, errorThrown);
                        // Optionally, revert UI changes or inform the user
                        // For example, you might want to revert stars to the initial average:
                        // updateStars(initialAverage);
                        // And reset button/container states if they were changed optimistically:
                        // starRatingContainer.classList.remove('voted', 'editing', 'submitted');
                    }
                });
            }
        }
    }
    
    // =========================================================================
    // 8. REACTION BUTTONS
    // =========================================================================
    
    /**
     * Setup reaction buttons functionality
     */
    function setupReactionButtons() {
        document.querySelectorAll('.reaction-btn').forEach(button => {
            const postId = button.dataset.postId;
            const cooldownKey = `gpCooldown_${postId}`;
            const setCooldownState = (isCoolingDown) => {
                document.querySelectorAll(`.reaction-btn[data-post-id="${postId}"]`).forEach(btn => {
                    btn.classList.toggle('cooldown', isCoolingDown);
                    btn.disabled = isCoolingDown;
                });
            };
            const checkCooldown = () => {
                const cooldownEndTime = localStorage.getItem(cooldownKey);
                if (cooldownEndTime && Date.now() < cooldownEndTime) {
                    setCooldownState(true);
                    setTimeout(() => { setCooldownState(false); }, cooldownEndTime - Date.now());
                    return true;
                }
                setCooldownState(false);
                return false;
            };
            checkCooldown();
            button.addEventListener('click', function() {
                if (this.disabled) return;
                const reaction = this.dataset.reaction;
                const countSpan = this.querySelector('.reaction-count');
                countSpan.textContent = parseInt(countSpan.textContent) + 1;
                const cooldownDuration = 10000;
                localStorage.setItem(cooldownKey, Date.now() + cooldownDuration);
                setCooldownState(true);
                setTimeout(() => setCooldownState(false), cooldownDuration);
                jQuery.ajax({
                    url: gp_ajax.ajax_url, type: 'POST',
                    data: { action: 'gp_handle_reaction', post_id: postId, reaction: reaction, nonce: gp_ajax.reactions_nonce },
                    error: () => {
                        countSpan.textContent = parseInt(countSpan.textContent) - 1;
                        localStorage.removeItem(cooldownKey);
                        setCooldownState(false);
                    }
                });
            });
        });
    }
    
    // =========================================================================
    // 9. MISCELLANEOUS FEATURES
    // =========================================================================
    
    /**
     * Setup posted date toggles
     */
    function setupPostedDateToggles() {
        const postedOnWrappers = document.querySelectorAll('.posted-on-wrapper');
        postedOnWrappers.forEach(function (wrapper) {
            const dateSecondary = wrapper.querySelector('.date-secondary');
            // const datePrimary = wrapper.querySelector('.date-primary'); // Not directly needed for event logic if wrapper is target

            if (dateSecondary) { // Setup toggle only if published date view exists
                wrapper.classList.add('is-updatable');
                wrapper.setAttribute('tabindex', '0');
                wrapper.setAttribute('role', 'button');
                // Initial state: 'Updated' date is visible, so 'Published' is not pressed/active.
                wrapper.setAttribute('aria-pressed', 'false');
                // Consider adding an aria-label that changes, or use aria-live for announcements later if needed.
                // For now, the visible text change is the primary feedback.

                wrapper.addEventListener('click', function () {
                    this.classList.toggle('state-published-visible');
                    const isPublishedVisible = this.classList.contains('state-published-visible');
                    this.setAttribute('aria-pressed', isPublishedVisible);
                });

                wrapper.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        this.classList.toggle('state-published-visible');
                        const isPublishedVisible = this.classList.contains('state-published-visible');
                        this.setAttribute('aria-pressed', isPublishedVisible);
                    }
                });
            }
        });
    }
    
    /**
     * Setup language toggle functionality for the new switcher structure (button + list)
     */
    function setupLanguageToggle() {
        // Use the ID of the main switcher container for more specific targeting if needed,
        // especially for the click-outside logic.
        const switcherContainer = document.getElementById('gp-language-switcher');
        if (!switcherContainer) {
            // console.warn('Language switcher container #gp-language-switcher not found.');
            return;
        }

        const toggleButton = document.getElementById('gp-lang-switcher-button');
        const languageList = document.getElementById('gp-lang-switcher-list');

        if (!toggleButton || !languageList) {
            console.warn('Language switcher button (#gp-lang-switcher-button) or list (#gp-lang-switcher-list) not found.');
            return;
        }

        toggleButton.addEventListener('click', function(event) {
            event.stopPropagation(); // Important: Prevents the document click listener from immediately closing the list.

            const isExpanded = toggleButton.getAttribute('aria-expanded') === 'true';

            if (isExpanded) {
                languageList.setAttribute('hidden', '');
                toggleButton.setAttribute('aria-expanded', 'false');
                switcherContainer.classList.remove('active'); // For CSS styling based on active state
            } else {
                languageList.removeAttribute('hidden');
                toggleButton.setAttribute('aria-expanded', 'true');
                switcherContainer.classList.add('active'); // For CSS styling based on active state
                // Optional: Focus the first item in the list when opened
                // const firstLink = languageList.querySelector('a.lang-link, span.lang-text');
                // if (firstLink) firstLink.focus();
            }
        });

        // Close the dropdown if a click occurs outside of the language switcher container
        document.addEventListener('click', function(event) {
            if (!switcherContainer.contains(event.target)) {
                if (toggleButton.getAttribute('aria-expanded') === 'true') {
                    languageList.setAttribute('hidden', '');
                    toggleButton.setAttribute('aria-expanded', 'false');
                    switcherContainer.classList.remove('active');
                }
            }
        });

        // Close the dropdown with the Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                if (toggleButton.getAttribute('aria-expanded') === 'true') {
                    languageList.setAttribute('hidden', '');
                    toggleButton.setAttribute('aria-expanded', 'false');
                    switcherContainer.classList.remove('active');
                    toggleButton.focus(); // Return focus to the button
                }
            }
        });
    }
    
    /**
     * Setup code copy buttons
     */
    function setupCodeCopyButtons() {
        document.querySelectorAll('pre').forEach(pre => {
            const button = document.createElement('button');
            button.className = 'copy-code-button';
            button.innerHTML = 'Copy';
            pre.appendChild(button);
            button.addEventListener('click', () => {
                const code = pre.querySelector('code');
                if (code) {
                    navigator.clipboard.writeText(code.innerText).then(() => {
                        button.innerHTML = 'Copied!';
                        setTimeout(() => { button.innerHTML = 'Copy'; }, 2000);
                    });
                }
            });
        });
    }
    
    // =========================================================================
    // LAZY LOADING FOR IMAGES (New Function)
    // =========================================================================

    // =========================================================================
    // 11. INFINITE SCROLL
    // =========================================================================
    function setupInfiniteScroll() {
        console.log('GP Theme: setupInfiniteScroll called.');
        const postsContainer = document.querySelector('.site-main'); // Standard GeneratePress container for posts
        if (!postsContainer) {
            console.error('GP Theme: postsContainer not found!');
            console.log('GP Theme: Posts container (.site-main) not found for infinite scroll.');
            return;
        } else {
            console.log('GP Theme: postsContainer:', postsContainer);
        }

        if (typeof gp_ajax === 'undefined' || !gp_ajax.ajax_url || !gp_ajax.load_more_posts_nonce) {
            console.error('GP Theme: gp_ajax object or required properties (ajax_url, load_more_posts_nonce) are not defined. Infinite scroll cannot proceed.');
            return;
        }
        console.log('GP Theme: AJAX URL:', gp_ajax.ajax_url);
        console.log('GP Theme: Load More Nonce:', gp_ajax.load_more_posts_nonce);

        let currentPage = 1; // Page 1 is already loaded
        let isLoading = false;
        let noMorePosts = false;

        const loaderElement = document.createElement('div');
        loaderElement.id = 'infinite-scroll-loader';
        loaderElement.className = 'infinite-scroll-loader'; // For styling
        // Initial unobtrusive style
        loaderElement.style.height = '1px'; // Must have some dimension to be observed
        loaderElement.style.opacity = '0';
        loaderElement.textContent = ''; // No initial text
        postsContainer.appendChild(loaderElement);

        if (!loaderElement) console.error('GP Theme: loaderElement not found after creation attempt!');
        else console.log('GP Theme: loaderElement:', loaderElement);

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                console.log('GP Theme: IntersectionObserver triggered. Entry isIntersecting:', entry.isIntersecting);
                console.log('GP Theme: Current page:', currentPage, 'Is loading:', isLoading, 'No more posts:', noMorePosts);
                if (entry.isIntersecting && !isLoading && !noMorePosts) {
                    isLoading = true;
                    // Make loader visible and indicate loading
                    loaderElement.textContent = 'Loading more posts...';
                    loaderElement.style.opacity = '1';
                    currentPage++;
                    console.log('GP Theme: Attempting to load page:', currentPage, 'Nonce:', gp_ajax.load_more_posts_nonce);
                    jQuery.ajax({
                        url: gp_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'load_more_posts',
                            page: currentPage,
                            nonce: gp_ajax.load_more_posts_nonce
                        },
                        success: function(response) {
                            console.log('GP Theme: AJAX success. Response:', response);
                            isLoading = false;

                            if (response.success && response.data.html && response.data.html.trim() !== '') {
                                console.log('GP Theme: Received HTML length:', response.data.html.length);
                                const tempDiv = document.createElement('div');
                                tempDiv.innerHTML = response.data.html;

                                Array.from(tempDiv.children).forEach(childNode => {
                                    postsContainer.insertBefore(childNode, loaderElement);
                                });
                                // Revert loader to unobtrusive state if more posts might be loaded
                                loaderElement.textContent = '';
                                loaderElement.style.opacity = '0';

                                if (typeof setupLazyLoading === 'function') {
                                    setupLazyLoading();
                                }

                            } else {
                                if (response.success && response.data && response.data.message) {
                                    console.log('GP Theme: Message from server:', response.data.message);
                                } else {
                                    console.warn('GP Theme: AJAX success but no HTML or message.');
                                }
                                noMorePosts = true;
                                console.log('GP Theme: No more posts. Observer will be disconnected.');
                                loaderElement.textContent = 'No more posts to load.';
                                loaderElement.style.opacity = '1'; // Keep "No more posts" message visible
                                observer.disconnect();
                                console.log('GP Theme: No more posts to load.'); // Duplicate log, but per instructions
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error('GP Theme: AJAX error:', errorThrown, jqXHR, textStatus);
                            isLoading = false;
                            loaderElement.textContent = 'Error loading posts. Please try refreshing the page.';
                            loaderElement.style.opacity = '1'; // Keep error message visible
                            // console.error('GP Theme: Error loading more posts:', textStatus, errorThrown); // This is redundant with the one above
                        }
                    });
                }
            });
        }, {
            rootMargin: '0px 0px 300px 0px',
            threshold: 0.01
        });

        if (document.getElementById('infinite-scroll-loader')) {
             observer.observe(loaderElement);
             console.log('GP Theme: Infinite scroll observer started.');
        } else {
            console.error("GP Theme: Loader element for infinite scroll was not found in DOM immediately after append. Observer not started.");
        }
    }

    // =========================================================================
    // LAZY LOADING FOR IMAGES (New Function)
    // =========================================================================
    function setupLazyLoading() {
        const lazyImages = document.querySelectorAll('img.lazy-load');

        if (!lazyImages.length) {
            console.log('GP Theme: No images to lazy load.');
            return;
        }

        let observer = new IntersectionObserver((entries, observerInstance) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    const src = img.dataset.src;

                    if (src) {
                        img.src = src;
                        img.removeAttribute('data-src'); // Clean up data-src
                    }

                    // Optional: Add a class to indicate the image has been loaded
                    // img.classList.add('loaded');

                    img.classList.remove('lazy-load');
                    observerInstance.unobserve(img); // Stop observing the image once loaded
                    console.log('GP Theme: Image loaded via IntersectionObserver:', src);
                }
            });
        }, {
            rootMargin: '0px 0px 50px 0px', // Start loading images when they are 50px from the viewport
            threshold: 0.01 // Even a tiny bit of the image visible should trigger loading
        });

        lazyImages.forEach(img => {
            observer.observe(img);
        });
        console.log(`GP Theme: Initialized IntersectionObserver for ${lazyImages.length} images.`);
    }

    // =========================================================================
    // 10. INITIALIZATION
    // =========================================================================
    
    // Initialize all functionality
    initDarkMode();
    setupFloatingButtons();
    setupSidebar();
    setupURLCopy();
    setupProgressBar();
    setupTOC();
    // setupTocAnimation();
    setupStarRating();
    setupReactionButtons();
    setupPostedDateToggles();
    setupLanguageToggle();
    setupCodeCopyButtons();
    setupLazyLoading();
    setupInfiniteScroll(); // Initialize Infinite Scroll
    
    console.log('GP Theme: All features initialized successfully');
});