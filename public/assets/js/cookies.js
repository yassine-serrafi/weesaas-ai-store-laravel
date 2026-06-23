/* WeeSaaS — Bannière cookies */
(function() {
  var KEY = 'wee_cookies_consent';
  var bar = document.getElementById('cookies-bar');
  if (!bar) return;
  if (!localStorage.getItem(KEY)) {
    setTimeout(function() { bar.classList.add('show'); }, 1500);
  }
  var btnAccept = document.getElementById('cookies-accept');
  var btnRefuse = document.getElementById('cookies-refuse');
  function accept() {
    localStorage.setItem(KEY, '1');
    bar.classList.remove('show');
    loadPixels();
  }
  function refuse() {
    localStorage.setItem(KEY, '0');
    bar.classList.remove('show');
  }
  if (btnAccept) btnAccept.addEventListener('click', accept);
  if (btnRefuse) btnRefuse.addEventListener('click', refuse);
  if (localStorage.getItem(KEY) === '1') loadPixels();

  function loadPixels() {
    var gaId = window.GA_ID;
    var fbId = window.FB_PIXEL_ID;
    var ttId = window.TT_PIXEL_ID;
    if (gaId) {
      var s1 = document.createElement('script');
      s1.src = 'https://www.googletagmanager.com/gtag/js?id=' + gaId;
      s1.async = true;
      document.head.appendChild(s1);
      window.dataLayer = window.dataLayer || [];
      window.gtag = function() { dataLayer.push(arguments); };
      gtag('js', new Date());
      gtag('config', gaId);
    }
    if (fbId) {
      !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
      fbq('init', fbId);
      fbq('track', 'PageView');
    }
    if (ttId) {
      !function(w,d,t){w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=['page','track','identify','instances','debug','on','off','once','ready','alias','group','enableCookie','disableCookie'];ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e};ttq.load=function(e,n){var i='https://analytics.tiktok.com/i18n/pixel/events.js';ttq._i=ttq._i||{};ttq._i[e]=[];ttq._i[e]._u=i;ttq._t=ttq._t||{};ttq._t[e]=+new Date;ttq._o=ttq._o||{};ttq._o[e]=n||{};var o=document.createElement('script');o.type='text/javascript';o.async=!0;o.src=i+'?sdkid='+e+'&lib='+t;var a=document.getElementsByTagName('script')[0];a.parentNode.insertBefore(o,a)};ttq.load(ttId);ttq.page()}(window,document,'ttq');
    }
  }
})();
