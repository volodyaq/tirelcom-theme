(function () {
  var rate = (window.TEK && TEK.mdlRate) || 0;
  var btns = document.querySelectorAll('.tek-cur-btn');
  if (!btns.length || !rate) return;
  function setCur(cur) {
    document.querySelectorAll('[data-prc]').forEach(function (el) {
      var v = parseFloat(el.getAttribute('data-prc'));
      if (isNaN(v)) return;
      if (cur === 'MDL') v = v / rate;
      el.textContent = v.toFixed(2).replace('.', ',');
      var u = el.parentNode.querySelector('[data-unit]');
      if (u) u.textContent = cur === 'MDL' ? 'MDL' : 'руб. ПМР';
    });
    btns.forEach(function (b) { b.setAttribute('aria-pressed', b.getAttribute('data-cur') === cur ? 'true' : 'false'); });
    try { localStorage.setItem('tek_cur', cur); } catch (e) {}
  }
  btns.forEach(function (b) { b.addEventListener('click', function () { setCur(b.getAttribute('data-cur')); }); });
  var saved = null; try { saved = localStorage.getItem('tek_cur'); } catch (e) {}
  if (saved === 'MDL') setCur('MDL');
})();

/* Количество и миниатюры на карточке товара */
(function () {
  var q = document.getElementById('qty');
  if (q) {
    var minus = document.querySelector('.tek-qty-minus'), plus = document.querySelector('.tek-qty-plus');
    function val() { var v = parseInt(q.value, 10); return isNaN(v) || v < 1 ? 1 : v; }
    minus && minus.addEventListener('click', function () { q.value = Math.max(1, val() - 1); });
    plus && plus.addEventListener('click', function () { q.value = val() + 1; });
  }
  var main = document.getElementById('tek-main-img');
  document.querySelectorAll('.tek-thumb').forEach(function (b) {
    b.addEventListener('click', function () {
      if (main) main.src = b.getAttribute('data-full');
      document.querySelectorAll('.tek-thumb').forEach(function (x) { x.style.border = '1px solid #D5DEEE'; });
      b.style.border = '2px solid #1D4ED8';
    });
  });
})();

/* Корзина: количество с автообновлением; чекаут: способ получения */
(function () {
  var form = document.querySelector('.woocommerce-cart-form');
  if (form) {
    var t;
    form.querySelectorAll('article').forEach(function (a) {
      var inp = a.querySelector('.tek-cq');
      function send() { clearTimeout(t); t = setTimeout(function () { var b = form.querySelector('[name=update_cart]'); if (b) b.click(); }, 500); }
      a.querySelector('.tek-cq-minus').addEventListener('click', function () { inp.value = Math.max(0, (parseInt(inp.value, 10) || 1) - 1); send(); });
      a.querySelector('.tek-cq-plus').addEventListener('click', function () { inp.value = (parseInt(inp.value, 10) || 0) + 1; send(); });
      inp.addEventListener('change', send);
    });
  }
  var radios = document.querySelectorAll('input[name=tek_delivery]');
  if (radios.length) {
    var addr = document.getElementById('tek-addr');
    function upd() {
      radios.forEach(function (r) {
        var l = r.closest('label');
        l.style.borderColor = r.checked ? '#1D4ED8' : '#D5DEEE';
        l.style.background = r.checked ? '#EEF3FE' : '#FFFFFF';
        if (r.checked && addr) addr.style.display = r.value === 'pickup' ? 'none' : 'flex';
      });
    }
    radios.forEach(function (r) { r.addEventListener('change', upd); });
    upd();
  }
})();


/* Вкладки карточки товара */
(function () {
  var tabs = document.querySelectorAll('.tek-tab');
  if (!tabs.length) return;
  function show(name) {
    tabs.forEach(function (t) {
      var on = t.getAttribute('data-tab') === name;
      t.classList.toggle('is-active', on);
      t.setAttribute('aria-selected', on ? 'true' : 'false');
      var p = document.getElementById('tek-p-' + t.getAttribute('data-tab'));
      if (p) p.hidden = !on;
    });
  }
  tabs.forEach(function (t) { t.addEventListener('click', function () { show(t.getAttribute('data-tab')); }); });
})();

/* Сравнение товаров */
(function () {
  var KEY = 'tek_cmp', MAX = 4;
  function load() { try { var a = JSON.parse(localStorage.getItem(KEY) || '[]'); return Array.isArray(a) ? a.map(String).slice(0, MAX) : []; } catch (e) { return []; } }
  function save(a) { try { localStorage.setItem(KEY, JSON.stringify(a)); } catch (e) {} }
  var base = (window.TEK && TEK.cmpUrl) || '/sravnenie/';
  var bar = document.getElementById('tek-cmp-bar');
  var table = document.querySelector('table.tek-cmp');
  var onCmpPage = /\/sravnenie\/?$/.test(location.pathname);
  if (onCmpPage) {
    if (table) save(table.getAttribute('data-ids').split(',').filter(Boolean));
    else if (/ids=/.test(location.search)) save([]);
    else { var s = load(); if (s.length) { location.replace(base + '?ids=' + s.join(',')); return; } }
  }
  var msgT;
  function msg(t) { var m = document.getElementById('tek-cmp-msg'); if (!m) return; m.textContent = t; clearTimeout(msgT); msgT = setTimeout(function () { m.textContent = ''; }, 3000); }
  function render() {
    var a = load();
    document.querySelectorAll('.tek-cmp-btn').forEach(function (b) {
      var on = a.indexOf(b.getAttribute('data-cmp')) > -1;
      b.setAttribute('aria-pressed', on ? 'true' : 'false');
      var tx = b.querySelector('.tek-cmp-tx'); if (tx) tx.textContent = on ? 'В сравнении' : 'Добавить к сравнению';
      b.title = on ? 'Убрать из сравнения' : 'Добавить к сравнению';
    });
    if (!bar) return;
    bar.hidden = !a.length || onCmpPage;
    document.getElementById('tek-cmp-n').textContent = a.length;
    document.getElementById('tek-cmp-go').href = a.length ? base + '?ids=' + a.join(',') : base;
  }
  document.addEventListener('click', function (e) {
    var b = e.target.closest && e.target.closest('.tek-cmp-btn');
    if (!b) return;
    e.preventDefault();
    var id = b.getAttribute('data-cmp'), a = load(), i = a.indexOf(id);
    if (i > -1) a.splice(i, 1);
    else if (a.length >= MAX) { msg('Можно сравнить до ' + MAX + ' товаров'); return; }
    else a.push(id);
    save(a); render();
  });
  var clr = document.getElementById('tek-cmp-clear');
  if (clr) clr.addEventListener('click', function () { save([]); render(); });
  var diff = document.getElementById('tek-cmp-diff');
  if (diff) diff.addEventListener('change', function () { document.querySelector('.tek-cmp-wrap').classList.toggle('tek-cmp-only-diff', diff.checked); });
  render();
})();
