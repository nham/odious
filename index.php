<?php

require_once('lib/glue.php');
require_once('lib/markdown.php');
require_once('lib/PasswordHash.php');
require_once('lib/util.php');

/* ROUTING */

$urls = array(
  '/odious/' => 'OdiousIndex',
  '/odious/index.php' => 'OdiousIndex',
  '/odious/(\d\d\d\d/\d\d/\d\d/[A-Za-z0-9\-]+)' => 'OdiousArticle',
  '/odious/admin/' => 'OdiousAdminIndex',
  '/odious/admin/setpw' => 'OdiousAdminSetPW',
  '/odious/admin/create' => 'OdiousAdminCreate',
  '/odious/admin/(\d\d\d\d/\d\d/\d\d/[A-Za-z0-9\-]+)/edit' => 'OdiousAdminEdit',
  '/odious/admin/(\d\d\d\d/\d\d/\d\d/[A-Za-z0-9\-]+)/delete' => 'OdiousAdminDelete',
  '/odious/404' => 'Odious404'
);


/* MODELS */

class Article {

  // "YYYY/MM/DD/post-title" -> "articles/YYYY-MM-DD-post-title.txt"
  private function descriptor2Filename($desc) {
    return 'articles/'.str_replace('/', '-', $desc) . ".txt";
  }

  // "YYYY-MM-DD-post-title.txt" -> "YYYY/MM/DD/post-title"
  function filename2Descriptor($fn) {
    return preg_replace('/(\d+)-(\d+)-(\d+)-([A-Za-z0-9\-]+)\.txt/', '$1/$2/$3/$4', $fn);
  }

  function formatPostFile($title, $timestamp, $body) {
    return "title: $title\ntimestamp: $timestamp\n\n$body";
  }


  public function createArticle($p) {
    global $timezone;
    $now = time() + $timezone;
    $date = date("Y/m/d/", $now);

    if($p['slug'] == "") {
      $p['slug'] = preg_replace('/[^A-Za-z0-9-]+/', '-', $p['title']);
    }

    $fn = $this->descriptor2Filename($date.$p['slug']);

    $postfile = $this->formatPostFile($p['title'], $now, $p['body']);
    if(file_put_contents($fn, $postfile)) {
      return true;
    } else {
      return false;
    }

  }

  public function editArticle($desc, $p) {
    $current = $this->getArticleInfo($desc);

    if($p['slug'] == "") {
      $p['slug'] = preg_replace('/[^A-Za-z0-9-]+/', '-', $p['title']);
    }

    $fn = $this->descriptor2Filename($desc);
    $postfile = $this->formatPostFile($p['title'], $current['timestamp'], $p['body']);

    if(file_put_contents($fn, $postfile)) {
      return true;
    } else {
      return false;
    }
  }

  public function deleteArticle($desc) {
    $fn = $this->descriptor2Filename($desc);
    if(unlink($fn)) {
      return true;
    } else {
      return false;
    }
  }
  

  private function parseArticle($desc) {    
    $fn = $this->descriptor2Filename($desc);
    if($f = fopen($fn, 'r')) {
      while((($line = rtrim(fgets($f))) !== false) && $line != "") {
	$arr = explode(": ", $line, 2);
        $article[$arr[0]] = $arr[1];
      }

      $article['body'] = stripslashes(fread($f, filesize($fn)));
      fclose($f);

      return $article;
    } else {
      return false;
    }
  }


  public function getArticle($descriptor) {
    if($article = $this->parseArticle($descriptor)) {
      $article['desc'] = $descriptor;
      return $article;

    } else {
      return false;
    }
  }

  // Returns article descriptor, title and date in an array
  public function getArticleInfo($descriptor) {
    $fn = $this->descriptor2Filename($descriptor);
      if($f = fopen($fn, 'r')) {
        $article['desc'] = $descriptor;

        // Relies on title and timestamp being the first two tags
	for($i = 0; $i < 2; $i++) {
          $arr = explode(": ", rtrim(fgets($f)), 2);
	  $article[$arr[0]] = $arr[1];
        }

        return $article;
      } else {
        return false;
      }
  }


  // Returns list of article descriptors, most recent first.
  public function listArticles() {
    if($pages = scandir("articles", 1)) {
      $pages = array_map(array($this, "filename2Descriptor"), $pages);
      return array_filter($pages, function($p) { return !preg_match('/^\.{1,2}$/', $p); });
    } else {
      return false;
    }
  }

}


class Password {
  var $pw;
  var $hasher;

  function __construct() {
    global $passfile;
    if(file_exists($passfile)) {
      $this->pw = file_get_contents($passfile);
    } else {
      $this->pw = "";
    }
    $this->hasher = new PasswordHash(8, false);
  }

  function passExists() {
    return ($this->pw != "");
  }

  function isValid($entered) {
    return $this->hasher->CheckPassword($entered, $this->pw);
  }

  function setPassword($new) {
    global $passfile;
    return file_put_contents($passfile, $this->hasher->HashPassword($new));
  }

}


/* Controller Classes */

class Odious404 extends Controller {
  function GET() {
    $this->layout_vars = array(
      'content' => new View('404', array()));

  }

}


class OdiousIndex extends Controller {
  function GET() {
    $A = new Article();

    if($article_list = $A->listArticles()) {
      $articles = array();

      // We fail silently here, but meh.
      for($i = 0; $i < count($article_list); $i++) {
        $articles[$i] = $A->getArticleInfo($article_list[$i]);
        $articles[$i]['timestamp'] = date("Y/m/d", $articles[$i]['timestamp']);
      }

      $data['articles'] = $articles;
      
      $this->layout_vars = array(
        'content' => new View('index', $data));
    }    
  }  
}

class OdiousArticle extends Controller {
  function GET($matches) {
    $A = new Article();

    if($article = $A->getArticle($matches[1])) {
      // Format date, apply markdown parser
      $article['body'] = markdown($article['body']);
      $article['timestamp'] = 'on '.date("F d, Y \a\\t H:i", (int) $article['timestamp']);

      $this->layout_vars = array(
        'content' => new View('article', array('article' => $article)));

    } else {
      $this->layout_vars = array(
        'content' => new View('404', array()));
    }
  }
}


/* ADMIN CONTROLLERS */

class OdiousAdminSetPW extends Controller {
  function GET() {
    $P = new Password();
    $data['pass_exists'] = $P->passExists();

    $this->layout_vars = array(
      'content' => new View('admin/setpw', $data));

  }

  function POST() {
    $P = new Password();
    $data['pass_exists'] = $P->passExists();

    if($data['pass_exists'] && !$P->isValid($_POST['password'])) {
      $this->layout_vars = array(
        'content' => "The password you entered is incorrect.");

      return;
    }

    if($_POST['newpass'] != $_POST['passconfirm']) {
      $data['confirm_failed'] = true;
      $this->layout_vars = array(
        'content' => new View('admin/setpw', $data));
      return;
    }

    if($P->setPassword($_POST['newpass'])) {
      $this->layout_vars = array(
        'content' => "Password set successfully. <a href=\"".site_page('admin/')."\">Go to index</a>");
    } else {
      $this->layout_vars = array(
        'content' => "Couldn't create the password file. Y'all need to check permissions.");
    }
  }
}


class OdiousAdminIndex extends Controller {
  function GET() {
    $A = new Article();

    if(($article_list = $A->listArticles()) !== false) {
      $articles = array();

      // We fail silently here, but meh.
      for($i = 0; $i < count($article_list); $i++) {
        $articles[$i] = $A->getArticleInfo($article_list[$i]);
        $articles[$i]['timestamp'] = date("Y/m/d", $articles[$i]['timestamp']);
      }

      $data['articles'] = $articles;

      $this->layout_vars = array(
        'content' => new View('admin/index', $data));
    }
  }
}

class OdiousAdminCreate extends Controller {
  function GET() {
    $this->layout_vars = array(
      'content' => new View('admin/create', array()));
  }

  function POST() {
    $P = new Password();

    if(!$P->isValid($_POST['password'])) {
      $this->layout_vars = array(
        'content' => "The password you entered is incorrect.");
    } else {
      $A = new Article();
      if($A->createArticle($_POST)) {
        $this->layout_vars = array(
          'content' => "Congratulations, you made a post. Everyone is so proud.");
      } else {
        $this->layout_vars = array(
	  'content' => "Couldn't write post. Fuuuuuu-");
      }
    }

  }
}

class OdiousAdminEdit extends Controller {
  function GET($matches) {
    $A = new Article();

    if($article = $A->getArticle($matches[1])) {
      $this->layout_vars = array(
        'content' => new View('admin/edit', array('article' => $article)));
    } else {
      $this->layout_vars = array(
        'content' => new View('404', array()));
    }
  }

  function POST($matches) {
    $P = new Password();

    if(!$P->isValid($_POST['password'])) {
      $this->layout_vars = array(
        'content' => "The password you entered is incorrect.");
    } else {
      $A = new Article();
      if($A->editArticle($matches[1], $_POST)) {
        $this->layout_vars = array(
          'content' => "Congratulations, you edited a post. Everyone is so proud.");
      } else {
        $this->layout_vars = array(
	  'content' => "Couldn't write post. Fuuuuuu-");
      }
    }

  }
}

class OdiousAdminDelete extends Controller {
  function GET() {
    $this->layout_vars = array(
      'content' => new View('admin/delete', array()));
  }

  function POST($matches) {
    $P = new Password();

    if(!$P->isValid($_POST['password'])) {
      $this->layout_vars = array(
        'content' => "The password you entered is incorrect.");
    } else {
      $A = new Article();
      if($A->deleteArticle($matches[1])) {
        $this->layout_vars = array(
          'content' => "Congratulations, you delete a post. Everyone is so proud.");
      } else {
        $this->layout_vars = array(
	  'content' => "Couldn't delete post. Fuuuuuu-");
      }
    }
  }
}


glue::stick($urls);


