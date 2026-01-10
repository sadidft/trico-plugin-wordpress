/**
 * Trico Theme Animations
 * Lightweight animation handler using AOS
 */

(function() {
    'use strict';

    const TricoAnimations = {
        
        init: function() {
            this.initAOS();
            this.initScrollEffects();
            this.initHoverEffects();
        },

        initAOS: function() {
            if (typeof AOS === 'undefined') return;
            
            AOS.init({
                duration: 800,
                easing: 'ease-out-cubic',
                once: true,
                offset: 50,
                delay: 0,
                anchorPlacement: 'top-bottom'
            });
        },

        initScrollEffects: function() {
            const header = document.querySelector('.trico-header');
            if (!header) return;

            let lastScroll = 0;
            
            window.addEventListener('scroll', function() {
                const currentScroll = window.pageYOffset;
                
                if (currentScroll > 100) {
                    header.classList.add('trico-header-scrolled');
                } else {
                    header.classList.remove('trico-header-scrolled');
                }
                
                lastScroll = currentScroll;
            }, { passive: true });
        },

        initHoverEffects: function() {
            const cards = document.querySelectorAll('.trico-card, .trico-bento-item');
            
            cards.forEach(function(card) {
                card.addEventListener('mouseenter', function(e) {
                    this.style.transform = 'translateY(-8px)';
                });
                
                card.addEventListener('mouseleave', function(e) {
                    this.style.transform = 'translateY(0)';
                });
            });
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            TricoAnimations.init();
        });
    } else {
        TricoAnimations.init();
    }

})();