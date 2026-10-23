<?php
$crdate = date("Y-m-d");
$crtime = date("H:i:s");
$crdatetime = date("Y-m-d H:i:s");
$today = date("d-m-Y");

define('SALT_LENGTH', 9); // salt for password


    // Password and salt generation
    function PwdHash($pwd, $salt = null)
    {
        if ($salt === null)     {
            $salt = substr(md5(uniqid(rand(), true)), 0, SALT_LENGTH);
        }
        else     {
            $salt = substr($salt, 0, SALT_LENGTH);
        }
        return $salt . sha1($pwd . $salt);
    }


function getCurrentPageURLWithoutFilename() {
    // Check if HTTPS is used
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";

    // Get the host
    $host = $_SERVER['HTTP_HOST'];

    // Get the current URI
    $uri = $_SERVER['REQUEST_URI'];

    // Remove the file name and extension from the URI
    $uri_without_filename = preg_replace('/\/[^\/]+\.[a-zA-Z0-9]+$/', '', $uri);

    // Combine the protocol, host, and modified URI to form the current page URL without filename and extension
    $current_page_url_without_filename = $protocol . "://" . $host . $uri_without_filename;

    return $current_page_url_without_filename;
}

function removeWordFromString($string, $wordToRemove) {
    // Find the position of the word in the string
    $pos = strpos($string, $wordToRemove);

    // If the word is not found, return the original string
    if ($pos === false) {
        return $string;
    }

    // Calculate the length of the word
    $wordLength = strlen($wordToRemove);

    // Remove the word from the string using substr
    return substr($string, 0, $pos) . substr($string, $pos + $wordLength);
}

?>