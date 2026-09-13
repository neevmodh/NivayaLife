/**
 * Scroll-driven entrance effects for the marketing page.
 *
 * Elements marked [data-reveal] start translated down and transparent (see
 * app.css) and settle into place the first time they enter the viewport;
 * [data-count-to] numbers tick up on the same trigger. Both are opt-in per
 * element and both no-op entirely when the visitor has asked their OS for
 * reduced motion, so the page is fully readable either way.
 */

const prefersReducedMotion = () =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function countUp(el) {
    const target = Number(el.dataset.countTo);
    if (!Number.isFinite(target)) return;

    const suffix = el.dataset.countSuffix ?? '';
    const duration = 1100;
    const start = performance.now();

    const tick = (now) => {
        const progress = Math.min((now - start) / duration, 1);
        // Ease-out cubic: fast off the line, gentle landing.
        const eased = 1 - Math.pow(1 - progress, 3);
        el.textContent = Math.round(target * eased).toLocaleString() + suffix;
        if (progress < 1) requestAnimationFrame(tick);
    };

    requestAnimationFrame(tick);
}

function initNavHighlight() {
    const navLinks = document.querySelectorAll('nav a[href^="#"]');
    if (!navLinks.length || !('IntersectionObserver' in window)) return;

    const linksByHash = new Map();
    navLinks.forEach((link) => {
        const hash = link.getAttribute('href');
        if (!linksByHash.has(hash)) linksByHash.set(hash, []);
        linksByHash.get(hash).push(link);
    });

    const sections = [...linksByHash.keys()]
        .map((hash) => document.querySelector(hash))
        .filter(Boolean);
    if (!sections.length) return;

    const setActive = (hash) => {
        navLinks.forEach((link) => link.classList.toggle('nivayalife-nav-active', link.getAttribute('href') === hash));
    };

    const observer = new IntersectionObserver(
        (entries) => {
            const visible = entries.filter((e) => e.isIntersecting);
            if (visible.length > 0) setActive('#' + visible[0].target.id);
        },
        // A thin band near the top of the viewport — the section crossing it
        // is what the reader is actually looking at right now.
        { rootMargin: '-20% 0px -70% 0px' },
    );

    sections.forEach((section) => observer.observe(section));
}

export default function initScrollEffects() {
    const revealables = document.querySelectorAll('[data-reveal]');
    const counters = document.querySelectorAll('[data-count-to]');

    initNavHighlight();

    // Without IntersectionObserver (or with reduced motion) everything simply
    // renders in its final state rather than staying invisible.
    if (prefersReducedMotion() || !('IntersectionObserver' in window)) {
        revealables.forEach((el) => el.classList.add('is-revealed'));
        counters.forEach((el) => {
            el.textContent =
                Number(el.dataset.countTo).toLocaleString() + (el.dataset.countSuffix ?? '');
        });
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-revealed');
                entry.target.querySelectorAll?.('[data-count-to]').forEach(countUp);
                if (entry.target.hasAttribute('data-count-to')) countUp(entry.target);
                observer.unobserve(entry.target);
            });
        },
        // Fire slightly before the element is fully on screen so the motion
        // reads as "already settling" rather than starting late.
        { threshold: 0.15, rootMargin: '0px 0px -40px 0px' },
    );

    revealables.forEach((el) => observer.observe(el));
    counters.forEach((el) => observer.observe(el));
}
