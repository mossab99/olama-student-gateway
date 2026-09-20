(function () {
    'use strict';

    document.querySelectorAll('[data-olama-gateway]').forEach(function (gateway) {
        var menu = gateway.querySelector('[data-gateway-menu]');
        var sidebar = gateway.querySelector('[data-gateway-sidebar]');
        var backdrop = gateway.querySelector('[data-gateway-backdrop]');
        var studentSwitch = gateway.querySelector('[data-student-switch]');
        var teacherSearch = gateway.querySelector('[data-teacher-search]');
        var videoSearch = gateway.querySelector('[data-video-search]');
        var demoExam = gateway.querySelector('[data-demo-exam]');

        if (menu && sidebar) {
            var mobileMenu = window.matchMedia('(max-width: 980px)');
            var setMenuState = function (open, returnFocus) {
                open = mobileMenu.matches && open;
                sidebar.classList.toggle('is-open', open);
                gateway.classList.toggle('has-open-menu', open);
                document.documentElement.classList.toggle('olama-gateway-menu-lock', open);
                menu.setAttribute('aria-expanded', open ? 'true' : 'false');
                sidebar.inert = mobileMenu.matches && !open;
                if (mobileMenu.matches) {
                    sidebar.setAttribute('aria-hidden', open ? 'false' : 'true');
                } else {
                    sidebar.removeAttribute('aria-hidden');
                }
                if (backdrop) {
                    backdrop.setAttribute('tabindex', open ? '0' : '-1');
                }
                if (open) {
                    var firstLink = sidebar.querySelector('a[href]');
                    if (firstLink) {
                        firstLink.focus();
                    }
                } else if (returnFocus) {
                    menu.focus();
                }
            };

            menu.addEventListener('click', function () {
                setMenuState(!sidebar.classList.contains('is-open'), false);
            });

            if (backdrop) {
                backdrop.addEventListener('click', function () {
                    setMenuState(false, true);
                });
            }

            sidebar.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    setMenuState(false, false);
                });
            });

            document.addEventListener('keydown', function (event) {
                if ('Escape' === event.key && sidebar.classList.contains('is-open')) {
                    setMenuState(false, true);
                }
            });

            var syncMenuMode = function () {
                setMenuState(false, false);
            };
            if (mobileMenu.addEventListener) {
                mobileMenu.addEventListener('change', syncMenuMode);
            } else {
                mobileMenu.addListener(syncMenuMode);
            }
            syncMenuMode();
        }

        if (studentSwitch) {
            studentSwitch.addEventListener('change', function () {
                if (studentSwitch.value) {
                    window.location.assign(studentSwitch.value);
                }
            });
        }

        if (teacherSearch) {
            var teacherCards = Array.prototype.slice.call(gateway.querySelectorAll('[data-teacher-card]'));
            var teacherEmpty = gateway.querySelector('[data-teacher-empty]');
            teacherSearch.addEventListener('input', function () {
                var query = teacherSearch.value.trim().toLocaleLowerCase();
                var visible = 0;
                teacherCards.forEach(function (card) {
                    var matches = !query || (card.getAttribute('data-search') || '').toLocaleLowerCase().indexOf(query) !== -1;
                    card.hidden = !matches;
                    if (matches) {
                        visible += 1;
                    }
                });
                if (teacherEmpty) {
                    teacherEmpty.hidden = visible !== 0;
                }
            });
        }

        if (videoSearch) {
            var videoSubjects = Array.prototype.slice.call(gateway.querySelectorAll('[data-video-subject]'));
            var videoEmpty = gateway.querySelector('[data-video-empty]');
            videoSearch.addEventListener('input', function () {
                var query = videoSearch.value.trim().toLocaleLowerCase();
                var visible = 0;
                videoSubjects.forEach(function (subject) {
                    var matches = !query || (subject.getAttribute('data-search') || '').toLocaleLowerCase().indexOf(query) !== -1;
                    subject.hidden = !matches;
                    if (matches) {
                        visible += 1;
                    }
                });
                if (videoEmpty) {
                    videoEmpty.hidden = visible !== 0;
                }
            });
        }

        if (demoExam) {
            var demoQuestions = Array.prototype.slice.call(demoExam.querySelectorAll('[data-demo-question]'));
            var demoProgress = demoExam.querySelector('[data-demo-progress]');
            var demoFinish = demoExam.querySelector('[data-demo-finish]');
            var demoMessage = demoExam.querySelector('[data-demo-message]');
            var questionAnswered = function (question) {
                var controls = Array.prototype.slice.call(question.querySelectorAll('[data-demo-answer]'));
                var radios = controls.filter(function (control) { return 'radio' === control.type; });
                if (radios.length) {
                    return radios.some(function (control) { return control.checked; });
                }
                return controls.length > 0 && controls.every(function (control) {
                    return String(control.value || '').trim() !== '';
                });
            };
            var updateDemoProgress = function () {
                var answered = demoQuestions.filter(questionAnswered).length;
                if (demoProgress) {
                    demoProgress.textContent = answered + ' / ' + demoQuestions.length;
                }
                return answered;
            };

            demoExam.querySelectorAll('[data-demo-answer]').forEach(function (control) {
                control.addEventListener('input', updateDemoProgress);
                control.addEventListener('change', updateDemoProgress);
            });
            if (demoFinish && demoMessage) {
                demoFinish.addEventListener('click', function () {
                    var answered = updateDemoProgress();
                    demoMessage.hidden = false;
                    demoMessage.textContent = 'اكتمل العرض التجريبي: أجبت عن ' + answered + ' من ' + demoQuestions.length + ' أسئلة. لم يتم حفظ أي إجابة أو محاولة أو علامة.';
                    demoMessage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                });
            }
        }
    });
}());
