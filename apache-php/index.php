<?php
require_once dirname(__FILE__).'/ChatServer.php';
$chatServer=new ChatServer();

// dump chat cache
if(isset($_GET['dump'])) {
	echo '<pre>';
	var_dump($chatServer->getAllMessages());
}
// clear chat cache
else if(isset($_GET['clear'])) {
	$chatServer->clearCache();
}
// invalid request
else if($_SERVER['REQUEST_METHOD']!=='POST') {
	// No dispatching
}
// post message
else if(isset($_POST['m'], $_POST['u'])) {
	$chatServer->postMessage($_POST['m'], $_POST['u']);
}
// no token given
else if(!isset($_POST['t'])) {
	header('HTTP/1.1 400 Bad Request');
}
// main handling
else {
	$newMessages=$chatServer->pollNewMessages($_POST['t']);
	
	header('Content-Type: application/json');
	echo json_encode($newMessages);
}
