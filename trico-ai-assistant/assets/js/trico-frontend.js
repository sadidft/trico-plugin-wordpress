/**
 * Trico Frontend Script
 * Handles frontend functionality for generated pages
 */

(function() {
    'use strict';
    
    var TricoFrontend = {
        
        init: function() {
            this.initAOS();
            this.initSmoothScroll();
            this.initLazyLoad();
        },
        
        /**
         * Initialize AOS animation library
         */
        initAOS: function() {
            if (typeof AOS === 'undefined') {
                return;
            }
            
            AOS.init({
                duration: 800,
                easing: 'ease-out-cubic',
                once: true,
                offset: 50,
                delay: 0
            });
        },
        
        /**
         * Smooth scroll for anchor links
         */
        initSmoothScroll: function() {
            document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
                anchor.addEventListener('click', function(e) {
                    var targetId = this.getAttribute('href');
                    
                    if (targetId === '#') return;
                    
                    var target = document.querySelector(targetId);
                    
                    if (target) {
                        e.preventDefault();
                        
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                        
                        // Update URL hash
                        history.pushState(null, null, targetId);
                    }
                });
            });
        },
        
        /**
         * Lazy load images
         */
        initLazyLoad: function() {
            if ('loading' in HTMLImageElement.prototype) {
                // Native lazy loading supported
                document.querySelectorAll('img[data-src]').forEach(function(img) {
                    img.src = img.dataset.src;
                    img.loading = 'lazy';
                });
            } else {
                // Fallback with Intersection Observer
                var lazyImages = document.querySelectorAll('img[data-src]');
                
                if ('IntersectionObserver' in window) {
                    var observer = new IntersectionObserver(function(entries) {
                        entries.forEach(function(entry) {
                            if (entry.isIntersecting) {
                                var img = entry.target;
                                img.src = img.dataset.src;
                                observer.unobserve(img);
                            }
                        });
                    });
                    
                    lazyImages.forEach(function(img) {
                        observer.observe(img);
                    });
                } else {
                    // Very old browser fallback
                    lazyImages.forEach(function(img) {
                        img.src = img.dataset.src;
                    });
                }
            }
        }
    };
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            TricoFrontend.init();
        });
    } else {
        TricoFrontend.init();
    }
    
})();