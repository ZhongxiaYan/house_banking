<?php

function set_session($var, $val) {
    $_SESSION[$var] = $val;
}

function set_session_if_none($var, $val) {
    if (get_session($var) === null) {
        set_session($var, $val);
    }
}

// returns null if $var not set
function get_session($var) {
    if (isset($_SESSION[$var])) {
        return $_SESSION[$var];
    } else {
        return null;
    }
}

function unset_session($var) {
    if (isset($_SESSION[$var])) {
        unset($_SESSION[$var]);
    }
}

function pop_session($var) {
    $return = get_session($var);
    unset_session($var);
    return $return;
}

// clear everything except the log in variables
function clear_session() {
    global $SESSION_LOGIN_VARS;

    $keep_info = array();
    foreach ($_SESSION as $key => $value) {
        // keep the login variables
        if (array_key_exists($key, $SESSION_LOGIN_VARS)) {
            $keep_info[$key] = $value;
        }
    }
    session_unset();
    $_SESSION = array_merge($_SESSION, $keep_info);
}

function redirect($destination) {
    header('HTTP/1.1 303 See Other');
    header('Location: ' . $destination);
    exit;
}

function date_cmp($a, $b) {
    return strtotime($a['action_time']) <= strtotime($b['action_time']);
}

function e($string) {
    return htmlspecialchars($string);
}

function p($string) {
    echo htmlspecialchars($string);
}

function generate_random_word_comb() {
    global $CONFIG;
    $new_word = '';
    $lines = file($CONFIG['paths']['word_list']);
    for ($i = 0; $i < 5; $i++) {
        $rand = rand(0, 998);
        $new_word .= preg_replace('/\s+/S', "", $lines[$rand]);
    }
    return $new_word;
}

function email($users, $subject, $body, $altbody) {
    global $CONFIG;
    global $PAGES;
    require_once $PAGES['autoload'];
    $mail = new PHPMailer();
    // $mail->SMTPDebug = 3;

    $mail->isSMTP();
    $mail->Host = "smtp.gmail.com";
    $mail->SMTPAuth = true;
    $mail->Username = $CONFIG['mailer']['email'];
    $mail->Password = $CONFIG['mailer']['password'];
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom($CONFIG['mailer']['email'], 'Maid');
    foreach ($users as $user) {
        $mail->addAddress($user->email, $user->name);
    }

    $mail->isHTML(true);

    $mail->Subject = $subject;
    $mail->Body    = $body;
    $mail->AltBody = $altbody;

    if(!$mail->send()) {
        echo 'Message could not be sent.';
        echo 'Mailer Error: ' . $mail->ErrorInfo;
        return false;
    }
    return true;
}

?>