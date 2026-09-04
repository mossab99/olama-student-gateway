(function () {
    'use strict';

    document.querySelectorAll('[data-olama-gateway]').forEach(function (gateway) {
        var menu = gateway.querySelector('[data-gateway-menu]');
        var sidebar = gateway.querySelector('[data-gateway-sidebar]');
        var backdrop = gateway.querySelector('[data-gateway-backdrop]');
        var studentSwitch = gateway.querySelector('[data-student-switch]');

        if (menu && sidebar) {
            var setMenuState = function (open) {
                sidebar.classList.toggle('is-open', open);
                gateway.classList.toggle('has-open-menu', open);
                menu.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (backdrop) {
                    backdrop.setAttribute('tabindex', open ? '0' : '-1');
                }
            };

            menu.addEventListener('click', function () {
                setMenuState(!sidebar.classList.contains('is-open'));
            });

            if (backdrop) {
                backdrop.addEventListener('click', function () {
                    setMenuState(false);
                });
            }

            sidebar.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    setMenuState(false);
                });
            });

            document.addEventListener('keydown', function (event) {
                if ('Escape' === event.key && sidebar.classList.contains('is-open')) {
                    setMenuState(false);
                    menu.focus();
                }
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
