/* EU Withdrawal Button — front behaviour | GPL-3.0-or-later */
(function () {
  'use strict';

  function ready(fn) {
    if (document.readyState !== 'loading') { fn(); }
    else { document.addEventListener('DOMContentLoaded', fn); }
  }

  ready(function () {
    // Step 1: toggle product selection when "partial" is chosen.
    var form = document.getElementById('euw-withdraw-form');
    if (form) {
      var items = document.getElementById('euw-items');
      var radios = form.querySelectorAll('input[name="euw_type"]');
      var syncItems = function () {
        var checked = form.querySelector('input[name="euw_type"]:checked');
        var isPartial = checked && checked.value === 'partial';
        if (items) { items.style.display = isPartial ? 'block' : 'none'; }
      };
      Array.prototype.forEach.call(radios, function (r) { r.addEventListener('change', syncItems); });
      syncItems();
    }

    // Step 2: explicit confirmation dialog before final submission.
    var confirmForm = document.getElementById('euw-confirm-form');
    if (confirmForm) {
      confirmForm.addEventListener('submit', function (e) {
        var msg = confirmForm.getAttribute('data-confirm')
          || 'Confermi l’invio della richiesta di recesso? L’operazione sarà registrata e riceverai una conferma via email.';
        if (!window.confirm(msg)) { e.preventDefault(); }
      });
    }
  });
})();
