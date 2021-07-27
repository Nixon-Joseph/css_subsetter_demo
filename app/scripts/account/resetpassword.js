$(function() {
    $('#PasswordVisibilityToggle').on('click', function () {
        $('.password-input:not(:visible)').val($('.password-input:visible').val());
        $('.password-input').toggle();
        $('#NewPasswordLabel').attr('for', $('.password-input:visible').attr('id'));
        $(this).find('i').toggleClass('fa-eye fa-eye-slash');
    });

    $('.password-input').on('keyup', function (e) {
        if (e.keyCode === 13) {
            $('#ResetPasswordButton').trigger('click');
        }
    });

    $('#ResetPasswordButton').on('click', function () {
        $('#EmailAddress, .password-input').removeClass('is-invalid');
        var email = $('#EmailAddress').val(),
            password = $('.password-input:visible').val(),
            valid = true;
        if (!email || $.helpers.constants.emailRegex.test(email) === false) {
            $('#EmailAddress').addClass('is-invalid');
            valid = false;
        }
        if (!password) {
            $('.password-input').addClass('is-invalid');
            valid = false;
        }
        if (valid === true) {
            var $button = $(this),
                oldButtonText = $button.html(),
                resetCode = $(this).data('reset-code');
            $button.html('<i class="fas fa-spinner fa-pulse"></i> Resetting...').prop('disabled', true);
            $('#EmailAddress').prop('disabled', true);
            $('.password-input').prop('disabled', true);
            $.postJSON('/api/account/resetpassword', {
                email: email,
                password: password,
                code: resetCode
            }, function (response) {
                if (response) {
                    if (response.Success) {
                        $.everywhere.alert({
                            body: '<p>Your password has been successfully reset!</p><p>Please <a href="/account/login" class="text-console-secondary">Log in</a> to continue.</p>',
                            hiddenCallback: function () {
                                window.location = '/account/login';
                            }
                        });
                    } else {
                        $.toast.danger('Error: ' + response.Message);
                    }
                } else {
                    $.toast.danger('Failed to reset password. Please try again, or contact us for assistance.');
                }
            }).fail(function() {
                $.toast.danger('Failed to reset password. Please try again, or contact us for assistance.');
            }).always(function() {
                $button.html(oldButtonText).prop('disabled', false);
                $('#EmailAddress').prop('disabled', false);
                $('.password-input').prop('disabled', false);
            });
        }
    });
});