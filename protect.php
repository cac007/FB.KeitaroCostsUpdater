<?php
# https://gist.github.com/4692807
namespace Protect;
# Will protect a page with a simple password. The user will only need
# to input the password once. After that their session will be enough
# to get them in. The optional scope allows access on one page to
# grant access on another page. If not specified then it only grants
# access to the current page.
function with($form, $password, $scope=null) {
  if( !$scope ) $scope = current_url();
  $session_key = 'password_protect_'.preg_replace('/\W+/', '_', $scope);
  session_start();
  # Check the POST for access
  if(isset($_POST['password']) && $_POST['password'] == $password ) {
    $_SESSION[$session_key] = true;
    redirect(current_url());
  }
  # If user has access then simply return so original page can render.
  if(isset($_SESSION[$session_key])&& $_SESSION[$session_key] ) return;
  require $form;
  exit;
}
#### PRIVATE ####
function current_url($script_only=false) {
	if (!isset($_SERVER['HTTPS']))
		$prefix='http://';
	else
		$prefix = $_SERVER['HTTPS'] ? 'https://' : 'http://';
	$servaddr = $_SERVER['SERVER_NAME']=='_'? $_SERVER['SERVER_ADDR']:$_SERVER['SERVER_NAME'];
  $path = $script_only ? $_SERVER['SCRIPT_NAME'] : $_SERVER['REQUEST_URI'];
  return "$prefix$servaddr$path";
}
function redirect($url) {
  header("Location: $url");
  exit;
}