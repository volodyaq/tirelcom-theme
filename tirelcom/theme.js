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
