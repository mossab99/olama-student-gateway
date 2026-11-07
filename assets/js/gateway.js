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

        var videoCards = Array.prototype.slice.call(gateway.querySelectorAll('[data-video-card]'));
        var videoSubjects = Array.prototype.slice.call(gateway.querySelectorAll('[data-video-subject]'));
        var videoGrid = gateway.querySelector('[data-video-cards]');
        var videoToolbar = gateway.querySelector('.olama-gateway__video-toolbar');
        var selectedCard = null;
        videoCards.forEach(function (card) {
            card.addEventListener('click', function () {
                selectedCard = card;
                videoCards.forEach(function (item) { item.setAttribute('aria-expanded', item === card ? 'true' : 'false'); });
                videoSubjects.forEach(function (subject) {
                    subject.hidden = subject.id !== card.getAttribute('aria-controls');
                    if (!subject.hidden) { subject.querySelector('[data-video-subject-title]').focus(); }
                });
                videoGrid.hidden = true;
                if (videoToolbar) { videoToolbar.hidden = true; }
            });
        });
        gateway.querySelectorAll('[data-video-back]').forEach(function (button) {
            button.addEventListener('click', function () {
                videoSubjects.forEach(function (subject) { subject.hidden = true; });
                videoGrid.hidden = false;
                if (videoToolbar) { videoToolbar.hidden = false; }
                videoCards.forEach(function (card) { card.setAttribute('aria-expanded', 'false'); });
                if (selectedCard) { selectedCard.focus(); }
            });
        });
        if (videoSearch) {
            var videoEmpty = gateway.querySelector('[data-video-empty]');
            videoSearch.addEventListener('input', function () {
                var query = videoSearch.value.trim().toLocaleLowerCase();
                var visible = 0;
                videoCards.forEach(function (card) {
                    card.hidden = !!query && (card.getAttribute('data-search') || '').toLocaleLowerCase().indexOf(query) === -1;
                    if (!card.hidden) { visible += 1; }
                });
                if (videoEmpty) { videoEmpty.hidden = visible !== 0; }
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

(function () {
    'use strict';
    document.querySelectorAll('[data-evaluations]').forEach(function (root) {
        var links = Array.from(root.querySelectorAll('[data-evaluation-tab]'));
        var reports = Array.from(root.querySelectorAll('[data-evaluation-report]'));
        function select(id) {
            reports.forEach(function (report) { report.hidden = report.id !== id; });
            links.forEach(function (link) { link.setAttribute('aria-current', link.hash === '#' + id ? 'true' : 'false'); });
        }
        links.forEach(function (link) {
            link.addEventListener('click', function (event) { event.preventDefault(); select(link.hash.slice(1)); });
        });
        var initial = reports.find(function (report) { return '#' + report.id === window.location.hash; }) || reports[0];
        if (initial) { select(initial.id); }
        var printRoot = null;
        function restore() {
            document.body.classList.remove('og-printing-evaluation');
            if (printRoot) { printRoot.remove(); printRoot = null; }
        }
        function prepare(report) {
            restore();
            var layout = report.querySelector('[data-evaluation-print-layout]');
            if (!layout) { return; }
            printRoot = document.createElement('div');
            printRoot.className = 'og-evaluation-print-root';
            printRoot.appendChild(layout.cloneNode(true));
            document.body.appendChild(printRoot);
            document.body.classList.add('og-printing-evaluation');
        }
        root.querySelectorAll('[data-evaluation-print]').forEach(function (button) {
            button.addEventListener('click', function () { prepare(button.closest('[data-evaluation-report]')); window.print(); });
        });
        window.addEventListener('beforeprint', function () {
            if (!document.body.classList.contains('og-printing-evaluation')) {
                var current = reports.find(function (report) { return !report.hidden; });
                if (current) { prepare(current); }
            }
        });
        window.addEventListener('afterprint', restore);
    });
    var videoScopeSelector = '[data-olama-gateway], [data-olama-plan-player]';
    document.querySelectorAll(videoScopeSelector).forEach(function (scope) {
        var videoDialog = Array.from(scope.querySelectorAll('[data-video-dialog]')).find(function (dialog) {
            return dialog.closest(videoScopeSelector) === scope;
        });
        if (videoDialog) {
            var videoScreen = videoDialog.querySelector('[data-video-screen]');
            var videoTitle = videoDialog.querySelector('[data-video-title]');
            var videoError = videoDialog.querySelector('[data-video-error]');
            var videoTrigger = null;
            function playerSource(value) {
                var url;
                try { url = new URL(value, window.location.href); } catch (error) { return null; }
                if (!/^https?:$/.test(url.protocol)) { return null; }
                var host = url.hostname.toLowerCase();
                var id;
                if (host === 'drive.google.com') {
                    var match = url.pathname.match(/^\/file\/d\/([a-zA-Z0-9_-]+)/);
                    id = match ? match[1] : url.searchParams.get('id');
                    if (id && /^[a-zA-Z0-9_-]+$/.test(id)) { return { src: 'https://drive.google.com/file/d/' + id + '/preview', type: 'iframe', restrictNavigation: true }; }
                }
                if (['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'www.youtube-nocookie.com'].indexOf(host) !== -1) {
                    id = host === 'youtu.be' ? url.pathname.slice(1) : url.searchParams.get('v') || url.pathname.replace(/^\/(embed|shorts)\//, '');
                    if (/^[a-zA-Z0-9_-]{11}$/.test(id || '')) { return { src: 'https://www.youtube-nocookie.com/embed/' + id, type: 'iframe' }; }
                }
                if (['vimeo.com', 'www.vimeo.com', 'player.vimeo.com'].indexOf(host) !== -1) {
                    var vimeo = url.pathname.match(/^\/(?:video\/)?(\d+)(?:\/([a-zA-Z0-9]+))?\/?$/);
                    if (vimeo) { return { src: 'https://player.vimeo.com/video/' + vimeo[1] + ((vimeo[2] || url.searchParams.get('h')) ? '?h=' + encodeURIComponent(vimeo[2] || url.searchParams.get('h')) : ''), type: 'iframe' }; }
                }
                if (/\.(mp4|webm|ogv|m4v)$/i.test(url.pathname)) { return { src: url.href, type: 'video' }; }
                return null;
            }
            scope.addEventListener('click', function (event) {
                var button = event.target.closest('[data-video-play]');
                if (!button || button.closest(videoScopeSelector) !== scope) { return; }
                videoTrigger = button;
                videoScreen.replaceChildren();
                videoTitle.textContent = button.getAttribute('data-video-title') || '';
                var source = playerSource(button.getAttribute('data-video-url'));
                videoError.hidden = !!source;
                videoScreen.classList.toggle('is-drive-player', !!source && !!source.restrictNavigation);
                if (source) {
                    var player = document.createElement(source.type);
                    if (source.type === 'video') { player.controls = true; player.playsInline = true; player.preload = 'metadata'; }
                    else {
                        player.title = videoTitle.textContent;
                        if (source.restrictNavigation) {
                            // Drive's own pop-out control cannot be removed across origins.
                            // Block popups and top navigation even if reached by keyboard.
                            player.setAttribute('sandbox', 'allow-scripts allow-same-origin allow-presentation');
                        }
                        player.allow = 'autoplay; encrypted-media; fullscreen; picture-in-picture';
                        player.allowFullscreen = true;
                        player.referrerPolicy = 'strict-origin-when-cross-origin';
                    }
                    player.src = source.src;
                    videoScreen.appendChild(player);
                }
                videoDialog.showModal();
            });
            videoDialog.querySelector('[data-video-close]').addEventListener('click', function () { videoDialog.close(); });
            videoDialog.addEventListener('click', function (event) {
                var bounds = videoDialog.getBoundingClientRect();
                if (event.target === videoDialog && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom)) { videoDialog.close(); }
            });
            videoDialog.addEventListener('close', function () {
                videoScreen.replaceChildren();
                videoScreen.classList.remove('is-drive-player');
                if (videoTrigger) { videoTrigger.focus(); }
            });
        }
    });
}());
