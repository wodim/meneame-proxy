<?php

$start = microtime(true);

require('ezsql/shared/ez_sql_core.php');
require('ezsql/mysql/ez_sql_mysql.php');

$db = new ezSQL_mysql();

if (!@$db->quick_connect('mnm', 'mnm', 'mnm', 'localhost')) {
	header('HTTP/1.1 500 Internal Server Error');
	die('Error conectando a la BD local (el horror)');
}

$db->query('SET NAMES `utf8`');

// desactivado, pero puede servir para sólo permitir a bots de google el entrar.
// prefiero el control por ip, total para cuatro tontos que puedan entrar por 66.*,
// esto es lo único medio fiable, el user agent se puede cambiar.
// lo desactivo porque quiero que la gente entre desde google.
if (!preg_match('/^66\./', $_SERVER['REMOTE_ADDR']) && 1 == 0) {
	header(sprintf('Location: http://%s%s', 'www.meneame.net', $_SERVER['REQUEST_URI']));
	die;
}

function randmnm() {
	return sprintf('mnm%02d.vortigaunt.net', rand(0, 99));
}

// inyección, blablalba. Tal como está configurado en lighttpd, los únicos hosts posibles son
// mnm\d\d.vortig..., así es que no hay inyección (ni error del regex) posible. de estar mal configurado, sí la habría.
preg_match('/^mnm(\d\d)\.vortigaunt\.net$/', $_SERVER['HTTP_HOST'], $vhost);
$vhost = $vhost[1];
$uri = mysql_real_escape_string(substr($_SERVER['REQUEST_URI'], 0, 512));
$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? mysql_real_escape_string(substr($_SERVER['HTTP_USER_AGENT'], 0, 512)) : '';
$referer = isset($_SERVER['HTTP_REFERER']) ? mysql_real_escape_string(substr($_SERVER['HTTP_REFERER'], 0, 512)) : '';

// parar backend: evitar que voten, que saquen info de votos, que hagan peticiones ajax
// evitar también login y registros. quizás queda algo fuera, revisar
// tampoco permito peticiones que no sean GET. POST no puede traer nada bueno y HEAD no me interesa
if (preg_match('/^\/(backend|api|oauth)\/|^\/(login|register)\.php/', $_SERVER['REQUEST_URI'])
		|| $_SERVER['REQUEST_METHOD'] != 'GET') {
	header('HTTP/1.1 403 Ford Bidden');
	echo 'No.';
	die;
}

// permitir a meneame.net manejar el go.php en sí. Si no lo hago, hago de proxy ante la web meneada!
if (preg_match('/^\/go\.php/', $_SERVER['REQUEST_URI'])) {
	header('HTTP/1.1 302 Found');
	header(sprintf('Location: http://www.meneame.net%s', $_SERVER['REQUEST_URI']));
	die;
}

$handle = @fopen(sprintf('http://www.meneame.net%s', $_SERVER['REQUEST_URI']), 'rb');

if (!$handle) {
	header('HTTP/1.1 403 Ford Bidden');
	die('No va la cosa.');
}

$contents = '';
while (!feof($handle)) {
	$contents .= fread($handle, 8192);
}
fclose($handle);

// hacer que las peticiones salten entre los dominios.
function mnmcb($string) {
	return randmnm();
}

// no funciona aún para mnmstatic porque no devuelvo las cabeceras correctas en cuanto a content-type
// de todos modos, para qué quiero servir estáticos?
$contents = preg_replace_callback('/((www|e)\.)?meneame\.net/', 'mnmcb', $contents);

// desactiva el noarchive 
$contents = str_replace('<meta name="ROBOTS" content="NOARCHIVE" />', '<!-- no archive -->', $contents);
$contents = str_replace('<meta name="robots" content="noindex,follow" />', '<!-- no index, no follow, blabla -->', $contents);

// desactiva el nofollow
$contents = str_replace('rel="nofollow"', '', $contents);

// desactiva trackers y publicidad
$contents = str_replace('partner.googleadservices.com', '255.255.255.255', $contents);
$contents = str_replace('b.scorecardresearch.com', '255.255.255.255', $contents);
$contents = str_replace('s.src = (document.location.protocol == "https:" ? "https://sb" : "http://b") + ".scorecardresearch.com/beacon.js";', 's.src = "http://255.255.255.255/beacon.js";', $contents);	
$contents = str_replace("ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';", "ga.src =  'http://255.255.255.255/ga.js';", $contents);

$real = preg_match('<!--Delivered to you in (\d+\.\d\d\d) seconds-->', $contents, $matches);

if ($real) {
	$real_time = (float)$matches[1];
} else {
	$real_time = 0;
}

echo($contents);

$db->query(sprintf('INSERT INTO mnm.hits (vhost, uri, date, ip, user_agent, referer, time, real_time)
	VALUES (\'%s\', \'%s\', NOW(), \'%s\', \'%s\', \'%s\', \'%s\', \'%s\')',
	$vhost, $uri, $_SERVER['REMOTE_ADDR'], $user_agent, $referer, (float)(microtime(true) - $start), $real_time));
