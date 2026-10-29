/**
 * Lenis smooth scrolling
 * Requires the Lenis CDN bundle (global `Lenis`) to be loaded first.
 */
(function () {
    if (typeof Lenis === 'undefined') return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    var lenis = new Lenis({
        duration: 1.1,
        easing: function (t) { return Math.min(1, 1.001 - Math.pow(2, -10 * t)); },
        smoothWheel: true,
        // Let wheel events through inside scrollable containers (modals, maps,
        // dropdown panels) so their own overflow scrolling still works.
        prevent: function (node) {
            return !!node.closest(
                '.overflow-y-auto, .overflow-auto, .overflow-y-scroll, ' +
                '.modal-overlay, .modal-content, [id$="Modal"], .leaflet-container'
            );
        }
    });

    function raf(time) {
        lenis.raf(time);
        requestAnimationFrame(raf);
    }
    requestAnimationFrame(raf);

    // Smooth-scroll anchor links through Lenis
    document.addEventListener('click', function (e) {
        var link = e.target.closest('a[href^="#"]');
        if (!link) return;
        var href = link.getAttribute('href');
        if (href === '#') return;
        var target = document.querySelector(href);
        if (!target) return;
        e.preventDefault();
        lenis.scrollTo(target, { offset: -80 });
    });

    window.lenis = lenis;
})();
