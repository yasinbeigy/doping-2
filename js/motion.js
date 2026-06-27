const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

if (!prefersReducedMotion) {
  const selectors = [
    '.hero-content',
    '.page-banner .container',
    '.section-header',
    '.feature-card',
    '.course-card',
    '.split-cta',
    '.testimonial-card',
    '.faq-item',
    '.exam-main',
    '.question-nav',
    '.dashboard-card',
    '.stat-card',
    '.teacher-hero',
    '.enrollment-card',
    '.teacher-info-card',
    '.auth-copy',
    '.auth-card',
    '.terms-card'
  ].join(',');

  const elements = Array.from(document.querySelectorAll(selectors));

  const prepare = (el) => {
    el.style.opacity = '0';
    el.style.willChange = 'opacity';
  };

  const cleanup = (el) => {
    el.style.opacity = '1';
    el.style.willChange = '';
  };

  const fallbackAnimate = (el, delay = 0) => {
    prepare(el);
    const animation = el.animate(
      [{ opacity: 0 }, { opacity: 1 }],
      {
        duration: 420,
        delay: delay * 1000,
        easing: 'ease-out',
        fill: 'forwards'
      }
    );
    animation.onfinish = () => cleanup(el);
  };

  const revealWithObserver = (animateFn) => {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting || entry.target.dataset.revealed === 'true') return;

        const el = entry.target;
        const siblings = Array.from(el.parentElement ? el.parentElement.children : []);
        const index = Math.max(0, siblings.indexOf(el));
        const delay = Math.min(index * 0.035, 0.14);

        el.dataset.revealed = 'true';
        requestAnimationFrame(() => animateFn(el, delay));
        observer.unobserve(el);
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -4% 0px' });

    elements.forEach((el) => observer.observe(el));
  };

  const startFallback = () => revealWithObserver(fallbackAnimate);

  import('https://cdn.jsdelivr.net/npm/framer-motion@12.40.0/+esm')
    .then(({ animate }) => {
      if (typeof animate !== 'function') {
        startFallback();
        return;
      }

      document.documentElement.dataset.motionEngine = 'framer-motion';
      revealWithObserver((el, delay) => {
        prepare(el);
        const controls = animate(
          el,
          { opacity: [0, 1] },
          { duration: 0.42, delay, ease: 'easeOut' }
        );

        if (controls && typeof controls.then === 'function') {
          controls.then(() => cleanup(el));
        } else {
          window.setTimeout(() => cleanup(el), (0.42 + delay) * 1000 + 60);
        }
      });
    })
    .catch(startFallback);
}

/* ─── دوپینگ شیمی — Enhanced Interactions ─── */

(function() {
  /* Scroll progress bar */
  const bar = document.createElement('div');
  bar.style.cssText = `position:fixed;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#2563EB,#10B981);transform-origin:0 50%;transform:scaleX(0);z-index:10000;pointer-events:none;transition:transform 0.1s linear;`;
  document.body.appendChild(bar);

  window.addEventListener('scroll', () => {
    const p = document.documentElement.scrollHeight - window.innerHeight;
    bar.style.transform = `scaleX(${p > 0 ? window.scrollY / p : 0})`;
  }, { passive: true });

  /* Scroll to top button */
  const topBtn = document.createElement('button');
  topBtn.innerHTML = `<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>`;
  topBtn.setAttribute('aria-label', 'بازگشت به بالا');
  topBtn.style.cssText = `position:fixed;bottom:32px;left:32px;width:48px;height:48px;border-radius:16px;background:#fff;color:#2563EB;border:1px solid #E2E8F0;box-shadow:0 8px 24px rgba(15,23,42,0.12);cursor:pointer;z-index:9999;display:flex;align-items:center;justify-content:center;opacity:0;transform:translateY(16px) scale(0.9);transition:opacity 0.3s ease,transform 0.3s ease,box-shadow 0.2s ease;pointer-events:none;`;
  document.body.appendChild(topBtn);

  topBtn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

  window.addEventListener('scroll', () => {
    const show = window.scrollY > 400;
    topBtn.style.opacity = show ? '1' : '0';
    topBtn.style.transform = show ? 'translateY(0) scale(1)' : 'translateY(16px) scale(0.9)';
    topBtn.style.pointerEvents = show ? 'auto' : 'none';
  }, { passive: true });

  /* Header shrink on scroll */
  const header = document.querySelector('.site-header');
  if (header) {
    window.addEventListener('scroll', () => {
      header.classList.toggle('header-scrolled', window.scrollY > 60);
    }, { passive: true });
  }

  /* Counter animation for hero stats */
  const heroStats = document.querySelectorAll('.hero-stat strong');
  if (heroStats.length) {
    const counterObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        counterObserver.unobserve(entry.target);
        heroStats.forEach(stat => {
          const raw = parseInt(stat.textContent.replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[+\-]/g, '').trim(), 10);
          if (isNaN(raw) || raw === 0) return;
          const suffix = stat.textContent.includes('+') ? '+' : '';
          const dur = 1800;
          const start = performance.now();
          function update(now) {
            const p = Math.min((now - start) / dur, 1);
            const v = Math.round((1 - Math.pow(1 - p, 3)) * raw);
            stat.textContent = String(v).replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]) + suffix;
            if (p < 1) requestAnimationFrame(update);
          }
          requestAnimationFrame(update);
        });
      });
    }, { threshold: 0.3 });
    counterObserver.observe(heroStats[0].closest('.hero-stats') || heroStats[0]);
  }
})();

console.log('🚀 دوپینگ شیمی — Motion Engine Active');