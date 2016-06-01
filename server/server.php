#!/usr/bin/env php
<?php 
include "lib/phpsockets.io.php";

/**
* The port to run this socket on
*/ 
$server_port="2000";

//initialize socket service
$socket=new PHPWebSockets("0.0.0.0",$server_port);

/**
* when a client is connect, send the client its own autogenerated id via emit
* 
*   @param  object  $socket    The socket object of the current client
*   @param  int  $uid		   The user id of the current client
* 
*/
$socket->on("connect",function($socket,$uid) {
	$socket->emit('connect',$uid);
});

/**
* a callback to handle the command "add user" from a client
* this is used by the advanced example to process a user login
* 
*   @param  object  $socket    The socket object of the current client
*   @param  int  $username	   The username of the current client
*   @param  int  $userid	   The user id of the current client
* 
*/
$socket->on("add user",function($socket,$username,$userid) {
	$socket->username=$username;
	
	//add the client's username to the global list
	$socket->usernames["$username"] = $username;
	$socket->socketID["$userid"] = $username;
	$socket->numUsers++;
	
	//inform me that my login was successful
	$socket->emit('login', array(
	'numUsers'=>$socket->numUsers
	));

	//broadcast to others that i have joined
	$socket->broadcast('user joined', array(
	'username'=>$socket->username,
	'numUsers'=>$socket->numUsers
	));
	
	$socket->addedUser=true;

});


/**
* a callback to broadcast typing message to other connected users (other than the current client who is typing)
* this is used by the advanced example to handle a message being typed
* 
*   @param  object  $socket    The socket object of the current client
*   @param  string  $data	   Data sent along with callback
* 
*/
$socket->on('typing', function ($socket,$data) {

	$socket->broadcast('typing', array(
	'username'=>$socket->socketID[$socket->user->id],
	));
	
});

/**
* a callback to broadcast when client is no longer typing 
* this is used by the advanced example to handle a message being typed
* 
*   @param  object  $socket    The socket object of the current client
*   @param  string  $data	   Data sent along with callback
* 
*/
$socket->on('stop typing', function ($socket,$data) {

	$socket->broadcast('stop typing', array(
	'username'=>$socket->socketID[$socket->user->id],
	));
	
});

/**
* a callback to broadcast when client broadcasts a chat message
* this is used by the basic example to handle a chat message being sent
* 
*   @param  object  $socket    The socket object of the current client
*   @param  string  $data	   Data sent along with callback
*   @param  string  $sender	   The client sending the message
* 
*/
$socket->on('chat message', function ($socket,$data,$sender) {
	$socket->broadcast('chat message', $data,true); 
});


/**
* a callback to broadcast when client broadcasts a "new message"
* this is used by the advanced example to handle a chat message being sent
* 
*   @param  object  $socket    The socket object of the current client
*   @param  string  $data	   Data sent along with callback
*   @param  string  $sender	   The client sending the message
* 
*/
$socket->on('new message', function ($socket,$data,$sender) {
	// we tell the client to execute 'new message'
	$socket->broadcast('new message', array(
	'username'=>$socket->socketID[$socket->user->id],
	'message'=>$data
	));
	
});

/**
* a callback to handle disconnection of a client from its socket
* 
*   @param  object  $socket    The socket object of the current client
*   @param  string  $data	   Data sent along with callback
* 
*/
$socket->on("disconnect",function($socket,$data) {
	// remove the username from global usernames list
	if ($socket->addedUser) {
		unset($socket->usernames[$socket->username]);
		unset($socket->socketID[$socket->user->id]);
		$socket->numUsers--;

		// echo globally that this client has left
		$socket->broadcast('user left', array(
		'username'=> $socket->username,
		'numUsers'=> $socket->numUsers
		));
		
	}

});

//instantiate and start handling transactions
$socket->listen();
?>