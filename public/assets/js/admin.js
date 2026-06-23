/* ============================================================
   WeeSaaS Admin JS
   ============================================================ */
(function() {
'use strict';

/* ---- TOAST ---- */
window.toast = function(msg, type, duration) {
  type = type || 'info';
  duration = duration || 3000;
  var container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }
  var icons = {
    success: '<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg>',
    error: '<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"/></svg>',
    info: '<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"/></svg>',
  };
  var t = document.createElement('div');
  t.className = 'toast toast-' + type;
  t.innerHTML = (icons[type] || '') + '<span>' + msg + '</span>';
  container.appendChild(t);
  setTimeout(function() {
    t.style.opacity = '0';
    t.style.transform = 'translateX(20px)';
    t.style.transition = 'all .3s';
    setTimeout(function() { if (t.parentNode) t.parentNode.removeChild(t); }, 300);
  }, duration);
};

/* ---- AUTOSAVE ---- */
window.initAutosave = function(fieldSelector, saveUrl, extraData) {
  var debounceTimers = {};
  document.querySelectorAll(fieldSelector).forEach(function(el) {
    el.addEventListener('input', function() {
      var fieldKey = el.dataset.field || el.name;
      clearTimeout(debounceTimers[fieldKey]);
      showAutosave('saving');
      debounceTimers[fieldKey] = setTimeout(function() {
        var data = new FormData();
        data.append('field', fieldKey);
        data.append('value', el.value);
        data.append('csrf_token', window.CSRF_TOKEN || '');
        if (extraData) Object.keys(extraData).forEach(function(k) { data.append(k, extraData[k]); });
        fetch(saveUrl, { method: 'POST', body: data })
          .then(function(r) { return r.json(); })
          .then(function(d) { showAutosave(d.success ? 'saved' : 'error'); })
          .catch(function() { showAutosave('error'); });
      }, 1000);
    });
  });
};

function showAutosave(state) {
  var el = document.querySelector('.autosave-indicator');
  if (!el) return;
  el.classList.add('visible');
  if (state === 'saving') {
    el.innerHTML = '<svg viewBox="0 0 20 20" fill="currentColor"><circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="2" fill="none" class="spin-circle"/></svg> Sauvegarde...';
  } else if (state === 'saved') {
    el.innerHTML = '<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg> Sauvegardé ✓';
    el.style.color = '#16A34A';
    setTimeout(function() { el.classList.remove('visible'); el.style.color = ''; }, 2000);
  } else {
    el.innerHTML = '⚠ Erreur';
    el.style.color = '#DC2626';
    setTimeout(function() { el.classList.remove('visible'); el.style.color = ''; }, 3000);
  }
}

/* ---- TABS ---- */
function initTabs() {
  document.querySelectorAll('.tabs').forEach(function(tabGroup) {
    var btns = tabGroup.querySelectorAll('.tab-btn');
    var targetGroup = tabGroup.dataset.target || 'tab';
    btns.forEach(function(btn) {
      btn.addEventListener('click', function() {
        btns.forEach(function(b) { b.classList.remove('active'); });
        btn.classList.add('active');
        var target = btn.dataset.tab;
        document.querySelectorAll('[data-panel]').forEach(function(p) {
          p.classList.toggle('active', p.dataset.panel === target);
        });
      });
    });
  });
}

/* ---- MODAL ---- */
window.openModal = function(id) {
  var m = document.getElementById(id);
  if (m) m.classList.add('open');
};
window.closeModal = function(id) {
  var m = document.getElementById(id);
  if (m) m.classList.remove('open');
};
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('open');
  }
  if (e.target.closest('[data-open-modal]')) {
    openModal(e.target.closest('[data-open-modal]').dataset.openModal);
  }
  if (e.target.closest('[data-close-modal]')) {
    closeModal(e.target.closest('[data-close-modal]').dataset.closeModal);
  }
});

/* ---- TOGGLE ---- */
function initToggles() {
  document.querySelectorAll('.toggle input').forEach(function(input) {
    input.addEventListener('change', function() {
      var target = input.dataset.target;
      if (target) {
        document.querySelectorAll('[data-show-if="' + input.id + '"]').forEach(function(el) {
          el.style.display = input.checked ? '' : 'none';
        });
      }
    });
    // Init state
    var targetEl = document.querySelector('[data-show-if="' + input.id + '"]');
    if (targetEl) targetEl.style.display = input.checked ? '' : 'none';
  });
}

/* ---- SIDEBAR MOBILE ---- */
function initSidebarMobile() {
  var hamburger = document.querySelector('.hamburger');
  var sidebar = document.querySelector('.admin-sidebar');
  var overlay = document.querySelector('.sidebar-overlay');
  if (!hamburger || !sidebar) return;
  function openSidebar() {
    sidebar.classList.add('open');
    if (overlay) overlay.classList.add('show');
  }
  function closeSidebar() {
    sidebar.classList.remove('open');
    if (overlay) overlay.classList.remove('show');
  }
  hamburger.addEventListener('click', openSidebar);
  if (overlay) overlay.addEventListener('click', closeSidebar);
}

/* ---- CONFIRM DELETE ---- */
document.addEventListener('click', function(e) {
  var btn = e.target.closest('[data-confirm]');
  if (!btn) return;
  var msg = btn.dataset.confirm || 'Confirmer cette action ?';
  if (!confirm(msg)) e.preventDefault();
});

/* ---- GENERATION PROGRESS POLLING ---- */
window.startPolling = function(jobId, onComplete) {
  var baseUrl = window.BASE_URL || '/';
  var interval = setInterval(function() {
    fetch(baseUrl + 'jobs/status.php?job_id=' + jobId)
      .then(function(r) { 
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json(); 
      })
      .then(function(data) {
        updateGenProgress(data);
        if (data.status === 'completed') {
          clearInterval(interval);
          if (onComplete) onComplete(data);
        } else if (data.status === 'failed') {
          clearInterval(interval);
          toast('Erreur: ' + (data.error || 'Génération échouée'), 'error', 5000);
          console.error('Job failed:', data.error);
        }
      })
      .catch(function(err) {
        console.warn('Polling error:', err);
      });
  }, 2000);
};

function updateGenProgress(data) {
  var pct = data.progress_pct || 0;
  var fill = document.querySelector('.gen-bar-fill');
  if (fill) fill.style.width = pct + '%';
  var stepEl = document.querySelector('.gen-step');
  if (stepEl) stepEl.textContent = data.step_label || '';
  var stepItems = document.querySelectorAll('.gen-step-item');
  stepItems.forEach(function(item, i) {
    item.classList.remove('done', 'active');
    if (i < (data.step_current - 1)) item.classList.add('done');
    else if (i === (data.step_current - 1)) item.classList.add('active');
  });
}

/* ---- BULK ACTIONS ---- */
window.bulkAction = function(statut) {
  var checked = document.querySelectorAll('.table-check:checked');
  if (!checked.length) { toast('Sélectionnez au moins une commande', 'info'); return; }
  if (!confirm('Changer ' + checked.length + ' commande(s) vers le statut « ' + statut + ' » ?')) return;
  var ids = [];
  checked.forEach(function(cb) { if (cb.value) ids.push(cb.value); });
  var data = new FormData();
  data.append('action', statut);
  data.append('ids', ids.join(','));
  data.append('csrf_token', window.CSRF_TOKEN || '');
  fetch(window.ADMIN_URL + 'ajax/bulk_orders.php', { method: 'POST', body: data })
    .then(function(r) { return r.json(); })
    .then(function(d) {
      if (d.success) {
        toast(d.updated + ' commande(s) mise(s) à jour', 'success');
        setTimeout(function() { window.location.reload(); }, 800);
      } else {
        toast(d.error || 'Erreur', 'error');
      }
    })
    .catch(function() { toast('Erreur réseau', 'error'); });
};

function initBulkActions() {
  var selectAll = document.getElementById('select-all');
  if (!selectAll) return;
  selectAll.addEventListener('change', function() {
    document.querySelectorAll('.table-check').forEach(function(cb) { cb.checked = selectAll.checked; });
    updateBulkCount();
  });
  document.querySelectorAll('.table-check').forEach(function(cb) {
    cb.addEventListener('change', updateBulkCount);
  });
}

function updateBulkCount() {
  var checked = document.querySelectorAll('.table-check:checked').length;
  var bulkBar = document.querySelector('.bulk-action-bar');
  if (bulkBar) {
    bulkBar.style.display = checked > 0 ? 'flex' : 'none';
    var countEl = bulkBar.querySelector('.bulk-count');
    if (countEl) countEl.textContent = checked;
  }
}

/* ---- CHARTS (Chart.js auto-hébergé) ---- */
window.initChart = function(canvasId, type, labels, datasets, options) {
  var canvas = document.getElementById(canvasId);
  if (!canvas || typeof Chart === 'undefined') return;
  var defaults = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: '#111111',
        titleColor: '#ffffff',
        bodyColor: 'rgba(255,255,255,.75)',
        borderColor: 'rgba(255,255,255,.1)',
        borderWidth: 1,
        padding: 10,
        cornerRadius: 8,
      }
    },
    scales: type !== 'doughnut' ? {
      x: { grid: { display: false }, ticks: { color: '#999', font: { size: 11 } } },
      y: { grid: { color: '#f0f0f0', drawBorder: false }, ticks: { color: '#999', font: { size: 11 } } },
    } : undefined,
  };
  return new Chart(canvas, {
    type: type,
    data: { labels: labels, datasets: datasets },
    options: Object.assign(defaults, options || {}),
  });
};

/* ---- INLINE EDITING (product sections) ---- */
window.initInlineEdit = function(productId) {
  document.querySelectorAll('[data-editable]').forEach(function(el) {
    el.setAttribute('contenteditable', 'true');
    el.addEventListener('blur', function() {
      debounceAutosaveField(el.dataset.editable, el.textContent.trim(), productId);
    });
  });
};

var autosaveTimer = null;
function debounceAutosaveField(field, value, productId) {
  clearTimeout(autosaveTimer);
  showAutosave('saving');
  autosaveTimer = setTimeout(function() {
    var data = new FormData();
    data.append('product_id', productId);
    data.append('field', field);
    data.append('value', value);
    data.append('csrf_token', window.CSRF_TOKEN || '');
    var baseUrl = window.BASE_URL || '/';
    fetch(baseUrl + 'weeadmin/ajax/update_field.php', { method: 'POST', body: data })
      .then(function(r) { return r.json(); })
      .then(function(d) { showAutosave(d.success ? 'saved' : 'error'); })
      .catch(function() { showAutosave('error'); });
  }, 1000);
}

/* ---- DRAG & DROP sections ---- */
window.initDragSections = function(listId, saveUrl, productId) {
  var list = document.getElementById(listId);
  if (!list) return;
  var dragged = null;
  list.querySelectorAll('.editor-section-item').forEach(function(item) {
    item.setAttribute('draggable', 'true');
    item.addEventListener('dragstart', function() { dragged = item; item.classList.add('drag-ghost'); });
    item.addEventListener('dragend', function() { item.classList.remove('drag-ghost'); saveSectionOrder(list, saveUrl, productId); });
    item.addEventListener('dragover', function(e) {
      e.preventDefault();
      var rect = item.getBoundingClientRect();
      var mid = rect.top + rect.height / 2;
      list.insertBefore(dragged, e.clientY < mid ? item : item.nextSibling);
    });
  });
};

function saveSectionOrder(list, saveUrl, productId) {
  var order = [];
  list.querySelectorAll('.editor-section-item').forEach(function(item) { order.push(item.dataset.section); });
  var data = new FormData();
  data.append('product_id', productId);
  data.append('order', JSON.stringify(order));
  data.append('csrf_token', window.CSRF_TOKEN || '');
  fetch(saveUrl, { method: 'POST', body: data }).catch(function() {});
}

/* ---- NOTIFICATIONS COUNT ---- */
function loadNotifCount() {
  var badge = document.querySelector('.sidebar-badge');
  if (!badge) return;
  var baseUrl = window.BASE_URL || '/';
  fetch(baseUrl + 'weeadmin/ajax/get_notif_count.php')
    .then(function(r) { return r.json(); })
    .then(function(d) {
      if (d.count > 0) { badge.textContent = d.count; badge.style.display = 'flex'; }
      else { badge.style.display = 'none'; }
    }).catch(function() {});
}

/* ---- LIVE VISITORS ---- */
window.startLiveVisitors = function() {
  var baseUrl = window.BASE_URL || '/';
  function refresh() {
    fetch(baseUrl + 'weeadmin/ajax/get_live_visitors.php')
      .then(function(r) { return r.json(); })
      .then(function(d) {
        var el = document.getElementById('live-count-header');
        if (el) el.textContent = d.count || 0;
      }).catch(function() {});
  }
  refresh();
  setInterval(refresh, 30000);
};

/* ---- COLOR PICKER PREVIEW ---- */
function initColorPickers() {
  document.querySelectorAll('.color-swatch input[type="color"]').forEach(function(input) {
    input.addEventListener('input', function() {
      var swatch = input.closest('.color-swatch');
      if (swatch) swatch.style.background = input.value;
      var targetId = input.dataset.preview;
      if (targetId) {
        var target = document.getElementById(targetId);
        if (target) target.style.setProperty('--product-color', input.value);
      }
      var textInput = document.getElementById(input.dataset.textInput);
      if (textInput) textInput.value = input.value;
    });
  });
}

/* ---- INIT ---- */
document.addEventListener('DOMContentLoaded', function() {
  initTabs();
  initToggles();
  initSidebarMobile();
  initBulkActions();
  initColorPickers();
  loadNotifCount();
  setInterval(loadNotifCount, 60000);
});

})();
