jQuery(function($) {
    var $createAccountCheckbox = $('#createaccount');
    var $createAccountFields = $('.create-account-fields');

    $createAccountCheckbox.on('change', function() {
        $createAccountFields.slideToggle(this.checked);
    });

    // Validación en tiempo real de las contraseñas
    var $password = $('#account_password');
    var $confirmPassword = $('#account_password_confirm');

    function validatePasswords() {
        if ($password.val() !== $confirmPassword.val()) {
            $confirmPassword[0].setCustomValidity('Las contraseñas no coinciden');
        } else {
            $confirmPassword[0].setCustomValidity('');
        }
    }

    $password.on('change', validatePasswords);
    $confirmPassword.on('keyup', validatePasswords);
}); 