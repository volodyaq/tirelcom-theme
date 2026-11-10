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

/* Подсказки в поиске */
(function () {
  var input = document.getElementById('q');
  if (!input || !window.TEK || !TEK.suggest) return;
  var form = input.form;
  form.style.position = 'relative';
  input.setAttribute('autocomplete', 'off');
  var box = document.createElement('div');
  box.className = 'tek-sg'; box.hidden = true; box.setAttribute('role', 'listbox');
  form.appendChild(box);
  var cache = {}, timer, ctl, items = [], cur = -1, lastQ = '';
  function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
  function hide() { box.hidden = true; cur = -1; }
  function render(d, q) {
    var h = '';
    (d.cats || []).forEach(function (c) { h += '<a class="tek-sg-cat" role="option" href="' + esc(c.u) + '"><span>Раздел: <b>' + esc(c.n) + '</b></span><em>' + c.c + '</em></a>'; });
    (d.products || []).forEach(function (p) {
      h += '<a class="tek-sg-p" role="option" href="' + esc(p.u) + '"><span class="tek-sg-i">' + (p.i ? '<img src="' + esc(p.i) + '" alt="" loading="lazy">' : '') + '</span><span class="tek-sg-t"><span class="tek-sg-n">' + esc(p.n) + '</span><span class="tek-sg-m">' + (p.c ? 'код ' + esc(p.c) + ' · ' : '') + (p.s ? '<b class="ok">в наличии</b>' : '<b class="pre">предзаказ</b>') + '</span></span><span class="tek-sg-pr">' + esc(p.p) + '</span></a>';
    });
    if (!h) h = '<div class="tek-sg-none">Ничего не нашли по «' + esc(q) + '». Попробуйте часть названия или позвоните: <b>+373 533 51338</b></div>';
    else h += '<a class="tek-sg-all" href="' + esc(TEK.searchUrl) + '?post_type=product&s=' + encodeURIComponent(q) + '">Показать все результаты (' + d.total + ')</a>';
    box.innerHTML = h; box.hidden = false; items = [].slice.call(box.querySelectorAll('a')); cur = -1;
  }
  function fetchQ(q) {
    if (cache[q]) { render(cache[q], q); return; }
    if (ctl && ctl.abort) ctl.abort();
    ctl = window.AbortController ? new AbortController() : null;
    fetch(TEK.suggest + (TEK.suggest.indexOf('?') > -1 ? '&' : '?') + 'q=' + encodeURIComponent(q), ctl ? { signal: ctl.signal } : {})
      .then(function (r) { return r.json(); })
      .then(function (d) { cache[q] = d; if (input.value.trim() === q) render(d, q); })
      .catch(function () {});
  }
  input.addEventListener('input', function () {
    var q = input.value.trim(); lastQ = q; clearTimeout(timer);
    if (q.length < 2) { hide(); return; }
    timer = setTimeout(function () { fetchQ(q); }, 220);
  });
  input.addEventListener('focus', function () { if (items.length && input.value.trim().length >= 2) box.hidden = false; });
  input.addEventListener('keydown', function (e) {
    if (box.hidden || !items.length) return;
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
      e.preventDefault();
      cur = (cur + (e.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
      items.forEach(function (a, i) { a.classList.toggle('on', i === cur); });
      items[cur].scrollIntoView({ block: 'nearest' });
    } else if (e.key === 'Enter' && cur > -1) { e.preventDefault(); location.href = items[cur].href; }
    else if (e.key === 'Escape') hide();
  });
  document.addEventListener('click', function (e) { if (!form.contains(e.target)) hide(); });
})();

/* Фильтры: автоприменение, «показать ещё», на телефоне свёрнуты */
(function () {
  var box = document.querySelector('.tek-fbox:not(.tek-catbox)');
  var cb = document.querySelector('.tek-catbox');
  if (window.innerWidth <= 700) {
    if (cb) cb.removeAttribute('open');
    if (box && !/(\?|&)(f%5B|f\[|stock=|min_price=|max_price=)/.test(location.search)) box.removeAttribute('open');
  }
  if (!box) return;
  var form = document.getElementById('tek-ff'), t;
  if (form) form.addEventListener('change', function (e) {
    if (e.target.type !== 'checkbox') return;
    clearTimeout(t); t = setTimeout(function () { form.submit(); }, 450);
  });
  document.querySelectorAll('.tek-fmore').forEach(function (b) {
    b.addEventListener('click', function () {
      var l = b.parentNode; l.classList.toggle('tek-open');
      b.textContent = l.classList.contains('tek-open') ? b.getAttribute('data-less') : b.getAttribute('data-more');
    });
  });
})();

/* Кнопка «Каталог» на телефоне */
(function () {
  var b = document.getElementById('tek-catbtn'), d = document.getElementById('tek-drawer');
  if (!b || !d) return;
  b.addEventListener('click', function () { var open = d.hidden; d.hidden = !open; b.setAttribute('aria-expanded', open ? 'true' : 'false'); });
})();
