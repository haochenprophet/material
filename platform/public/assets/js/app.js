/* 物格云 v0.1.0 · 无依赖渐进增强 */
(function () {
  'use strict';

  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  function toast(message) {
    var el = document.getElementById('toast');
    if (!el) return;
    el.textContent = message;
    el.classList.add('show');
    clearTimeout(el._timer);
    el._timer = setTimeout(function () { el.classList.remove('show'); }, 1800);
  }

  ready(function () {
    // 侧边栏抽屉
    var sidebar = document.getElementById('appSidebar');
    var toggle = document.querySelector('[data-action="toggle-sidebar"]');
    if (toggle && sidebar) {
      toggle.addEventListener('click', function () {
        var open = sidebar.classList.toggle('open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
      sidebar.querySelectorAll('a').forEach(function (a) {
        a.addEventListener('click', function () {
          sidebar.classList.remove('open');
          toggle.setAttribute('aria-expanded', 'false');
        });
      });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
          sidebar.classList.remove('open');
          toggle.setAttribute('aria-expanded', 'false');
        }
      });
    }

    // 演示 toast
    document.querySelectorAll('[data-action="toast"]').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        toast(btn.getAttribute('data-toast') || '操作成功');
      });
    });

    // 质检项 通过/不通过/N/A
    document.querySelectorAll('[data-qc]').forEach(function (item) {
      var buttons = item.querySelectorAll('[data-qc-state]');
      buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
          buttons.forEach(function (b) {
            b.classList.remove('on', 'bad');
            b.setAttribute('aria-pressed', 'false');
          });
          var state = btn.getAttribute('data-qc-state');
          btn.classList.add(state === 'fail' ? 'bad' : 'on');
          btn.setAttribute('aria-pressed', 'true');
          var note = item.querySelector('small');
          if (note && state === 'fail') {
            note.style.display = '';
          }
        });
      });
    });

    // 搜索快捷键
    document.addEventListener('keydown', function (e) {
      if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        var input = document.querySelector('.app-search input');
        if (input) input.focus();
      }
    });

    // 文件选择提示（不上传，只做 UI 反馈）
    document.querySelectorAll('.uploader input[type="file"]').forEach(function (input) {
      input.addEventListener('change', function () {
        if (input.files && input.files.length) {
          toast('已选择 ' + input.files.length + ' 个文件（演示：不真正上传）');
        }
      });
    });
  });
})();

  // 开发诊断：?debug=1 输出布局宽度与越界元素（不启用任何业务逻辑）
  var params = new URLSearchParams(location.search);
  if (params.get('debug') === '1') {
    var cw = document.documentElement.clientWidth;
    var over = [];
    document.querySelectorAll('body *').forEach(function (el) {
      var r = el.getBoundingClientRect();
      if (r.right > cw + 1 && r.width > 0 && over.length < 8) {
        over.push((el.tagName + '.' + (el.className || '')).slice(0, 80) + ':' + Math.round(r.right));
      }
    });
    document.body.setAttribute('data-debug', JSON.stringify({
      clientWidth: cw,
      scrollWidth: document.documentElement.scrollWidth,
      bodyScrollWidth: document.body.scrollWidth,
      over: over
    }));
  }