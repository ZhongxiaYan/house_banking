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

?>