jQuery(function () {

    /**
     * Класс, описывающий страницу генератора
     * @constructor
     */
    var Generator = function() {
        "use strict";

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
                success: function (response, status, xhr) {

                    if (response.status < 200 || response.status >= 300) {
                        error(response.message);
                        return false;
                    }

                    success(response, status, xhr);

                },
                error: function() {
                    error('Произошла ошибка. Обновите страницу');
                }
            });
        };

        /**
         * Метод для показа всплывающего окна с информацией
         *
         * @param {string} content
         * @param {string} title
         */
        var showPopup = function(content, title) {
            alert(content);
            return;
            var options = {
                title: title || 'Внимание!',
                content: content,
                draggable: false,
                resizable: false,
                min_width: 100,
                min_height: 100
            };
            var dialog = new BX.CDialog(options);
            dialog.SetContent(content);
            dialog.Show();
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

                ShowWaitWindow();
                sessionId = this.elements.sessid.value;
                console.log(sessionId);
                var formData = $(this).serialize();
                makeActionRequest('generation_start', formData, generationSuccess, generationError);
                return false;
            });

            $('#js-coupon-generation-preview').on('click', function (event) {

                if (isRunning) {
                    showPopup('Процесс генерации запущен. Необходимо дождаться завершения');
                    return false;
                }

                ShowWaitWindow();
                var template = $('#template').val();
                makeActionRequest('generation_preview', 'template=' + template, previewSuccess, previewError);
                return false;
            });
        };

        var generationSuccess = function (response, status, xhr) {

            ShowWaitWindow();

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
                CloseWaitWindow();
                isRunning = false;
                showPopup(response.message);
            }
        };

        var generationError = function (errorMessage) {
            CloseWaitWindow();
            isRunning = false;
            showPopup(errorMessage);
        };

        var previewSuccess = function (response) {
            CloseWaitWindow();
            if (response.payload.preview) {
                showPopup(response.payload.preview);
            } else {
                previewError('Произошла ошибка, попробуйте снова');
            }
        };

        var previewError = function (errorMessage) {
            CloseWaitWindow();
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