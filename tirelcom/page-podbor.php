<?php if (!defined('ABSPATH')) exit; get_header(); $sent = isset($_GET['rqt']) && $_GET['rqt'] === 'podbor' && isset($_GET['rq']); ?>
<main style="max-width:1200px;margin:0 auto;padding:28px 24px 72px;display:flex;flex-direction:column;gap:24px">
<div style="display:flex;flex-direction:column;gap:12px">
<nav style="font-size:14px;color:#44506A"><a class="hv-link" href="<?php echo esc_url(home_url('/')); ?>">Главная</a> / Подбор детали</nav>
<h1 style="margin:0;font-size:38px;line-height:1.15;font-weight:800;letter-spacing:-0.02em">Подберите деталь</h1>
<p style="margin:0;font-size:17px;line-height:1.6;color:#33405A;max-width:760px">Введите маркировку или название: сначала поищем на сайте и покажем, что есть в наличии. Если не нашлось, оставьте заявку: проверим наличие, предложим аналог или привезём под заказ. Ответим в рабочее время: пн–сб 10–19, вс 10–14.</p>
</div>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:24px;align-items:start">
<div style="display:flex;flex-direction:column;gap:16px;min-width:0">
<section style="background:#FFFFFF;border:1px solid #D5DEEE;border-radius:16px;padding:22px;display:flex;flex-direction:column;gap:12px">
<label for="pd-q" style="font-size:14px;font-weight:600">Что ищете: маркировка или название</label>
<div style="display:flex;gap:8px">
<input id="pd-q" type="search" autocomplete="off" placeholder="Например: LM317, 2N2222, резистор 10 кОм" style="flex:1;min-width:0;height:50px;border:1px solid #C5D1E6;border-radius:12px;padding:0 14px;font:inherit;font-size:16px">
<button type="button" id="pd-go" style="height:50px;padding:0 22px;border:0;border-radius:12px;background:#1D4ED8;color:#fff;font:inherit;font-weight:700;cursor:pointer">Найти</button>
</div>
<div id="pd-res" aria-live="polite"></div>
</section>
<div id="pd-form-wrap">
<div id="pd-ask" hidden style="margin-bottom:12px;font-size:16px;line-height:1.5;color:#33405A"></div>
<?php echo tek_req_form('podbor'); ?>
</div>
</div>
<aside style="background:#0F2A5C;color:#FFFFFF;border-radius:16px;padding:24px;display:flex;flex-direction:column;gap:12px">
<span style="font-size:20px;font-weight:800">Быстрее по телефону</span>
<span style="font-size:15px;line-height:1.55;color:#C9D6F2">Позвоните, и подскажем сразу. Фото маркировки можно прислать в мессенджер.</span>
<a class="hv-link" href="tel:+37353351338" style="color:#FF8A1F;font-weight:800;font-size:20px">+373 533 51338</a>
<?php get_template_part('messengers'); ?>
</aside>
</div>
</main>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var inp = document.getElementById('pd-q'), go = document.getElementById('pd-go'), res = document.getElementById('pd-res');
  var wrap = document.getElementById('pd-form-wrap'), ask = document.getElementById('pd-ask');
  if (!inp || !window.TEK || !TEK.suggest) return;
  var sent = <?php echo $sent ? 'true' : 'false'; ?>;
  var ta = wrap.querySelector('textarea[name=rq_text]');
  if (!sent) wrap.hidden = true;
  function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
  function showForm(q, msg) {
    wrap.hidden = false;
    ask.hidden = !msg; ask.innerHTML = msg || '';
    if (ta && q && !ta.value) ta.value = q;
  }
  function render(d, q) {
    var h = '';
    var n = (d.products || []).length;
    if (n) {
      if (d.alt) h += '<div style="background:#FFF4E6;border:1px solid #F3D3A5;border-radius:10px;padding:10px 12px;font-size:14px;margin-bottom:10px">Показаны результаты для «' + esc(d.alt) + '»: мы исправили раскладку.</div>';
      if (d.relaxed) h += '<div style="background:#FFF1DE;border:1px solid #F3D3A5;border-radius:10px;padding:10px 12px;font-size:14px;margin-bottom:10px">Точного совпадения нет, показаны похожие по названию. Проверьте номинал в карточке.</div>';
      h += '<div style="font-size:15px;font-weight:700;margin:4px 0 8px">Нашли на сайте: ' + d.total + '</div><div style="display:flex;flex-direction:column;gap:8px">';
      d.products.forEach(function (p) {
        h += '<a href="' + esc(p.u) + '" style="display:flex;gap:12px;align-items:center;border:1px solid #D5DEEE;border-radius:12px;padding:10px;background:#fff">'
          + '<span style="flex:none;width:56px;height:56px;border-radius:8px;background:#F4F7FB;display:flex;align-items:center;justify-content:center;overflow:hidden">' + (p.i ? '<img src="' + esc(p.i) + '" alt="" loading="lazy" style="max-width:100%;max-height:100%">' : '') + '</span>'
          + '<span style="flex:1;min-width:0;display:flex;flex-direction:column;gap:2px"><span style="font-weight:700;font-size:15px;line-height:1.3;color:#0F1B33">' + esc(p.n) + '</span>'
          + '<span style="font-size:14px;color:' + (p.s ? '#14602B' : '#8A5A00') + '">' + (p.s ? 'В наличии' : 'Под заказ, срок уточним') + '</span></span>'
          + '<span style="flex:none;font-weight:800;font-size:15px;color:#0F1B33">' + esc(p.p) + ' <small style="font-weight:500">руб.</small></span></a>';
      });
      h += '</div>';
      if (d.total > n) h += '<a href="' + esc(TEK.searchUrl) + '?post_type=product&s=' + encodeURIComponent(q) + '" style="display:inline-block;margin-top:10px;font-weight:700;color:#1D4ED8;text-decoration:underline">Показать все результаты (' + d.total + ')</a>';
      h += '<div style="margin-top:14px;padding-top:12px;border-top:1px solid #E6ECF6;font-size:15px">Это не то, что нужно? <button type="button" id="pd-open" style="border:0;background:none;padding:0;font:inherit;font-weight:700;color:#C2410C;text-decoration:underline;cursor:pointer">Оставить заявку</button></div>';
      res.innerHTML = h;
      var b = document.getElementById('pd-open');
      if (b) b.addEventListener('click', function () { showForm(q, ''); wrap.scrollIntoView({ behavior: 'smooth', block: 'start' }); });
    } else {
      res.innerHTML = '<div style="background:#FFF4E6;border:1px solid #F3D3A5;border-radius:10px;padding:12px 14px;font-size:15px;line-height:1.5;color:#5B3300">По «' + esc(q) + '» на сайте ничего не нашлось. Оставьте заявку ниже, мы проверим наличие и предложим аналог.</div>';
      showForm(q, '');
    }
  }
  var cache = {}, timer, seq = 0;
  function run() {
    var q = inp.value.trim();
    if (q.length < 2) { res.innerHTML = '<div style="font-size:14px;color:#6A7894">Введите хотя бы 2 символа.</div>'; return; }
    var my = ++seq;
    if (cache[q]) { render(cache[q], q); return; }
    res.innerHTML = '<div style="font-size:14px;color:#6A7894">Ищем…</div>';
    fetch(TEK.suggest + (TEK.suggest.indexOf('?') > -1 ? '&' : '?') + 'q=' + encodeURIComponent(q))
      .then(function (r) { return r.json(); })
      .then(function (d) { cache[q] = d; if (my === seq) render(d, q); })
      .catch(function () { res.innerHTML = ''; showForm(q, 'Поиск сейчас недоступен. Оставьте заявку, мы ответим.'); });
  }
  go.addEventListener('click', run);
  inp.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); run(); } });
  inp.addEventListener('input', function () { clearTimeout(timer); if (inp.value.trim().length >= 3) timer = setTimeout(run, 500); });
  var m = location.search.match(/[?&]q=([^&]+)/);
  if (m) { try { inp.value = decodeURIComponent(m[1].replace(/\+/g, ' ')); run(); } catch (e) {} }
});
</script>
<?php get_footer();
