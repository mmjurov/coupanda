jQuery(function () {

    /**
     * Класс, описывающий страницу генератора
     * @constructor
     */
    var Generator = function() {
        "use strict";

        var popup = new BX.PopupWindow('generator_popup', window.body, {
            autoHide: true,
            offsetTop: 1,
            offsetLeft: 0,
            lightShadow: true,
            closeIcon: true,
            closeByEsc: true,
            overlay: {
                backgroundColor: '#000', opacity: 40
            },
            className: 'popup-window--coupanda-padded'
        });

        /**
         * Запущен ли процесс
         * @type {boolean}
         */
        var isRunning = false;

        /**
         * Идентификатор запущенного процесса
         * @type {int}
         */
        var pid;

        /**
         * Идентификатор сессии
         * @type {string}
         */
        var sessionId = $('#sessid').val();

        /**
         * Ссылка на блок для вставки прогресса
         * @type {*}
         */
        var $progressBlock = $('#js-progress-block');

        /**
         * Ссылка на блок для вставки отчета
         * @type {*}
         */
        var $reportBlock = $('#js-report-block');

        /**
         * Метод шорткат для выполнения ajax запросов
         * @param {string} action Название действия
         * @param {string} data перечисление параметров запроса
         * @param {function} success
         * @param {function} error
         */
        var makeActionRequest = function (action, data, success, error) {
            data += (data.length > 0 ? '&' : '') + 'ajax_action=' + action + '&lang=' + window.phpVars.LANGUAGE_ID;
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: data,
                dataType: 'json',
                headers: {'X-Requested-With': 'XMLHttpRequest'},
                success: function (response, status, xhr) {

                    if (
                        typeof response !== 'object'
                        || response.status === undefined
                        || response.message === undefined
                        || response.payload === undefined
                    ) {
                        error(BX.message['COUPANDA.GENERATOR:INVALID_RESPONSE']);
                        return false;
                    }

                    if (response.status < 200 || response.status >= 300) {
                        error(response.message, response);
                        return false;
                    }

                    // Вызываем с таймаутом, чтобы порядок работы с тробблером не нарушался
                    setTimeout(function(){
                        success(response, status, xhr);
                    }, 1)

                },
                error: function() {
                    error(BX.message['COUPANDA.GENERATOR:UNKNOWN_ERROR']);
                },
                beforeSend: function() {
                    ShowWaitWindow();
                },
                complete: function () {
                    CloseWaitWindow();
                }
            });
        };

        /**
         * Метод для показа всплывающего окна с информацией
         *
         * @param {string} content
         */
        var showPopup = function(content) {
            popup.setContent(content);
            popup.show();
        };

        /**
         * Метод для перехода к другому табу страницы
         * @param {string} tab
         */
        var switchTab = function(tab) {
            coupanda_generator.SelectTab(tab);
        };

        this.bindEvents = function() {

            var formId = 'js-generator-settings';
            var form = document.getElementById(formId);

            if (!form) {
                error(BX.message['COUPANDA.GENERATOR:FORM_NOT_FOUND']);
                return;
            }

            var $form = $(form);
            checkMaxUseAvailability(form);

            $form.on('submit', function (event) {
                if (isRunning) {
                    showPopup(BX.message['COUPANDA.GENERATOR:ALREADY_RUNNING']);
                    return false;
                }

                isRunning = true;

                sessionId = this.elements.sessid.value;
                var formData = $(this).serialize();
                makeActionRequest('generation_start', formData, generationSuccess, generationError);
                return false;
            });

            form.elements['TYPE'].addEventListener('change', function() {
                checkMaxUseAvailability(form);
            });


            $('#js-coupon-generation-preview').on('click', function (event) {

                if (isRunning) {
                    showPopup(BX.message['COUPANDA.GENERATOR:ALREADY_RUNNING']);
                    return false;
                }

                var template = $('#template').val();
                makeActionRequest('generation_preview',
                    'template=' + template + '&sessid=' + sessionId,
                    previewSuccess, previewError
                );
                return false;
            });
        };

        var generationSuccess = function (response, status, xhr) {

            render(response);

            if (!pid) {
                pid = response.payload.pid;
            }

            if (!pid) {
                generationError(BX.message['COUPANDA.GENERATOR:NO_PID'], response);
            } else {
                switchTab('progress');
            }

            if (response.payload.next_action) {
                makeActionRequest(response.payload.next_action, 'sessid=' + sessionId + '&pid=' + pid, generationSuccess, generationError);
            } else {
                switchTab('report');
                isRunning = false;
                pid = undefined;
                showPopup(response.message);
            }
        };

        var generationError = function(errorMessage, response) {
            isRunning = false;
            pid = undefined;
            response && render(response);
            showPopup(errorMessage);
        };

        var previewSuccess = function (response) {
            if (response.payload.preview != undefined) {
                var previewReport = response.payload.preview.map(function (coupon) {
                    return '<span class="popup-window__preview-coupon">' + coupon + '</span>';
                });
                var header = '<p>' + response.payload.preview.length + ' ' + BX.message['COUPANDA.GENERATOR:EXAMPLES'] + '</p>'
                showPopup(header + previewReport.join(''));
            } else {
                previewError(BX.message['COUPANDA.GENERATOR:UNKNOWN_ERROR']);
            }
        };

        var previewError = function (errorMessage) {
            showPopup(errorMessage);
        };

        var renderReportHtml = function(report) {

            var html = report.map(function(reportObject) {
                return '<tr><td>' + reportObject.name + '</td>'
                    + '<td>' + (reportObject.value === null ? '' : reportObject.value) + '</td></tr>';
            });
            return '<table>' + html.join('') + '</table>';
        };

        var render = function(response) {
            if (response.payload.progress_html) {
                $progressBlock.html(response.payload.progress_html);
            }

            if (response.payload.report) {
                $reportBlock.html(renderReportHtml(response.payload.report));
            }
        };

        var checkMaxUseAvailability = function(form) {
            if (form.elements.length <= 0) {
                return;
            }

            var maxUseCount = form.elements.MAX_USE_COUNT;
            var couponType = form.elements['TYPE'];

            maxUseCount.disabled = couponType.value != 4;
        };
    };

    // TODO при входе после потери авторизации событие не навешивается по причине отсутствия элементов формы
    var generator = new Generator();
    generator.bindEvents();
});