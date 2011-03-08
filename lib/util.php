<?php

$passfile = 'pw.txt';
$timezone = -6;

function site_page($page) {
  $site_url = str_replace("index.php", "", 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF']);
  return $site_url . $page;
}

class View {
  function __construct($name, $data) {
    $this->name = $name;
    $this->data = $data;
  }

  // Stolen from TweetMVC v0.8.1
  function __toString() {
    ob_start();
    extract((array) $this->data);
    require("views/$this->name.php");
    return ob_get_clean();
  }
}

class Controller {
  function __construct() { }
  function before() { }

  function after() {
    echo new View('layout', $this->layout_vars);
  }
}