/* EU Withdrawal Button — front behaviour | GPL-3.0-or-later */
(function () {
  'use strict';

  function ready(fn) {
    if (document.readyState !== 'loading') { fn(); }
    else { document.addEventListener('DOMContentLoaded', fn); }
  }

  ready(function () {
    var form = document.getElementById('euw-withdraw-form');
    if (!form) { return; }

    var items = document.getElementById('euw-items');
    var radios = form.querySelectorAll('input[name="euw_type"]');

    function syncItems() {
      var partial = form.querySelector('input[name="euw_type"]:checked');
      var isPartial = partial && partial.value === 'partial';
      if (items) { items.style.display = isPartial ? 'block' : 'none'; }
    }

    Array.prototype.forEach.call(radios, function (r) {
      r.addEventListener('change', syncItems);
    });
    syncItems();

    // Extra safeguard: explicit confirmation dialog before final submission.
    form.addEventListener('submit', function (e) {
      var msg = form.getAttribute('data-confirm')
        || 'Confermi l’invio della richiesta di recesso? L’operazione sarà registrata e riceverai una conferma via email.';
      if (!window.confirm(msg)) {
        e.preventDefault();
      }
    });
  });
})();
