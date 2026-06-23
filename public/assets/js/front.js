/* ============================================================
   WeeSaaS Front JS — Vanilla, zéro dépendance
   ============================================================ */
(function() {
'use strict';

/* ---- TIMER ---- */
function initTimer() {
  var el = document.getElementById('timer-wrap');
  if (!el) return;
  var endKey = 'timer_end_' + el.dataset.slug;
  var hours = parseInt(el.dataset.hours || 24, 10);
  var stored = localStorage.getItem(endKey);
  var endTime;
  if (stored && parseInt(stored) > Date.now()) {
    endTime = parseInt(stored);
  } else {
    endTime = Date.now() + hours * 3600000;
    localStorage.setItem(endKey, endTime);
  }
  function tick() {
    var diff = endTime - Date.now();
    if (diff <= 0) { el.style.display = 'none'; return; }
    var h = Math.floor(diff / 3600000);
    var m = Math.floor((diff % 3600000) / 60000);
    var s = Math.floor((diff % 60000) / 1000);
    var pad = function(n) { return n < 10 ? '0' + n : '' + n; };
    var hEl = el.querySelector('.timer-h');
    var mEl = el.querySelector('.timer-m');
    var sEl = el.querySelector('.timer-s');
    if (hEl) hEl.textContent = pad(h);
    if (mEl) mEl.textContent = pad(m);
    if (sEl) sEl.textContent = pad(s);
  }
  tick();
  setInterval(tick, 1000);
}

/* ---- ATTRIBUTS héro (les attr du formulaire de commande ont leur propre handler inline) ---- */
function initAttrs() {
  // Cibler uniquement les boutons dans .attr-grid (section héro)
  // Les boutons dans .attr-pills (formulaire commande) sont gérés par order_form.php
  var btns = document.querySelectorAll('.attr-grid .attr-btn');
  if (!btns.length) return;
  btns.forEach(function(btn) {
    btn.addEventListener('click', function() {
      var group = btn.dataset.group;
      document.querySelectorAll('.attr-grid .attr-btn[data-group="' + group + '"]').forEach(function(b) { b.classList.remove('selected'); });
      btn.classList.add('selected');
      var selEl = document.querySelector('.attr-selected[data-group="' + group + '"]');
      if (selEl) selEl.textContent = btn.dataset.val;
      var hiddenInput = document.getElementById('attr-hidden-' + group);
      if (hiddenInput) hiddenInput.value = btn.dataset.val;
      updateTotalPrice();
    });
  });
}

/* ---- QUANTITÉ (héro uniquement — boutons avec data-delta) ---- */
function initQty() {
  var qtyVal = document.getElementById('qty-val');
  if (!qtyVal) return;
  var min = parseInt(qtyVal.dataset.min || 1);
  var max = parseInt(qtyVal.dataset.max || 99);
  // N'écouter QUE les boutons héro (ceux avec data-delta), pas ceux du formulaire
  document.querySelectorAll('.qty-btn[data-delta]').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var cur = parseInt(qtyVal.textContent);
      var delta = btn.dataset.delta === '-1' ? -1 : 1;
      var nv = Math.min(Math.max(cur + delta, min), max);
      qtyVal.textContent = nv;
      var hiddenQty = document.getElementById('qty-input');
      if (hiddenQty) hiddenQty.value = nv;
      updateTotalPrice();
    });
  });
}

/* ---- PRIX DYNAMIQUE ---- */
function updateTotalPrice() {
  var basePrice = parseFloat(document.getElementById('prix-base') ? document.getElementById('prix-base').value : 0);
  var qty = parseInt((document.getElementById('qty-val') ? document.getElementById('qty-val').textContent : 1) || 1);
  var totalEl = document.querySelector('.price-total-dynamic');
  if (totalEl && basePrice) {
    totalEl.textContent = formatPrice(basePrice * qty);
  }
  var summaryPrice = document.querySelector('.order-summary-price');
  if (summaryPrice && basePrice) {
    summaryPrice.textContent = formatPriceWithSymbol(basePrice * qty);
  }
}

function formatPrice(n) {
  return new Intl.NumberFormat('fr-FR').format(n);
}
function formatPriceWithSymbol(n) {
  var sym = document.getElementById('devise-symbole') ? document.getElementById('devise-symbole').value : 'MAD';
  var pos = document.getElementById('devise-position') ? document.getElementById('devise-position').value : 'apres';
  var formatted = new Intl.NumberFormat('fr-FR').format(n);
  return pos === 'avant' ? sym + ' ' + formatted : formatted + ' ' + sym;
}

/* ---- GALERIE + LIGHTBOX CARROUSEL ---- */
function initGallery() {
  // Miniatures du hero : changent l'image principale.
  var main = document.getElementById('hero-main-img');
  var thumbs = document.querySelectorAll('.hero-thumb');
  if (thumbs.length && main) {
    thumbs.forEach(function(th) {
      th.addEventListener('click', function() {
        var newSrc = th.dataset.full || th.src;
        if (main.getAttribute('src') === newSrc) return;
        thumbs.forEach(function(t) { t.classList.remove('active'); });
        th.classList.add('active');
        // Fondu doux : on précharge la nouvelle image, puis on bascule.
        main.style.opacity = '0';
        var pre = new Image();
        var swap = function() {
          main.src = newSrc;
          main.setAttribute('data-src', newSrc); // garde le lightbox synchronisé
          main.style.opacity = '1';
        };
        pre.onload = swap;
        pre.onerror = swap;
        pre.src = newSrc;
      });
    });
  }

  var lb = document.getElementById('lightbox');
  if (!lb) return;

  var lbImg     = lb.querySelector('.lightbox-img');
  var elPrev    = lb.querySelector('.lightbox-prev');
  var elNext    = lb.querySelector('.lightbox-next');
  var elCur     = lb.querySelector('.lb-cur');
  var elTotal   = lb.querySelector('.lb-total');
  var elCaption = lb.querySelector('.lightbox-caption');
  var elThumbs  = lb.querySelector('.lightbox-thumbs');
  var elCounter = lb.querySelector('.lightbox-counter');
  var closeBtn  = lb.querySelector('.lightbox-close');

  var set = [];   // [{src, alt, caption}]
  var idx = 0;

  lbImg.addEventListener('load', function(){ lbImg.classList.add('loaded'); });

  function render() {
    var item = set[idx];
    if (!item) return;
    lbImg.classList.remove('loaded');
    lbImg.src = item.src;
    lbImg.alt = item.alt || '';
    var multi = set.length > 1;
    if (elCur)     elCur.textContent = idx + 1;
    if (elTotal)   elTotal.textContent = set.length;
    if (elCaption) elCaption.textContent = item.caption || '';
    if (elCounter) elCounter.style.display = multi ? '' : 'none';
    if (elPrev)    elPrev.style.display = multi ? '' : 'none';
    if (elNext)    elNext.style.display = multi ? '' : 'none';
    if (elThumbs) elThumbs.querySelectorAll('.lb-thumb').forEach(function(t, i) {
      t.classList.toggle('active', i === idx);
      if (i === idx) t.scrollIntoView({block:'nearest', inline:'center', behavior:'smooth'});
    });
  }

  function buildThumbs() {
    if (!elThumbs) return;
    elThumbs.innerHTML = '';
    if (set.length < 2) return;
    set.forEach(function(item, i) {
      var t = document.createElement('img');
      t.src = item.src;
      t.className = 'lb-thumb';
      t.loading = 'lazy';
      t.addEventListener('click', function(){ go(i); });
      elThumbs.appendChild(t);
    });
  }

  function go(i) { idx = (i + set.length) % set.length; render(); }

  function open(newSet, start) {
    set = newSet; idx = start < 0 ? 0 : start;
    buildThumbs();
    render();
    lb.classList.add('open');
    lb.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }
  function close() {
    lb.classList.remove('open');
    lb.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  function itemFrom(el) {
    return { src: el.dataset.src || el.src, alt: el.getAttribute('alt') || '', caption: el.dataset.caption || '' };
  }

  document.querySelectorAll('[data-lightbox]').forEach(function(el) {
    function trigger(e) {
      e.preventDefault();
      var group = el.getAttribute('data-gallery');
      if (group) {
        var nodes = Array.prototype.slice.call(document.querySelectorAll('[data-gallery="' + group + '"]'));
        open(nodes.map(itemFrom), nodes.indexOf(el));
      } else {
        open([itemFrom(el)], 0);
      }
    }
    el.addEventListener('click', trigger);
    el.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' || e.key === ' ') trigger(e);
    });
  });

  if (elPrev)   elPrev.addEventListener('click', function(e){ e.stopPropagation(); go(idx - 1); });
  if (elNext)   elNext.addEventListener('click', function(e){ e.stopPropagation(); go(idx + 1); });
  if (closeBtn) closeBtn.addEventListener('click', close);
  lb.addEventListener('click', function(e){ if (e.target === lb) close(); });

  document.addEventListener('keydown', function(e) {
    if (!lb.classList.contains('open')) return;
    if (e.key === 'Escape') close();
    else if (e.key === 'ArrowLeft') go(idx - 1);
    else if (e.key === 'ArrowRight') go(idx + 1);
  });

  // Swipe mobile.
  var sx = null;
  lb.addEventListener('touchstart', function(e){ sx = e.touches[0].clientX; }, {passive:true});
  lb.addEventListener('touchend', function(e){
    if (sx === null) return;
    var dx = e.changedTouches[0].clientX - sx;
    if (Math.abs(dx) > 50) go(idx + (dx < 0 ? 1 : -1));
    sx = null;
  }, {passive:true});
}

/* ---- FAQ ---- */
function initFaq() {
  document.querySelectorAll('.faq-question').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var item = btn.closest('.faq-item');
      var wasOpen = item.classList.contains('open');
      document.querySelectorAll('.faq-item').forEach(function(i) { i.classList.remove('open'); });
      if (!wasOpen) item.classList.add('open');
    });
  });
}

/* ---- CITY DROPDOWN ---- */
function initCityDropdown() {
  var wrap = document.querySelector('.city-select-wrap');
  if (!wrap) return;
  var input = wrap.querySelector('.city-input');
  var dropdown = wrap.querySelector('.city-dropdown');
  var searchInput = wrap.querySelector('.city-search-input');
  var hiddenInput = document.getElementById('ville-hidden');
  var paysProduit = document.getElementById('pays-produit') ? document.getElementById('pays-produit').value : 'maroc';

  // Charger les villes en AJAX
  var baseUrl = document.getElementById('base-url') ? document.getElementById('base-url').value : '/';
  fetch(baseUrl + 'villes?pays=' + paysProduit)
    .then(function(r) { return r.json(); })
    .then(function(villes) {
      renderVilles(villes, '');
    })
    .catch(function() {
      var optionsEl = wrap.querySelector('.city-options');
      if (optionsEl) optionsEl.innerHTML = '<div class="city-option">Saisir votre ville</div>';
    });

  function renderVilles(villes, filter) {
    var opts = wrap.querySelector('.city-options');
    if (!opts) return;
    opts.innerHTML = '';
    var filtered = filter ? villes.filter(function(v) { return v.toLowerCase().indexOf(filter.toLowerCase()) !== -1; }) : villes;
    filtered.forEach(function(v) {
      var opt = document.createElement('div');
      opt.className = 'city-option';
      opt.textContent = v;
      opt.addEventListener('click', function() {
        if (input) input.value = v;
        if (hiddenInput) hiddenInput.value = v;
        dropdown.classList.remove('open');
        input.classList.remove('error');
      });
      opts.appendChild(opt);
    });
  }

  if (input) {
    input.addEventListener('click', function() { dropdown.classList.toggle('open'); });
    input.addEventListener('focus', function() { dropdown.classList.add('open'); });
  }
  if (searchInput) {
    searchInput.addEventListener('input', function() {
      fetch(baseUrl + 'villes?pays=' + paysProduit)
        .then(function(r) { return r.json(); })
        .then(function(villes) { renderVilles(villes, searchInput.value); });
    });
  }
  document.addEventListener('click', function(e) {
    if (wrap && !wrap.contains(e.target)) dropdown.classList.remove('open');
  });
}

/* ---- FORMULAIRE COMMANDE AJAX ---- */
function initOrderForm() {
  var form = document.getElementById('order-form');
  if (!form) return;

  // Tracking: premier keystroke formulaire
  var tracked = false;
  form.querySelectorAll('input,textarea,select').forEach(function(el) {
    el.addEventListener('focus', function() {
      if (!tracked) {
        tracked = true;
        trackEvent('initiate_checkout');
      }
    }, { once: true });
  });

  form.addEventListener('submit', function(e) {
    e.preventDefault();
    if (!validateForm(form)) return;

    var btn = form.querySelector('.btn-submit');
    btn.classList.add('loading');
    btn.disabled = true;

    var baseUrl = document.getElementById('base-url') ? document.getElementById('base-url').value : '/';
    var formData = new FormData(form);
    // Les attributs du formulaire sont déjà inclus via les hidden inputs attr-hid-{group}
    // gérés par le handler inline de order_form.php — pas besoin de les ajouter manuellement

    // Quantité — lire depuis le champ du formulaire en priorité (évite conflit avec héro)
    var orderQtyInput = document.getElementById('qty-order-input');
    var heroQtyVal = document.getElementById('qty-val');
    var qtyToSend = orderQtyInput ? orderQtyInput.value : (heroQtyVal ? heroQtyVal.textContent : '1');
    formData.set('quantite', parseInt(qtyToSend, 10) || 1);

    // Temps depuis première visite
    var firstVisit = parseInt(localStorage.getItem('first_visit_' + (document.getElementById('product-id') ? document.getElementById('product-id').value : '')) || Date.now());
    formData.append('temps_avant_commande', Math.floor((Date.now() - firstVisit) / 1000));

    // UTM depuis session
    var utm = ['utm_source','utm_medium','utm_campaign'];
    utm.forEach(function(k) {
      var v = sessionStorage.getItem(k);
      if (v) formData.append(k, v);
    });

    // Pixel Facebook — InitiateCheckout
    trackPixelEvent('InitiateCheckout');

    fetch(baseUrl + 'commande', {
      method: 'POST',
      body: formData,
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.success) {
        trackPixelEvent('Purchase', { value: data.total, currency: data.devise });
        trackEvent('purchase');
        window.location.href = baseUrl + 'merci?ref=' + encodeURIComponent(data.reference);
      } else {
        showFormError(data.error || 'Une erreur est survenue');
        btn.classList.remove('loading');
        btn.disabled = false;
      }
    })
    .catch(function() {
      showFormError('Erreur de connexion. Réessayez.');
      btn.classList.remove('loading');
      btn.disabled = false;
    });
  });
}

function validateForm(form) {
  var valid = true;
  form.querySelectorAll('[required]').forEach(function(el) {
    el.classList.remove('error');
    if (!el.value.trim()) {
      el.classList.add('error');
      valid = false;
    }
  });
  var tel = form.querySelector('.tel-input');
  if (tel && tel.value && !/^[0-9+\s\-]{6,15}$/.test(tel.value.replace(/\s/g, ''))) {
    tel.classList.add('error');
    valid = false;
  }
  if (!valid) {
    var firstErr = form.querySelector('.error');
    if (firstErr) firstErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
  return valid;
}

function showFormError(msg) {
  var el = document.getElementById('form-error');
  if (el) { el.textContent = msg; el.style.display = 'block'; }
  else alert(msg);
}

/* ---- SOCIAL PROOF ---- */
function initSocialProof() {
  var proofs = window.SOCIAL_PROOFS || [];
  if (!proofs.length) return;
  var el = document.getElementById('social-proof');
  if (!el) return;
  var i = 0;
  function showNext() {
    el.textContent = '';
    var span = document.createElement('div');
    span.innerHTML = proofs[i % proofs.length];
    el.innerHTML = proofs[i % proofs.length];
    el.classList.add('show');
    setTimeout(function() { el.classList.remove('show'); }, 4000);
    i++;
  }
  setTimeout(showNext, 8000);
  setInterval(showNext, 12000);
}

/* ---- SCROLL REVEAL (IntersectionObserver) ---- */
function initScrollReveal() {
  if (!window.IntersectionObserver) return;
  var els = document.querySelectorAll('.reveal');
  if (!els.length) return;
  var obs = new IntersectionObserver(function(entries) {
    entries.forEach(function(e) {
      if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); }
    });
  }, { threshold: 0.12 });
  els.forEach(function(el) { obs.observe(el); });
}

/* ---- STOCK BAR ANIMATION ---- */
function initStockBarAnim() {
  var fill = document.querySelector('.stock-bar-fill');
  if (!fill) return;
  var targetW = fill.style.width;
  if (!targetW) return;
  fill.style.width = '0';
  if (!window.IntersectionObserver) { fill.style.width = targetW; return; }
  var obs = new IntersectionObserver(function(entries) {
    if (!entries[0].isIntersecting) return;
    requestAnimationFrame(function() { fill.style.width = targetW; });
    obs.disconnect();
  }, { threshold: 0.5 });
  obs.observe(fill);
}

/* ---- COUNTER ANIMATION (stats) ---- */
function initCounters() {
  var els = document.querySelectorAll('.stat-val');
  if (!els.length || !window.IntersectionObserver) return;
  var obs = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (!entry.isIntersecting) return;
      var el = entry.target;
      obs.unobserve(el);
      var raw = el.textContent.trim();
      var numStr = raw.replace(/[^0-9.]/g, '');
      var num = parseFloat(numStr);
      if (!num || isNaN(num)) return;
      var suffix = raw.replace(/^[\d\s.,]+/, '');
      var prefix = raw.indexOf(numStr) > 0 ? raw.slice(0, raw.indexOf(numStr)) : '';
      var isDecimal = numStr.indexOf('.') !== -1;
      var decimals = isDecimal ? (numStr.split('.')[1] || '').length : 0;
      var duration = 1600;
      var startTime = null;
      function step(ts) {
        if (!startTime) startTime = ts;
        var pct = Math.min((ts - startTime) / duration, 1);
        var ease = 1 - Math.pow(1 - pct, 3);
        var cur = ease * num;
        var display = isDecimal ? cur.toFixed(decimals) : Math.floor(cur);
        var formatted = new Intl.NumberFormat('fr-FR').format(display);
        el.textContent = prefix + formatted + suffix;
        if (pct < 1) { requestAnimationFrame(step); }
        else {
          el.textContent = prefix + new Intl.NumberFormat('fr-FR').format(isDecimal ? num.toFixed(decimals) : num) + suffix;
          el.classList.add('pop');
          setTimeout(function() { el.classList.remove('pop'); }, 400);
        }
      }
      requestAnimationFrame(step);
    });
  }, { threshold: 0.5 });
  els.forEach(function(el) { obs.observe(el); });
}

/* ---- STAGGER CARDS (features, temoignages, garanties) ---- */
function initStaggerCards() {
  if (!window.IntersectionObserver) return;
  var isMobile = window.innerWidth <= 768;
  var groups = [
    { parent: '.features-grid',    children: '.feature-card'     },
    // Testimonials : stagger désactivé sur mobile (carousel actif)
    { parent: '.testimonials-grid', children: '.testimonial-card', skipMobile: true },
    { parent: '.guarantee-inner',  children: '.guarantee-item'   },
    { parent: '.faq-list',         children: '.faq-item'         },
  ];
  groups.forEach(function(g) {
    if (g.skipMobile && isMobile) return;
    var parent = document.querySelector(g.parent);
    if (!parent) return;
    var cards = parent.querySelectorAll(g.children);
    if (!cards.length) return;
    cards.forEach(function(c) {
      c.style.opacity = '0';
      c.style.transform = 'translateY(22px)';
      c.style.transition = 'opacity .5s ease, transform .5s ease';
    });
    var obs = new IntersectionObserver(function(entries) {
      if (!entries[0].isIntersecting) return;
      cards.forEach(function(c, i) {
        setTimeout(function() {
          c.style.opacity = '1';
          c.style.transform = 'translateY(0)';
        }, i * 75);
      });
      obs.disconnect();
    }, { threshold: 0.08 });
    obs.observe(parent);
  });
}

/* ---- SCROLL TRACKING ---- */
function initScrollTracking() {
  var formReached = false;
  var formEl = document.getElementById('order-section');
  if (!formEl) return;
  window.addEventListener('scroll', function() {
    if (formReached) return;
    var rect = formEl.getBoundingClientRect();
    if (rect.top < window.innerHeight * 0.8) {
      formReached = true;
      trackEvent('scroll_to_form');
    }
  }, { passive: true });
}

/* ---- HEADER STICKY ---- */
function initStickyHeader() {
  var header = document.querySelector('.site-header');
  if (!header) return;
  window.addEventListener('scroll', function() {
    header.classList.toggle('scrolled', window.scrollY > 10);
  }, { passive: true });
}

/* ---- UTM PERSISTENCE ---- */
function persistUtms() {
  var params = new URLSearchParams(window.location.search);
  ['utm_source','utm_medium','utm_campaign'].forEach(function(k) {
    var v = params.get(k);
    if (v) sessionStorage.setItem(k, v);
  });
  // First visit tracking
  var pid = document.getElementById('product-id');
  if (pid) {
    var key = 'first_visit_' + pid.value;
    if (!localStorage.getItem(key)) {
      localStorage.setItem(key, Date.now());
    }
  }
}

/* ---- PIXEL EVENTS ---- */
function trackPixelEvent(eventName, data) {
  try {
    if (typeof fbq !== 'undefined') fbq('track', eventName, data || {});
    if (typeof ttq !== 'undefined' && ttq.track) ttq.track(eventName.toLowerCase() === 'purchase' ? 'PlaceAnOrder' : eventName);
  } catch(e) {}
}

/* Identifiant de session visiteur, persistant (localStorage). */
function weeSid() {
  try {
    var s = localStorage.getItem('wee_sid');
    if (!s) { s = 's_' + Date.now().toString(36) + Math.random().toString(36).slice(2, 8); localStorage.setItem('wee_sid', s); }
    return s;
  } catch (e) { return 's_' + Math.random().toString(36).slice(2, 10); }
}

/* Base URL (slash final garanti), compatible sous-dossier. */
function weeBase() {
  var b = window.BASE_URL || (document.getElementById('base-url') ? document.getElementById('base-url').value : '/');
  return b.charAt(b.length - 1) === '/' ? b : b + '/';
}

function trackEvent(name) {
  var pid = document.getElementById('product-id') ? document.getElementById('product-id').value : '';
  navigator.sendBeacon && navigator.sendBeacon(weeBase() + 'track', JSON.stringify({ event: name, product_id: pid, session_id: weeSid() }));
}

/* ---- HEARTBEAT « présence live » (visiteurs actifs en temps réel) ---- */
(function () {
  function beat() {
    if (document.hidden || !navigator.sendBeacon) return;
    var pid = document.getElementById('product-id') ? document.getElementById('product-id').value : '';
    try {
      navigator.sendBeacon(weeBase() + 'track', JSON.stringify({ event: 'heartbeat', product_id: pid, session_id: weeSid() }));
    } catch (e) {}
  }
  beat();                                  // au chargement
  setInterval(beat, 45000);                // puis toutes les 45 s
  document.addEventListener('visibilitychange', function () { if (!document.hidden) beat(); });
})();

/* ---- TESTIMONIALS CAROUSEL (mobile) ---- */
function initTestimonialsCarousel() {
  var track = document.querySelector('.testimonials-track');
  if (!track) return;
  var cards = track.querySelectorAll('.testimonial-card');
  var dots = document.querySelectorAll('.carousel-dot');
  if (!cards.length || window.innerWidth > 768) return;
  var cur = 0;
  function goTo(idx) {
    cur = Math.max(0, Math.min(idx, cards.length - 1));
    track.style.transform = 'translateX(' + (cur * -296) + 'px)';
    dots.forEach(function(d, i) { d.classList.toggle('active', i === cur); });
  }
  dots.forEach(function(d, i) { d.addEventListener('click', function() { goTo(i); }); });
  var startX;
  track.addEventListener('touchstart', function(e) { startX = e.touches[0].clientX; }, { passive: true });
  track.addEventListener('touchend', function(e) {
    var dx = e.changedTouches[0].clientX - startX;
    if (Math.abs(dx) > 50) goTo(cur + (dx < 0 ? 1 : -1));
  });
}

/* ---- MOBILE STICKY CTA ---- */
function initMobileStickyScroll() {
  var stickyCta = document.querySelector('.mobile-sticky-cta');
  if (!stickyCta) return;
  var heroForm = document.getElementById('order-form');
  if (!heroForm) return;
  window.addEventListener('scroll', function() {
    var rect = heroForm.getBoundingClientRect();
    stickyCta.style.display = rect.top > window.innerHeight ? 'block' : 'none';
  }, { passive: true });
  stickyCta.querySelector('.mobile-sticky-btn') && stickyCta.querySelector('.mobile-sticky-btn').addEventListener('click', function() {
    var orderSection = document.getElementById('order-section');
    if (orderSection) orderSection.scrollIntoView({ behavior: 'smooth' });
  });
}

/* ---- ViewContent pixel ---- */
function trackViewContent() {
  var pid = document.getElementById('product-id');
  var pname = document.getElementById('product-name');
  var pprice = document.getElementById('product-price');
  var pcur = document.getElementById('product-currency');
  if (pid) {
    trackPixelEvent('ViewContent', {
      content_ids: [pid.value],
      content_name: pname ? pname.value : '',
      content_type: 'product',
      value: pprice ? parseFloat(pprice.value) : 0,
      currency: pcur ? pcur.value : 'MAD',
    });
  }
}

/* ---- HAMBURGER MENU MOBILE ---- */
function initHamburgerMenu() {
  var btn = document.getElementById('hamburger-btn');
  if (!btn) return;
  btn.addEventListener('click', function() { toggleMobileNav(true); });
}

window.toggleMobileNav = function(open) {
  var nav = document.getElementById('mobile-nav');
  if (!nav) return;
  if (open) {
    nav.classList.add('open');
    document.body.style.overflow = 'hidden';
  } else {
    nav.classList.remove('open');
    document.body.style.overflow = '';
  }
};

/* ---- SIZE GUIDE TOGGLE ---- */
function initSizeGuide() {
  var toggle = document.getElementById('size-guide-toggle');
  var panel  = document.getElementById('size-guide-panel');
  if (!toggle || !panel) return;
  toggle.addEventListener('click', function() {
    var isOpen = panel.style.display !== 'none';
    panel.style.display = isOpen ? 'none' : 'block';
    toggle.textContent = isOpen
      ? '📏 ' + toggle.dataset.labelOpen || toggle.textContent
      : '✕ Fermer';
  });
}

/* ---- MODAUX ---- */
window.openModal = function(id) {
  var el = document.getElementById(id);
  if (el) { el.classList.add('open'); document.body.style.overflow = 'hidden'; }
};
window.closeModal = function(id) {
  var el = document.getElementById(id);
  if (el) { el.classList.remove('open'); document.body.style.overflow = ''; }
};
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('modal-overlay')) closeModal(e.target.id);
  var opener = e.target.closest('[data-open-modal]');
  if (opener) openModal(opener.dataset.openModal);
  var closer = e.target.closest('[data-close-modal]');
  if (closer) closeModal(closer.dataset.closeModal);
});

/* ---- INIT ---- */
document.addEventListener('DOMContentLoaded', function() {
  persistUtms();
  initTimer();
  initAttrs();
  initQty();
  initGallery();
  initFaq();
  initCityDropdown();
  initOrderForm();
  initSocialProof();
  initScrollTracking();
  initStickyHeader();
  initTestimonialsCarousel();
  initMobileStickyScroll();
  trackViewContent();
  initScrollReveal();
  initStockBarAnim();
  initCounters();
  initStaggerCards();
  initHamburgerMenu();
  initSizeGuide();
});

})();
