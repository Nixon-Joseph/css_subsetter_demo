$(function() {
    $('#PasswordVisibilityToggle').on('click', function () {
        $('.password-input:not(:visible)').val($('.password-input:visible').val());
        $('.password-input').toggle();
        $('#NewPasswordLabel').attr('for', $('.password-input:visible').attr('id'));
        $(this).find('i').toggleClass('fa-eye fa-eye-slash');
    });

    $('.create-form-input').enterListener(function() {
        $('#RegisterButton').trigger('click');
    });

    $('#RegisterButton').on('click', function () {
        $('.create-form-input').removeClass('is-invalid');
        var email = $('#EmailAddress').val().trim(),
            username = $('#Username').val().trim(),
            password = $('.password-input:visible').val(),
            valid = true;
        if (!email || $.helpers.constants.emailRegex.test(email) === false) {
            $('#EmailAddress').addClass('is-invalid');
            valid = false;
        }
        if (!username || /[\W]/.test(username) === true) {
            $('#Username').addClass('is-invalid');
            valid = false;
        }
        if (!password) {
            $('.password-input').addClass('is-invalid');
            valid = false;
        }
        if (valid === true) {
            var $button = $(this),
                oldButtonText = $button.html(),
                registrationCode = $(this).data('registration-code');
            $button.html('<i class="fas fa-spinner fa-pulse"></i> Creating Account...').prop('disabled', true);
            $('.create-form-input').prop('disabled', true);
            $.postJSON('/api/account/FinishRegister', {
                username: username,
                email: email,
                password: password,
                code: registrationCode
            }, function (response) {
                if (response) {
                    if (response.Success) {
                        $.everywhere.alert({
                            body: '<p>Your account was created successfully!</p><p>Please <a href="/account/login" class="text-console-secondary">Log in</a> to continue.</p>',
                            hiddenCallback: function () {
                                window.location = '/account/login';
                            }
                        });
                    } else {
                        $.toast.danger('Error: ' + response.Message);
                    }
                } else {
                    $.toast.danger('Could not finish creating your account at this time. Please try again, or contact us for assistance.');
                }
            }).fail(function() {
                $.toast.danger('Could not finish creating your account at this time. Please try again, or contact us for assistance.');
            }).always(function() {
                $button.html(oldButtonText).prop('disabled', false);
                $('.create-form-input').prop('disabled', false);
            });
        }
    });
});