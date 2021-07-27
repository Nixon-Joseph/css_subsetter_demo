$(function() {
    var emailRegex = $.helpers.constants.emailRegex;

    $('body').on('click', '.forgot-password-link', function(e) {
        e.preventDefault();
        $.everywhere.confirm({
            title: 'Forgot Password',
            body: '<p>To reset your password, please provide the email address associated with your account below.</p>' +
                    '<input class="form-control" type="email" id="ForgotPasswordEmail" placeholder="Email Address" />' +
                    '<span class="invalid-feedback">Please provide a valid email address</span>',
            size: 'sm',
            confirmText: 'Reset',
            dismissOnConfirm: false,
            shownCallback: function($modal, e) {
                $modal.find('#ForgotPasswordEmail').focus().on('keyup', function (e) {
                    if (e.keyCode === 13) {
                        $modal.confirmButton.trigger('click');
                    }
                })
            },
            confirmCallback: function($modal, e) {
                var email = $('#ForgotPasswordEmail').removeClass('is-invalid').val();
                if (email && emailRegex.test(email)) {
                    $('.register-loader').fadeIn();
                    $modal.disable();
                    $.getJSON('/api/account/BeginResetPassword?email=' + email, function(response) {
                        if (response) {
                            if (response.Success === true) {
                                $modal.modal('hide');
                                $.toast.success("Reset password email sent!");
                            } else {
                                $.toast.danger("Error: " + response.Message);
                            }
                        } else {
                            $.toast.danger("Could not send the reset password email at this time. Please try again later.");
                        }
                    }).fail(function() {
                        $.toast.danger("Could not send the reset password email at this time. Please try again later.");
                    }).always(function() {
                        $('.register-loader').fadeOut();
                        $modal.enable();
                    });
                } else {
                    $('#ForgotPasswordEmail').addClass('is-invalid');
                }
            }
        });
    });

    $('#PasswordInput').on('keyup', function(e) {
        if (e.keyCode === 13) {
            $('#LoginButton').trigger('click');
        }
    });
    $('#LoginButton').on('click', function() {
        var $button = $(this),
            username = $('#UsernameInput').removeClass('is-invalid').val(),
            password = $('#PasswordInput').removeClass('is-invalid').val();
        if (username && password) {
            $('#UsernameInput, #PasswordInput, #LoginButton').prop('disabled', true);
            var btnText = $button.html();
            $button.html('<i class="fas fa-spinner fa-pulse"></i> Loggin in...')
            $.postJSON('/api/account/login', { payload: btoa(username + '|::|' + password) }, function(response) {
                if (response) {
                    if (response.Success === true) {
                        if (response.Id === "2FA") {
                            var postObj = {
                                '2fa': response.Message,
                                code: null
                            };
                            $.everywhere.confirm({
                                title: '2 Factor Authentication',
                                body: '<p class="text-center">Two factor authentication has been enabled for this account. Please provide the code from your authenticator app below</p>' +
                                        '<input class="form-control text-center" placeholder="Authenticator Code" id="TwoFactorCode" />' +
                                        '<span class="invalid-feedback">2 Factor Authentication code is required</span>',
                                size: 'sm',
                                dismissOnConfirm: false,
                                showCallback: function($modal) {
                                    $modal.find('#TwoFactorCode').on('keyup', function(e) {
                                        if (e.keyCode === 13) {
                                            $modal.confirmButton.trigger('click');
                                        }
                                    });
                                },
                                shownCallback: function($modal) {
                                    $modal.find('#TwoFactorCode').focus();
                                },
                                confirmCallback: function($modal) {
                                    var code = $('#TwoFactorCode').removeClass('is-invalid').val();
                                    if (code) {
                                        postObj.code = code;
                                        $modal.disable();
                                        $.postJSON('/api/account/login2fa', postObj, function(twoFactorResponse) {
                                            if (twoFactorResponse) {
                                                if (twoFactorResponse.Success === true) {
                                                    $modal.modal('hide');
                                                    window.location.replace('/account/');
                                                } else {
                                                    postObj['2fa'] = twoFactorResponse.Id;
                                                    $.toast.danger(twoFactorResponse.Message);
                                                }
                                            } else {
                                                $.toast.danger('Failed to authenticate code. Please try again, or contact us for assistance.');
                                            }
                                        }).fail(function() {
                                            $.toast.danger("Failed to authenticate code. Please try again, or contact us for assistance.");
                                        }).always(function() {
                                            $modal.enable();
                                        });
                                    } else {
                                        $('#TwoFactorCode').addClass('is-invalid')
                                    }
                                }
                            });
                        } else {
                            window.location.replace('/account/');
                        }
                    } else {
                        $.toast.danger(response.Message);
                    }
                } else {
                    $.toast.danger("Could not log in. Please try again.");
                }
            }).fail(function() {
                $.toast.danger("Could not log in. Please try again.");
            }).always(function() {
                $('#UsernameInput, #PasswordInput, #LoginButton').prop('disabled', false);
                $button.html(btnText);
            });
        } else {
            if (!username) {
                $('#UsernameInput').addClass('is-invalid');
            }
            if (!password) {
                $('#PasswordInput').addClass('is-invalid');
            }
        }
    });

    $('#RegisterLink').on('click', function (e) {
        e.preventDefault();
        $.everywhere.confirm({
            title: 'New Account',
            body: '<div class="register-loader text-center h3 no-margin position-absolute w-100 h-100 p-4 text-console" style="display:none; background: rgba(0, 0, 0, 0.6); top: 0; left: 0;"><i class="fas fa-spinner fa-pulse"></i> Working...</div>' + 
                    '<div class="form" novalidate id="RegisterForm">'+
                        '<div class="form-group">' + 
                            '<label for="RegisterEmail">Email Address</label>' + 
                            '<input class="form-control" type="email" id="RegisterEmail" placeholder="awesome@developer.com" required />' + 
                            '<div class="invalid-feedback">Please provide a valid email address.</div>' + 
                            '<small class="form-text text-muted">#We\'ll never share your email with anyone else.</small>' + 
                        '</div>' +
                    '</div>',
            confirmText: 'Register',
            dismissOnConfirm: false,
            confirmCallback: function($modal, e) {
                var email = $('#RegisterEmail').removeClass('is-invalid').val();
                if (email && emailRegex.test(email)) {
                    $('.register-loader').fadeIn();
                    $modal.disable();
                    $.getJSON('/api/account/beginregister?email=' + email, function(response) {
                        if (response) {
                            $modal.modal('hide');
                            if (response.Success === true) {
                                if (response.Message) {
                                    $.everywhere.alert(response.Message);
                                } else {
                                    $.toast.success('Successfully registered. Check your email for your next step.');
                                }
                            } else {
                                $.everywhere.alert("Error: " + response.Message);
                            }
                        } else {
                            $.everywhere.alert("Could not complete registration at this time. Please try again later.");
                        }
                    }).fail(function() {
                        $.everywhere.alert("Could not complete registration at this time. Please try again later.");
                    }).always(function() {
                        $('.register-loader').fadeOut();
                        $modal.enable();
                    });
                } else {
                    $('#RegisterEmail').addClass('is-invalid');
                }
            },
            shownCallback: function($modal) {
                $modal.find('#RegisterEmail').focus().on('keyup', function(e) {
                    if (e.keyCode === 13) {
                        $modal.find('.everywhere-confirm').trigger('click');
                    }
                });
            }
        })
    });
});