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
