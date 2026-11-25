(function () {
  'use strict';
  document.querySelectorAll('.thumb-link').forEach(function (link) {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      var src = link.getAttribute('data-src');
      var main = document.querySelector('.detail-gallery .main-img img');
      if (src && main) { main.src = src; }
    });
  });
  document.querySelectorAll('[data-reply]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var hidden = document.getElementById('comment-parent');
      if (!hidden) { return; }
      hidden.value = btn.getAttribute('data-reply') || '';
      var form = hidden.closest('form');
      if (form) {
        var ta = form.querySelector('textarea');
        if (ta) { ta.focus(); ta.scrollIntoView({block: 'center', behavior: 'smooth'}); }
      }
    });
  });
})();
