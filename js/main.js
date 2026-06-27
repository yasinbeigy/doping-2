document.addEventListener('DOMContentLoaded', function() {
  const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
  const mainNav = document.querySelector('.main-nav');

  if (mobileMenuBtn && mainNav) {
    const menuBackdrop = document.createElement('div');
    menuBackdrop.className = 'site-menu-backdrop';
    menuBackdrop.setAttribute('aria-hidden', 'true');
    document.body.appendChild(menuBackdrop);

    const closeSiteMenu = function() {
      mainNav.classList.remove('is-open');
      menuBackdrop.classList.remove('is-open');
      document.body.classList.remove('site-menu-open');
      mobileMenuBtn.setAttribute('aria-expanded', 'false');
    };

    const openSiteMenu = function() {
      mainNav.classList.add('is-open');
      menuBackdrop.classList.add('is-open');
      document.body.classList.add('site-menu-open');
      mobileMenuBtn.setAttribute('aria-expanded', 'true');
    };

    mobileMenuBtn.addEventListener('click', function() {
      mainNav.classList.contains('is-open') ? closeSiteMenu() : openSiteMenu();
    });

    menuBackdrop.addEventListener('click', closeSiteMenu);

    mainNav.querySelectorAll('a').forEach(function(link) {
      link.addEventListener('click', function() {
        if (window.matchMedia('(max-width: 768px)').matches) closeSiteMenu();
      });
    });

    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape') closeSiteMenu();
    });
  }

  const closeAllSelects = function(except) {
    document.querySelectorAll('.custom-select.is-open').forEach(function(select) {
      if (select !== except) {
        select.classList.remove('is-open');
        const trigger = select.querySelector('.custom-select-trigger');
        if (trigger) trigger.setAttribute('aria-expanded', 'false');
      }
    });
  };

  const applyCourseFilters = function() {
    const grid = document.querySelector('.courses-grid');
    if (!grid || !document.querySelector('.filters-modern')) return;

    const cards = Array.from(grid.querySelectorAll('.course-card'));
    const searchInput = document.querySelector('.filters-modern .search-input');
    const search = searchInput ? searchInput.value.trim().toLowerCase() : '';
    const selected = {};

    document.querySelectorAll('.custom-select').forEach(function(select) {
      const key = select.dataset.filter;
      const chosen = select.querySelector('[aria-selected="true"]');
      selected[key] = chosen ? chosen.dataset.value : 'all';
    });

    const gradeMap = {
      '10': ['دهم'],
      '11': ['یازدهم'],
      '12': ['دوازدهم'],
      konkur: ['کنکور', 'جمع‌بندی']
    };

    const levelMap = {
      basic: ['پایه', 'دهم', 'شروع'],
      practice: ['تمرین', 'یازدهم', 'دوازدهم', 'محاسبات'],
      review: ['جمع‌بندی', 'کنکور', 'تست']
    };

    cards.forEach(function(card) {
      const text = card.textContent.toLowerCase();
      const gradeOk = !selected.grade || selected.grade === 'all' || (gradeMap[selected.grade] || []).some(function(word) { return text.includes(word); });
      const levelOk = !selected.level || selected.level === 'all' || (levelMap[selected.level] || []).some(function(word) { return text.includes(word); });
      const searchOk = !search || text.includes(search);
      card.classList.toggle('is-hidden-by-filter', !(gradeOk && levelOk && searchOk));
    });

    if (selected.sort && selected.sort !== 'default') {
      const visibleCards = cards.slice();
      const persianToEnglish = function(str) {
        return str.replace(/[۰-۹]/g, function(d) { return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d); }).replace(/[٠-٩]/g, function(d) { return '٠١٢٣٤٥٦٧٨٩'.indexOf(d); });
      };
      visibleCards.sort(function(a, b) {
        if (selected.sort === 'new') {
          return (b.textContent.includes('جدید') ? 1 : 0) - (a.textContent.includes('جدید') ? 1 : 0);
        }
        if (selected.sort === 'popular') {
          return (b.textContent.includes('پرفروش') || b.textContent.includes('کنکور') ? 1 : 0) - (a.textContent.includes('پرفروش') || a.textContent.includes('کنکور') ? 1 : 0);
        }
        if (selected.sort === 'budget') {
          const priceA = parseInt(persianToEnglish((a.querySelector('.course-price') || {}).textContent || '0').replace(/[^0-9]/g, ''), 10) || 0;
          const priceB = parseInt(persianToEnglish((b.querySelector('.course-price') || {}).textContent || '0').replace(/[^0-9]/g, ''), 10) || 0;
          return priceA - priceB;
        }
        return 0;
      });
      visibleCards.forEach(function(card) { grid.appendChild(card); });
    }
  };

  document.querySelectorAll('.custom-select').forEach(function(select) {
    const trigger = select.querySelector('.custom-select-trigger');
    const label = select.querySelector('.custom-select-label');
    const options = select.querySelectorAll('[role="option"]');

    if (!trigger) return;

    trigger.addEventListener('click', function(e) {
      e.stopPropagation();
      const willOpen = !select.classList.contains('is-open');
      closeAllSelects(select);
      select.classList.toggle('is-open', willOpen);
      trigger.setAttribute('aria-expanded', String(willOpen));
    });

    options.forEach(function(option) {
      option.addEventListener('click', function() {
        options.forEach(function(item) { item.setAttribute('aria-selected', 'false'); });
        option.setAttribute('aria-selected', 'true');
        if (label) label.textContent = option.textContent;
        select.classList.remove('is-open');
        trigger.setAttribute('aria-expanded', 'false');
        applyCourseFilters();
      });
    });
  });


  // Auth password visibility toggles
  document.querySelectorAll('[data-password-toggle]').forEach(function(toggle) {
    toggle.addEventListener('click', function() {
      const wrap = toggle.closest('.password-input-wrap');
      const input = wrap ? wrap.querySelector('input') : null;
      if (!input) return;

      const shouldShow = input.type === 'password';
      input.type = shouldShow ? 'text' : 'password';
      const iconUse = toggle.querySelector('use');
      if (iconUse) {
        iconUse.setAttribute('href', shouldShow ? 'images/icons.svg#eye-off' : 'images/icons.svg#eye');
      }
      toggle.classList.toggle('is-visible', shouldShow);
      toggle.setAttribute('aria-label', shouldShow ? 'مخفی کردن رمز عبور' : 'نمایش رمز عبور');
    });
  });

  // Auth custom grade dropdown
  const closeAuthSelects = function(except) {
    document.querySelectorAll('.auth-grade-dropdown.is-open').forEach(function(select) {
      if (select !== except) {
        select.classList.remove('is-open');
        const trigger = select.querySelector('.auth-grade-trigger');
        if (trigger) trigger.setAttribute('aria-expanded', 'false');
      }
    });
  };

  document.querySelectorAll('[data-auth-select]').forEach(function(select) {
    const trigger = select.querySelector('.auth-grade-trigger');
    const label = select.querySelector('.auth-grade-label');
    const valueInput = select.querySelector('[data-auth-select-value]');
    const options = select.querySelectorAll('[role="option"]');

    if (!trigger || !label || !valueInput) return;

    trigger.addEventListener('click', function(e) {
      e.stopPropagation();
      const willOpen = !select.classList.contains('is-open');
      closeAuthSelects(select);
      select.classList.toggle('is-open', willOpen);
      trigger.setAttribute('aria-expanded', String(willOpen));
    });

    options.forEach(function(option) {
      option.addEventListener('click', function(e) {
        e.stopPropagation();
        options.forEach(function(item) { item.setAttribute('aria-selected', 'false'); });
        option.setAttribute('aria-selected', 'true');
        label.textContent = option.textContent;
        valueInput.value = option.dataset.value || '';
        select.classList.remove('is-open');
        select.classList.remove('is-invalid');
        trigger.setAttribute('aria-expanded', 'false');
      });
    });
  });


  // Forgot password multi-step flow (SMS service integration point is in the first step)
  document.querySelectorAll('[data-forgot-flow]').forEach(function(flow) {
    const panels = flow.querySelectorAll('[data-forgot-step]');
    const dots = document.querySelectorAll('[data-step-dot]');
    const phoneOutput = flow.querySelector('[data-forgot-phone]');
    const success = flow.querySelector('[data-forgot-success]');

    const setStep = function(step) {
      panels.forEach(function(panel) {
        const active = panel.dataset.forgotStep === step;
        panel.hidden = !active;
        panel.classList.toggle('is-active', active);
      });
      if (success) success.hidden = true;
      dots.forEach(function(dot) {
        dot.classList.toggle('is-active', dot.dataset.stepDot === step);
        const order = ['phone', 'code', 'password'];
        dot.classList.toggle('is-done', order.indexOf(dot.dataset.stepDot) < order.indexOf(step));
      });
    };

    const phoneForm = flow.querySelector('[data-forgot-step="phone"]');
    const codeForm = flow.querySelector('[data-forgot-step="code"]');
    const passwordForm = flow.querySelector('[data-forgot-step="password"]');

    if (phoneForm) {
      phoneForm.addEventListener('submit', function(e) {
        e.preventDefault();
        if (!phoneForm.checkValidity()) {
          phoneForm.reportValidity();
          return;
        }
        const phone = phoneForm.querySelector('[name="phone"]').value.trim();
        if (phoneOutput) phoneOutput.textContent = phone;

        // TODO: Connect your SMS verification service here and send the code to `phone`.
        setStep('code');
      });
    }

    if (codeForm) {
      codeForm.addEventListener('submit', function(e) {
        e.preventDefault();
        if (!codeForm.checkValidity()) {
          codeForm.reportValidity();
          return;
        }
        // TODO: Verify entered SMS code with your SMS service/backend here.
        setStep('password');
      });
    }

    flow.querySelectorAll('[data-forgot-back]').forEach(function(button) {
      button.addEventListener('click', function() {
        setStep(button.dataset.forgotBack || 'phone');
      });
    });

    if (passwordForm) {
      passwordForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const password = passwordForm.querySelector('[name="password"]');
        const confirm = passwordForm.querySelector('[data-confirm-password]');
        const passValue = password ? password.value : '';
        const rulesOk = passValue.length >= 7 && /[A-Z]/.test(passValue);
        if (password) {
          password.setCustomValidity(rulesOk ? '' : 'رمز عبور باید بیشتر از ۶ کاراکتر باشد و حداقل یک حرف بزرگ انگلیسی داشته باشد.');
        }
        if (confirm && password) {
          confirm.setCustomValidity(confirm.value === password.value ? '' : 'تکرار رمز عبور با رمز عبور یکسان نیست.');
        }
        if (!passwordForm.checkValidity()) {
          passwordForm.reportValidity();
          return;
        }

        panels.forEach(function(panel) {
          panel.hidden = true;
          panel.classList.remove('is-active');
        });
        dots.forEach(function(dot) {
          dot.classList.remove('is-active');
          dot.classList.add('is-done');
        });
        if (success) success.hidden = false;
      });
    }
  });

  // Auth validation: password rules, confirm password, grade dropdown
  document.querySelectorAll('.auth-form').forEach(function(form) {
    const password = form.querySelector('[name="password"]');
    const confirm = form.querySelector('[data-confirm-password]');
    const gradeDropdown = form.querySelector('[data-auth-select]');
    const gradeValue = form.querySelector('[data-auth-select-value]');

    const validatePassword = function() {
      if (!password || !password.hasAttribute('data-password-rules')) return true;
      const value = password.value || '';
      const ok = value.length >= 7 && /[A-Z]/.test(value);
      password.setCustomValidity(ok || value.length === 0 ? '' : 'رمز عبور باید بیشتر از ۶ کاراکتر باشد و حداقل یک حرف بزرگ انگلیسی داشته باشد.');
      return ok;
    };

    const validateConfirm = function() {
      if (!password || !confirm) return true;
      const ok = !confirm.value || confirm.value === password.value;
      confirm.setCustomValidity(ok ? '' : 'تکرار رمز عبور با رمز عبور یکسان نیست.');
      return ok;
    };

    if (password) {
      password.addEventListener('input', function() {
        validatePassword();
        validateConfirm();
      });
    }
    if (confirm) {
      confirm.addEventListener('input', validateConfirm);
    }

    form.addEventListener('submit', function(e) {
      const passwordOk = validatePassword();
      const confirmOk = validateConfirm();
      let gradeOk = true;

      if (gradeDropdown && gradeValue) {
        gradeOk = Boolean(gradeValue.value);
        gradeDropdown.classList.toggle('is-invalid', !gradeOk);
      }

      if (!passwordOk || !confirmOk || !gradeOk || !form.checkValidity()) {
        e.preventDefault();
        if (!gradeOk) {
          const trigger = gradeDropdown.querySelector('.auth-grade-trigger');
          if (trigger) trigger.focus();
        } else {
          form.reportValidity();
        }
      }
    });
  });

  document.addEventListener('click', function() { closeAuthSelects(); });
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeAuthSelects();
  });

  const courseSearch = document.querySelector('.filters-modern .search-input');
  if (courseSearch) {
    courseSearch.addEventListener('input', applyCourseFilters);
  }

  document.addEventListener('click', function() { closeAllSelects(); });
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeAllSelects();
  });

  document.querySelectorAll('.faq-question').forEach(function(question) {
    const answer = question.nextElementSibling;
    const icon = question.querySelector('span');

    if (answer) {
      answer.hidden = true;
    }

    question.addEventListener('click', function() {
      const willOpen = answer ? answer.hidden : false;
      document.querySelectorAll('.faq-answer').forEach(function(item) {
        if (item !== answer) item.hidden = true;
      });
      document.querySelectorAll('.faq-question span').forEach(function(item) {
        if (item !== icon) item.textContent = '+';
      });

      if (answer) answer.hidden = !willOpen;
      if (icon) icon.textContent = willOpen ? '−' : '+';
    });
  });

  document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
    anchor.addEventListener('click', function(e) {
      const href = this.getAttribute('href');
      if (href && href.length > 1) {
        const target = document.querySelector(href);
        if (target) {
          e.preventDefault();
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }
    });
  });



});

/* ─── دوپینگ شیمی — Enhanced Interactions ─── */

// Smooth entrance for page banners
(function() {
  const banner = document.querySelector('.page-banner');
  if (banner) {
    banner.style.opacity = '0';
    banner.style.transform = 'translateY(-10px)';
    requestAnimationFrame(() => {
      banner.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
      banner.style.opacity = '1';
      banner.style.transform = 'translateY(0)';
    });
  }
})();

// Course price hover animation
document.querySelectorAll('.course-price').forEach(price => {
  price.addEventListener('mouseenter', function() {
    this.style.transform = 'scale(1.05)';
  });
  price.addEventListener('mouseleave', function() {
    this.style.transform = 'scale(1)';
  });
});

// Teacher avatar hover pulse
document.querySelectorAll('.teacher-avatar, .large-avatar').forEach(avatar => {
  avatar.addEventListener('mouseenter', function() {
    this.style.transform = 'scale(1.15)';
    this.style.boxShadow = '0 8px 24px rgba(37,99,235,0.2)';
  });
  avatar.addEventListener('mouseleave', function() {
    this.style.transform = '';
    this.style.boxShadow = '';
  });
});

// Footer year auto-update
document.querySelectorAll('.footer-bottom p:first-child').forEach(el => {
  const match = el.textContent.match(/(\d{4})/);
  if (match) {
    el.textContent = el.textContent.replace(match[0], '۱۴۰۵');
  }
});

// Course card filter animation
const filterObserver = new MutationObserver(() => {
  document.querySelectorAll('.course-card.is-hidden-by-filter').forEach(card => {
    card.style.opacity = '0';
    card.style.transform = 'scale(0.95)';
    card.style.position = 'absolute';
    card.style.pointerEvents = 'none';
  });
  document.querySelectorAll('.course-card:not(.is-hidden-by-filter)').forEach(card => {
    card.style.opacity = '';
    card.style.transform = '';
    card.style.position = '';
    card.style.pointerEvents = '';
  });
});

const filterContainer = document.querySelector('.courses-grid');
if (filterContainer) {
  filterObserver.observe(filterContainer, { childList: true, subtree: true, attributes: true, attributeFilter: ['class'] });
}

console.log('🚀 دوپینگ شیمی — Interactions Ready');
