<?php
/*!
 * DEV TEMPLATE GULP (Twig)
 * Version 0.3.7
 * Repository https://github.com/yama-dev/dev-template-gulp-twig
 * Copyright yama-dev
 * Licensed under the MIT license.
 */

$request_uri = $_SERVER['REQUEST_URI'];
$request_query = $_SERVER['QUERY_STRING'];

// error_log($request_uri."\n",3,"../log.html");

define('JSON_PATH', '/vendor/data/json/dummy.json');

// Copy include files.
function dir_copy($dir_name, $new_dir) {
  if (!is_dir($new_dir)) {
    mkdir($new_dir, 0777, true);
  }

  if (is_dir($dir_name)) {
    if ($dh = opendir($dir_name)) {
      while (($file = readdir($dh)) !== false) {
        if ($file == "." || $file == "..") {
          continue;
        }
        if (is_dir($dir_name . "/" . $file)) {
          dir_copy($dir_name . "/" . $file, $new_dir . "/" . $file);
        } else {
          if(time() - 10 >= @filemtime($new_dir . "/" . $file)){
            copy($dir_name . "/" . $file, $new_dir . "/" . $file);
          }
        }
      }
      closedir($dh);
    }
  }
  return true;
}

function getFileList($dir) {
  $files = array_filter(glob(rtrim($dir, '/') . '/*'), function($v) {
    if(strpos($v, 'vendor')){
      return false;
    } elseif(strpos($v, 'inc') || strpos($v, 'assets')){
      return $v;
    } else {
      return false;
    }
  });
  $list = array();
  foreach ($files as $file) {
    if (is_file($file) && preg_match('/(\.html|\.htm|\.ssi)/', $file) ) {
      $list[] = $file;
    }
    if (is_dir($file)) {
      $list = array_merge($list, getFileList($file));
    }
  }
  return $list;
}
$filelists = getFileList($_SERVER['DOCUMENT_ROOT']);
foreach ($filelists as $item) {
  $itemPre = str_replace($_SERVER['DOCUMENT_ROOT'], '', $item);
  $itemFix = pathinfo($itemPre,PATHINFO_DIRNAME);
  if(strlen($itemFix)>3){
    dir_copy($_SERVER['DOCUMENT_ROOT'].$itemFix, $_SERVER['DOCUMENT_ROOT'] . '/vendor/data/html'.$itemFix);
  }
}

// Load our autoloader
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

// Specify our Twig templates location
$loader = new Twig_Loader_Filesystem($_SERVER['DOCUMENT_ROOT'] . '/');
$loader->addPath($_SERVER['DOCUMENT_ROOT'] . '/vendor/data/');

 // Instantiate our Twig
$twig = new Twig_Environment($loader, array(
  'debug' => true,
));
$twig->addGlobal('env', 'develop');

$json_path = $_SERVER['DOCUMENT_ROOT'] . JSON_PATH;
if(file_exists($json_path)){
  $_json = file_get_contents($json_path);
  $jsonArray = json_decode($_json, true);
} else {
  $jsonArray = array('statu'=>'no json');
}

$service = array(
  'path' => $json_path,
  'json' => $jsonArray,
  'call' => $jsonArray
);
$twig->addGlobal('service', $service);

$twig->addExtension(new Twig_Extension_Debug());
$twig->addExtension(new Twig_Extension_StringLoader());

// Create app data.
$app = array();

$CONFIG = array(
  'default_file' => 'index.html'
);

// Set request uri.
if( preg_match("/(\.html|\.php|\.css|\.js|\.pdf|\.xml|\.txt|\.json|\.jpg|\.jpeg|\.png|\.gif|\.svg)/", $request_uri) ){
  // Remove Parameter.
  $request_uri_fix = preg_replace("/(\?.*$)/", '', $request_uri);
} else {
  $request_uri_fix = preg_replace("/(.*)\/.*$/", '$1/'.$CONFIG['default_file'], $request_uri);
}

// Not file.
if(!file_exists($_SERVER['DOCUMENT_ROOT'].$request_uri_fix)){
  header('HTTP/1.0 404 Not Found');
  exit;
}

// Render file.
if( !preg_match("/(\.php|\.css|\.js|\.pdf|\.xml|\.txt|\.json|\.jpg|\.jpeg|\.png|\.gif|\.svg)/", $request_uri) ){
  // Render our view.
  $document = $twig->render($request_uri_fix, ['app' => $app] );
  echo $document;
} else {
  // Render our file.
  $mine_type = get_minetype($request_uri_fix);
  header("Content-type: ".$mine_type."; charset=utf-8");
  readfile($_SERVER['DOCUMENT_ROOT'].$request_uri_fix);
}

function get_minetype($file){
  $filetype = preg_replace("/.*\/.*\.(.*)$/", '$1', $file);

  $minetype_data = array(
    'txt' => 'text/plain',
    'log' => 'text/plain',
    'htm' => 'text/html',
    'html' => 'text/html',
    'php' => 'text/html',
    'css' => 'text/css',
    'js' => 'application/javascript',
    'json' => 'application/json',
    'xml' => 'application/xml',
    'swf' => 'application/x-shockwave-flash',
    'flv' => 'video/x-flv',

    // images
    'png' => 'image/png',
    'jpe' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'jpg' => 'image/jpeg',
    'gif' => 'image/gif',
    'bmp' => 'image/bmp',
    'ico' => 'image/vnd.microsoft.icon',
    'tiff' => 'image/tiff',
    'tif' => 'image/tiff',
    'svg' => 'image/svg+xml',
    'svgz' => 'image/svg+xml',

    // archives
    'zip' => 'application/zip',
    'rar' => 'application/x-rar-compressed',
    'exe' => 'application/x-msdownload',
    'msi' => 'application/x-msdownload',
    'cab' => 'application/vnd.ms-cab-compressed',

    // audio/video
    'mp3' => 'audio/mpeg',
    'qt' => 'video/quicktime',
    'mov' => 'video/quicktime',

    // adobe
    'pdf' => 'application/pdf',
    'psd' => 'image/vnd.adobe.photoshop',
    'ai' => 'application/postscript',
    'eps' => 'application/postscript',
    'ps' => 'application/postscript',

    // ms office
    'doc' => 'application/msword',
    'rtf' => 'application/rtf',
    'xls' => 'application/vnd.ms-excel',
    'ppt' => 'application/vnd.ms-powerpoint'
  );

  foreach ($minetype_data as $key => $value) {
    if($key == $filetype){
      return $value;
      break;
    }
  }
}
