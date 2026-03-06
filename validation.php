<?php 
function validate_input($input){
    $input = trim($input);
    $input = stripslashes($input);
    $input = strip_tags($input);
    $input = htmlspecialchars($input);
    return $input;
}
function validate_username($username) {
    return strlen($username) > 2;
}
function validate_email1($email){
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validate_phone($phone)
{
    if (!isset($phone) || empty($phone)) {
        return false;
    }

    $digits = preg_replace('/\D/', '', $phone);
    return preg_match('/^(010|011|012|015)[0-9]{8}$/', $digits) === 1;
}


function validatePassword($pass) {
    if (empty($pass)) {
        return false;
    }
    return strlen($pass) >= 8;
}

function validateConfirmPassword($pass, $confirmPass) {
    if (empty($confirmPass)) {
        return false;
    }
    return $pass === $confirmPass;
}
?>