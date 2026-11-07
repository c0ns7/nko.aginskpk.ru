(function () {
  var toggle = document.querySelector('[data-nav-toggle]');
  var nav = document.querySelector('[data-nav]');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  var toast = document.querySelector('.toast');
  if (toast) {
    setTimeout(function () {
      toast.style.display = 'none';
    }, 4200);
  }

  var delForms = document.querySelectorAll('[data-confirm]');
  for (var i = 0; i < delForms.length; i++) {
    delForms[i].addEventListener('submit', function (e) {
      if (!window.confirm(this.getAttribute('data-confirm'))) {
        e.preventDefault();
      }
    });
  }
})();
