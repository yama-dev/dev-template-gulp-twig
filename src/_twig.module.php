<?php

// Set include dir.
$arrayIncDir = array(
  '/assets/inc',
  '/assets/_inc',
  '/assets/include',
  '/assets/_include',
  '/inc',
  '/_inc',
  '/include',
  '/_include'
);

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
        }
        else {
          if(time() - 10 >= filemtime($new_dir . "/" . $file)){
            copy($dir_name . "/" . $file, $new_dir . "/" . $file);
          }
        }
      }
      closedir($dh);
    }
  }
  return true;
}
foreach ($arrayIncDir as $item) {
  dir_copy('.'.$item, './vendor/data/html'.$item);
}

// Load our autoloader
require_once __DIR__.'/vendor/autoload.php';

// Specify our Twig templates location
$loader = new Twig_Loader_Filesystem(__DIR__.'/');
$loader->addPath('vendor/data/');

 // Instantiate our Twig
$twig = new Twig_Environment($loader, array(
  'debug' => true,
));
$twig->addGlobal('env', 'develop');

$json_path = './vendor/data/json/dummy.json';
$json = file_get_contents($json_path);
$jsonArray = json_decode($json, true);

$service = array(
  'path' => $json_path,
  'vendor_data_samplejson' => $jsonArray
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
$request_uri = $_SERVER['REQUEST_URI'];
$request_query = $_SERVER['QUERY_STRING'];
if( preg_match("/(\.html|\.php|\.css|\.js|\.pdf|\.xml|\.txt|\.json|\.jpg|\.jpeg|\.png|\.gif|\.svg)/", $request_uri) ){
  $request_uri_fix = $request_uri;
} else {
  $request_uri_fix = preg_replace("/(.*)\/.*$/", '$1/'.$CONFIG['default_file'], $request_uri);
}

if(!file_exists(__DIR__.$request_uri_fix)){
  header('HTTP/1.0 404 Not Found');
  exit;
}

if( !preg_match("/(\.php|\.css|\.js|\.pdf|\.xml|\.txt|\.json|\.jpg|\.jpeg|\.png|\.gif|\.svg)/", $request_uri) ){
  // Render our view.
  $document = $twig->render($request_uri_fix, ['app' => $app] );
  echo $document;
} else {
  // Render our file.
  $mine_type = get_minetype($request_uri_fix);
  header("Content-type: ".$mine_type."; charset=utf-8");
  include_once(__DIR__.$request_uri_fix);
}


function get_minetype($file){

  $filetype = preg_replace("/.*\/.*\.(.*)$/", '$1', $file);

  $minetype_data = array(
    'txt' => 'text/plain',
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
