/**
 * Javascript containing function of the training catalog
 */

define([
    'jquery',
    'jqueryui',
    'local_mentor_core/mentor',
    'format_edadmin/format_edadmin',
    'core/templates',
    'core/str'
], function ($, ui, mentor, format_edadmin, templates, str) {

    var trainingCatalog = {
        /**
         * Init JS
         */
        init: function () {

            // Get all session data to the training catalog.
            this.sessionData = JSON.parse($('#available-sessions').text());

            this.initCopyLink();

            this.initTrainingObjective();

            this.initTrainingSession();

            this.initSessionTileEvent();
            
            this.saveTrainingId();
            
        },
        /**
         * Init copy training catalog link event.
         */
        initCopyLink: function () {
            $('.copy-training-link').on('click', function (e) {
                e.preventDefault();

                var copyLinkButton = e.currentTarget;

                var trainingId = $(this).data('trainingid');

                var link = M.cfg.wwwroot + '/catalog/' + trainingId;

                navigator.clipboard.writeText(link).then(function () {
                    mentor.dialog("<div>" + M.util.get_string('copylinktext', 'local_mentor_core') + "</div>", {
                        close: function () {
                            copyLinkButton.focus();
                        },
                        title: M.util.get_string('confirmation', 'admin'),
                        position: { my: "center", at: "top+20" },
                        buttons: [
                            {
                                text: "OK",
                                class: "btn btn-primary",
                                click: function () {
                                    $(this).dialog("close");
                                    copyLinkButton.focus();
                                }
                            }
                        ]
                    });
                }, function () {
                    mentor.dialog("<div>" + M.util.get_string('copylinkerror', 'local_mentor_core') + link + "</div>", {
                        close: function () {
                            copyLinkButton.focus();
                        },
                        title: M.util.get_string('error', 'moodle'),
                        position: { my: "center", at: "top+20" },
                        buttons: [
                            {
                                text: "OK",
                                class: "btn btn-primary",
                                click: function () {
                                    $(this).dialog("close");
                                    copyLinkButton.focus();
                                }
                            }
                        ]
                    });
                });
            });
        },
        /**
         * Init training catalog objective event show/hide text.
         */
        initTrainingObjective: function () {

            var initialHeight = $('.training-goal').height();
            var isTruncated = true;
            $('.training-goal').removeClass('hide');

            // Truncate logic based on height
            function applyInitialTruncation() {
                if (initialHeight > 130) {
                    $('.training-goal').addClass('truncate');
                    $('#gradientmask').removeClass('hide');
                    $('.toggle-content').removeClass('hide');
                    isTruncated = true;
                } else {
                    $('#gradientmask').addClass('hide');
                    $('.toggle-content').addClass('hide');
                    $('.training-goal').removeClass('truncate');
                }
            }

            // update button text and toggle behaviour
            function toggleContentVisibility() {
                isTruncated = !isTruncated;
                let $toggleButton = $('.toggle-content');
                if (!isTruncated) {
                    $('.training-goal').removeClass('truncate');
                    $('#gradientmask').addClass('hide');
                    // get i18n string relative to "viewless" and then replace the button text by the string content
                    str.get_string('viewless', 'local_catalog').then(function (str) {
                        $toggleButton.text(str);
                    });
                    $toggleButton.attr('aria-expanded', 'true');
                } else {
                    $('.training-goal').addClass('truncate');
                    $('#gradientmask').removeClass('hide');
                    str.get_string('readmore', 'local_catalog').then(function (str) {
                        $toggleButton.text(str);
                    });
                    $toggleButton.attr('aria-expanded', 'false');
                }
            }

            // Initialize truncatation
            applyInitialTruncation();

            $('.toggle-content, #gradientmask').click(function (e) {
                e.preventDefault();
                toggleContentVisibility();
            });

            $(window).on('resize', function () {
                $('.show-more:before').width($('#training-objective').width());
            });

        },
        /**
         * Init view sessions tile training catalog event.
         */
        initTrainingSession: function () {
            var sessionList = $('button.session');

            if (sessionList.length > 3) {
                $('.show-sessions').removeClass('hide');
                $('.session').slice(3).hide();

                $('.show-sessions').click(function (e) {
                    if ($(e.currentTarget).hasClass('more')) {
                        $('.session').slice(3).show();
                        $('.show-sessions').removeClass('more');
                        $('.show-sessions').addClass('less');
                    } else {
                        $('.session').slice(3).hide();
                        $('.show-sessions').addClass('more');
                        $('.show-sessions').removeClass('less');
                    }
                });
            }
        },
        /**
         * Get session data by session id.
         *
         * @param {int} sessionId
         * @returns {JSON}
         */
        getSessionDataById: function (sessionId) {
            return this.sessionData.find(function (x) {
                return x.id == sessionId;
            });
        },
        /**
         * Init session tile event.
         */
        initSessionTileEvent: function () {
            var that = this;

            // Trigger click event to available enrol and enrol session tile.
            $('button.session.available-enrol, button.session.is-enrol, #confirm-move-session').click(function (e) {
                // Not use event if is preview.
                if ($(e.currentTarget).hasClass('session-preview')) {
                    return;
                }

                if ($('body').hasClass('notloggedin')) {
                    // Create pop-in to redirect login page.
                    mentor.dialog(
                        '<div id="session-not-login-popin">' +
                        M.util.get_string('nologginsessionaccess', 'local_catalog') + '<div>',
                        {
                            width: 500,
                            title: M.util.get_string('registrationsession', 'local_catalog'),
                            buttons: [
                                {
                                    // Login page redirect.
                                    text: M.util.get_string('toconnect', 'local_catalog'),
                                    class: "btn btn-primary",
                                    click: function () {
                                        window.location.href = M.cfg.wwwroot + '/login/index.php';
                                    }
                                },
                                {
                                    // Cancel button
                                    text: M.util.get_string('cancel', 'format_edadmin'),
                                    class: "btn btn-secondary",
                                    click: function () {
                                        // Just close the modal.
                                        $(this).dialog("destroy");
                                    }
                                }]
                        });

                    return;
                }

                var sessionId = $(e.currentTarget).data('sessionId') || $(e.target).parent().data('sessionId');
                // Check if session id is defined.
                if (typeof sessionId === 'undefined') {
                    return;
                }
                var sessionData = that.getSessionDataById(sessionId);

                if(that.sessionData && that.sessionData.length == 1) {
                    that.enrolmentToSession(sessionData);
                    return;
                }

                format_edadmin.ajax_call({
                    url: M.cfg.wwwroot + '/local/catalog/ajax/ajax.php',
                    controller: 'catalog',
                    action: 'get_session_enrolment_data',
                    format: 'json',
                    sessionid: sessionId,
                    callback: function (response) {

                        response = JSON.parse(response);

                        if (!response.success) {
                            return;
                        }

                        var responseData = response.message;

                        if (responseData.selfenrolment) {
                            sessionData.isselfenrolment = true;
                            sessionData.hasselfregistrationkey = responseData.hasselfregistrationkey;
                        } else {
                            sessionData.isotherenrolement = true;
                            sessionData.message = responseData.message;
                        }

                        that.getSessionEnrolPopin(sessionData);
                    }
                });
            });
        },

        getSessionEnrolPopin: function (sessionData) {
            var that = this;

            templates.renderForPromise('local_catalog/session-popin', sessionData)
                .then(function (_ref) {
                    var html = _ref.html;

                    var buttons = [];

                    // Add access session button if user is enrol to sesison.
                    if (sessionData.isenrol && (!sessionData.isopenedregistration || sessionData.istutor)) {
                        buttons.push({
                            // Access button
                            text: M.util.get_string('access', 'local_catalog'),
                            id: 'confirm-move-session',
                            class: "btn btn-primary",
                            click: function () {
                                window.location.href = sessionData.courseurl;
                            }
                        });
                        // Add enrolment button if self enrol is enable to session.
                    } else if (sessionData.isselfenrolment) {
                        buttons.push({
                            // Remove button
                            text: M.util.get_string('register', 'local_catalog'),
                            id: 'confirm-move-session',
                            class: "btn btn-primary",
                            click: function () {
                                that.enrolmentToSession(sessionData);
                            }
                        });
                    }




                    // Add cancel button.
                    buttons.push({
                        // Cancel button
                        text: M.util.get_string('cancel', 'format_edadmin'),
                        class: "btn btn-secondary",
                        click: function () {
                            // Just close the modal.
                            $(this).dialog("destroy");
                        }
                    });

                    // Create pop-in enrolment.
                    mentor.dialog('<div id="session-enrol-popin">' + html + '<div>', {
                        width: 650,
                        title: sessionData.fullname,
                        buttons: buttons
                    });
                });
        },
        /**
         * Create enrolment pop-in
         *
         * @param {Object} sessionData
         */
        enrolmentToSession: function (sessionData) {

            // Retrieves the key that the user has entered.
            var enrolmentKey = $('#enrolmentkey').val();

            format_edadmin.ajax_call({
                url: M.cfg.wwwroot + '/local/catalog/ajax/ajax.php',
                controller: 'catalog',
                action: 'enrol_current_user',
                format: 'json',
                sessionid: sessionData.id,
                enrolmentkey: enrolmentKey,
                callback: function (ajaxResponse) {
                    ajaxResponse = JSON.parse(ajaxResponse);

                    if (ajaxResponse.success == true) {
                        var urlredirection = ajaxResponse.message;

                        if (sessionData.isopenedregistration && !sessionData.istutor) {
                            urlredirection = M.cfg.wwwroot + '/local/catalog/pages/training.php?trainingid=' + sessionData.trainingid;
                        }

                        window.location.href = urlredirection;
                    } else {
                        $('.enrolment-warning').html(ajaxResponse.message);
                        $('#enrolmentkey-container input').addClass("warning");
                    }
                }
            });
        },
        /**
         * Save training id for scroll when back to catalog
         */
        saveTrainingId : function() {
            const urlParams = new URLSearchParams(window.location.search);
            const trainingId = urlParams.get('trainingid');
            const catalogUrl =  `${M.cfg.wwwroot}/local/catalog/index.php`;
            if (document.referrer != catalogUrl){
                return;
            }
            if (trainingId) {
                sessionStorage.setItem('selectedTrainingIdCatalog', trainingId);
            }
        }
    };

    // Add object to window to be called outside require.
    window.trainingCatalog = trainingCatalog;
    return trainingCatalog;
});
