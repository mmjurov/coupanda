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
            data += (data.length > 0 ? '&' : '') + 'ajax_action=' + action;
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: data,
                dataType: 'json',
                headers: {'X-Requested-With': 'XMLHttpRequest'},
                success: function (response, status, xhr) {

                    if (response.status < 200 || response.status >= 300) {
                        error(response.message);
                        return false;
                    }

                    // Вызываем с таймаутом, чтобы порядок работы с тробблером не нарушался
                    setTimeout(function(){
                        success(response, status, xhr);
                    }, 1)

                },
                error: function() {
                    error('Произошла ошибка. Обновите страницу');
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

        this.bindEvents = function () {
            $('#js-generator-settings').on('submit', function (event) {
                if (isRunning) {
                    showPopup('Процесс уже запущен. Необходимо дождаться завершения');
                    return false;
                }

                isRunning = true;

                sessionId = this.elements.sessid.value;
                var formData = $(this).serialize();
                makeActionRequest('generation_start', formData, generationSuccess, generationError);
                return false;
            });

            $('#js-coupon-generation-preview').on('click', function (event) {

                if (isRunning) {
                    showPopup('Процесс генерации запущен. Необходимо дождаться завершения');
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

            if (response.payload.progress_html) {
                $progressBlock.html(response.payload.progress_html);
            }

            if (response.payload.report) {
                $reportBlock.html(renderReportHtml(response.payload.report));
            }

            if (response.payload.init && response.payload.init == 'ok') {
                switchTab('progress');
            }

            if (response.payload.next_action) {
                makeActionRequest(response.payload.next_action, 'sessid=' + sessionId, generationSuccess, generationError);
            } else {
                switchTab('report');
                isRunning = false;
                showPopup(response.message);
            }
        };

        var generationError = function (errorMessage) {
            isRunning = false;
            showPopup(errorMessage);
        };

        var previewSuccess = function (response) {
            if (response.payload.preview != undefined) {
                showPopup(response.payload.preview);
            } else {
                previewError('Произошла ошибка, попробуйте снова');
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
        }
    };

    var generator = new Generator();
    generator.bindEvents();
});