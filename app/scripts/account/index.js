$(function() {
    function passwordConfirmModal(confirmCallback, title, buttonText) {
        $.everywhere.confirm({
            title: title,
            body: '<div class="form-group"><label for="PasswordConfirm">Password</label><input type="password" id="PasswordConfirm" class="form-control" /><span class="invalid-feedback">Password is required</span></div>',
            confirmText: buttonText,
            dismissOnConfirm: false,
            size: 'sm',
            shownCallback: function($modal, e) {
                $('#PasswordConfirm').focus().on('keyup', function(e) {
                    if (e.keyCode === 13) {
                        $modal.confirmButton.trigger("click");
                    }
                });
            },
            confirmCallback: confirmCallback
        });
    }

    function handleEnableTwoFactor() {
        $.everywhere.confirm({
            title: "Enable 2FA",
            body: '<div class="text-center text-console h1 p-3"><i class="fas fa-spinner fa-pulse"></i> Loading...</div>',
            confirmText: 'Enable',
            dismissOnConfirm: false,
            shownCallback: function($modal, e) {
                $modal.disable();
                $.getJSON('/api/account/get2factorsetup', function(response) {
                    if (response) {
                        if (response.Success === true) {
                            $modal.find('.modal-body')
                                .addClass("text-center")
                                .html('<img src="' + response.Message + '" />' + 
                                    '<p class="text-center mt-2">Scan this code with your authenticator app, and enter the code provided below.</p>' +
                                    '<input type="tel" class="form-control text-center" id="TwoFactorCode" placeholder="Authenticator code" />' +
                                    '<span class="invalid-feedback">Please provide the code generated from your Authenticator app</span>');
                            $('#TwoFactorCode').on('keyup', function(e) {
                                if (e.keyCode === 13) {
                                    $modal.confirmButton.trigger('click');
                                }
                            }).focus();
                        } else {
                            $modal.modal('hide');
                            $.toast.danger(response.Message);
                        }
                    } else {
                        $modal.modal('hide');
                        $.toast.danger("Unable to request 2FA at this time. Please try again, or contact us for assistance.");
                    }
                }).always(function() {
                    $modal.enable();
                });
            },
            confirmCallback: function($modal, e) {
                var code = $('#TwoFactorCode').removeClass('is-invalid').val();
                if (code) {
                    $modal.disable();
                    $.getJSON('/api/account/enable2factorsetup/' + code, function(response) {
                        if (response) {
                            if (response.Success === true) {
                                $modal.modal('hide');
                                $.toast.success("Successfully set up 2 Factor Authentication!");
                                window.location.reload();
                            } else {
                                $.toast.danger(response.Message);
                            }
                        } else {
                            $.toast.danger("Unable to set up 2 factor at this time. Please try again, or contact us for assistance.");
                        }
                    }).always(function() {
                        $modal.enable();
                    });
                } else {
                    $('#TwoFactorCode').addClass('is-invalid');
                }
            }
        });
    }

    $('#Enable2FALink').on('click', function(e) {
        e.preventDefault();
        passwordConfirmModal(function($modal, e) {
            var password = $('#PasswordConfirm').removeClass('is-invalid').val();
            if (password) {
                $modal.disable();
                $.postJSON('/api/account/verifypassword', { password: password }, function(response) {
                    if (response) {
                        if (response.Success === true) {
                            $modal.modal('hide');
                            handleEnableTwoFactor();
                        } else {
                            $.toast.danger(response.Message);
                        }
                    } else {
                        $.toast.danger("Failed to verify password. Please try again, or contact us for assistance.");
                    }
                }).always(function() {
                    $modal.enable();
                });
            } else {
                $('#PasswordConfirm').addClass('is-invalid');
            }
        }, "Enable 2FA", "Enable");
    });
    $('#Disable2FALink').on('click', function(e) {
        e.preventDefault();
        passwordConfirmModal(function($modal, e) {
            var password = $('#PasswordConfirm').removeClass('is-invalid').val();
            if (password) {
                $modal.disable();
                $.postJSON('/api/account/disable2factor', { password: password }, function(response) {
                    if (response) {
                        if (response.Success === true) {
                            $.toast.success("Successfully disabled 2 Factor Authentication.");
                            window.location.reload();
                            $modal.modal('hide');
                        } else {
                            $.toast.danger(response.Message);
                        }
                    } else {
                        $.toast.danger("Unable to request 2FA at this time. Please try again, or contact us for assistance.");
                    }
                }).always(function() {
                    $modal.enable();
                });
            } else {
                $('#PasswordConfirm').addClass('is-invalid');
            }
        }, "Disable 2FA", "Disable");
    });
});