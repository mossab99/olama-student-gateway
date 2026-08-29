(function () {
    'use strict';

    document.querySelectorAll('[data-olama-gateway]').forEach(function (gateway) {
        var menu = gateway.querySelector('[data-gateway-menu]');
        var sidebar = gateway.querySelector('[data-gateway-sidebar]');
        var studentSwitch = gateway.querySelector('[data-student-switch]');

        if (menu && sidebar) {
            menu.addEventListener('click', function () {
                var open = sidebar.classList.toggle('is-open');
                menu.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        }

        if (studentSwitch) {
            studentSwitch.addEventListener('change', function () {
                if (studentSwitch.value) {
                    window.location.assign(studentSwitch.value);
                }
            });
        }
    });
}());

