<?php
/**
 * Баннер cookie и счётчики. Счётчики подключаются только после согласия.
 * ID счётчиков задаются опциями tek_ym_id (Яндекс Метрика, число) и tek_ga_id (Google Analytics, G-XXXXXXXXXX).
 */
defined('ABSPATH') || exit;

add_action('wp_footer', function () {
    $ym = preg_replace('/\D/', '', (string) get_option('tek_ym_id'));
    $ga = preg_replace('/[^A-Za-z0-9\-]/', '', (string) get_option('tek_ga_id'));
    $pol = home_url('/politika-konfidencialnosti/');
    $has = ($ym || $ga);
    ?>
<div id="tek-ck" hidden role="dialog" aria-label="Cookie" style="position:fixed;left:12px;right:12px;bottom:12px;z-index:99990;max-width:640px;margin:0 auto;background:#0F2A5C;color:#fff;border-radius:14px;padding:16px 18px;box-shadow:0 8px 30px rgba(0,0,0,.3);font-size:14px;line-height:1.5">
<div><?php echo $has ? 'Мы используем cookie, необходимые для работы сайта, и, с вашего согласия, счётчики посещаемости.' : 'Мы используем только cookie, необходимые для работы сайта (корзина, оформление заказа).'; ?> <a href="<?php echo esc_url($pol); ?>" style="color:#FF8A1F;text-decoration:underline">Политика конфиденциальности</a></div>
<div style="display:flex;gap:10px;margin-top:12px;flex-wrap:wrap">
<button type="button" data-ck="yes" style="min-height:42px;padding:0 20px;border:0;border-radius:10px;background:#FF8A1F;color:#0F1B33;font:inherit;font-weight:700;cursor:pointer"><?php echo $has ? 'Принять' : 'Понятно'; ?></button>
<?php if ($has) : ?><button type="button" data-ck="no" style="min-height:42px;padding:0 20px;border:1px solid #fff;border-radius:10px;background:transparent;color:#fff;font:inherit;font-weight:600;cursor:pointer">Только необходимые</button><?php endif; ?>
</div></div>
<script>
(function(){
var YM='<?php echo esc_js($ym); ?>',GA='<?php echo esc_js($ga); ?>',K='tek_cookie_choice';
function get(){try{return localStorage.getItem(K)}catch(e){return null}}
function set(v){try{localStorage.setItem(K,v)}catch(e){}}
function load(){
 if(window.__tekC)return;window.__tekC=1;
 if(GA){var s=document.createElement('script');s.async=1;s.src='https://www.googletagmanager.com/gtag/js?id='+GA;document.head.appendChild(s);window.dataLayer=window.dataLayer||[];window.gtag=function(){dataLayer.push(arguments)};gtag('js',new Date());gtag('config',GA,{anonymize_ip:true});}
 if(YM){(function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};m[i].l=1*new Date();k=e.createElement(t);a=e.getElementsByTagName(t)[0];k.async=1;k.src=r;a.parentNode.insertBefore(k,a)})(window,document,'script','https://mc.yandex.ru/metrika/tag.js','ym');ym(+YM,'init',{clickmap:true,trackLinks:true,accurateTrackBounce:true});}
}
var b=document.getElementById('tek-ck');
function show(){b.hidden=false}
var c=get();
if(c==='yes')load();else if(c===null)show();
b.addEventListener('click',function(e){var v=e.target.getAttribute&&e.target.getAttribute('data-ck');if(!v)return;set(v);b.hidden=true;if(v==='yes')load();});
document.addEventListener('click',function(e){var a=e.target.closest&&e.target.closest('[data-ck-open]');if(a){e.preventDefault();show();}});
})();
</script>
<?php
}, 50);
