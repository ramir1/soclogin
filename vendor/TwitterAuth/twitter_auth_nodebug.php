<?php

session_start();

$auth = array_key_exists('auth', $_GET) ? true : false;
$denied = array_key_exists('denied', $_GET) ? true : false;

if ($auth && !$denied)
{
	define ('TWITTER_CONSUMER_KEY',		'O0IgnYHonW4KGL6pJr0YCQ');
	define ('TWITTER_CONSUMER_SECRET',	'OYUjBgJPl4yra3N32sSpSSVGboLSCo5pLGsky20VJE');
	define ('TWITTER_URL_CALLBACK',		'http://expange.ru/files/130/twitter_auth_nodebug.php?auth=1');
	
	include 'TwitterAuth.php';
	
	$TWAuth = new TwitterAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, TWITTER_URL_CALLBACK);
	
	$oauth_token = array_key_exists('oauth_token', $_GET) ? $_GET['oauth_token'] : false;
	$oauth_verifier = array_key_exists('oauth_verifier', $_GET) ? $_GET['oauth_verifier'] : false;
	
	
	if (!$oauth_token && !$oauth_verifier)
	{
		$TWAuth->request_token();
		$TWAuth->authorize();
	}
	else
	{
		// access_token и user_id
		$TWAuth->access_token($oauth_token, $oauth_verifier);
		
		// JSON-версия
		$user_data = $TWAuth->user_data();
		$user_data = json_decode($user_data);
		
		echo '<pre>User data<br>';
		print_r($user_data);
		echo '</pre>';
		
		// XML-версия
		// $user_data = $TWAuth->user_data('xml');
	}
}
else
{
	if ($denied)
	{
		echo '<p><strong>Было отказано в доверии приложению</strong></p>';
	}
	
	echo '<p><a href="twitter_auth_nodebug.php?auth=1">Начать авторизацию через Твиттер</a></p>';
	echo '<p>Скачать архив: <a href="TwitterAuth.rar">TwitterAuth.rar</a></p>';
}

?>