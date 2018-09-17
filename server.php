<?php
$host = 'localhost'; //host
$port = '9001'; //port
$null = NULL; //null var

//Create TCP/IP sream socket
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
//reuseable port
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);

//bind socket to specified host
socket_bind($socket, 0, $port);

//listen to port
socket_listen($socket);

//create & add listning socket to the list
$clients = array($socket);
$room_array = array();

//MYSQL connection
// $servername = "accessfeedback.com";
// $username = "feedback";
// $password = "876a3cfb61cdd7172f68b97319981a60";
// $dbname = "feedback";

// Create connection
// $conn = new mysqli($servername, $username, $password, $dbname);
// // Check connection
// if ($conn->connect_error) {
//     die("Connection failed: " . $conn->connect_error);
// } 





//start endless loop, so that our script doesn't stop
while (true) {
	//manage multipal connections
	$changed = $clients;
	//returns the socket resources in $changed array
	socket_select($changed, $null, $null, 0, 10);
	
	//check for new socket
	if (in_array($socket, $changed)) {
		$socket_new = socket_accept($socket); //accpet new socket


		 //add socket to client array
		
		$header = socket_read($socket_new, 1024); //read data sent by the socket
		foreach(preg_split("/((\r?\n)|(\r\n?))/", $header) as $line){
    // do stuff with $line
			$url_pices=explode(" ", $line);
			$url=explode(" ", $url_pices[1])[0];
			$room_id_pices=explode("/", $url);
			$room_id=end($room_id_pices);
			
			    break;
} 

  $clients[] = $socket_new;
  $user_array[$socket_new][]=$room_id;
  $room_array[$room_id][] = $socket_new;
    
		perform_handshaking($header, $socket_new, $host, $port); //perform websocket handshake
		
		socket_getpeername($socket_new, $ip); //get ip address of connected socket
		$response = mask(json_encode(array(
		'type'=>0,
	    'message'=>$ip.' connected',
	    'msg_type'=>1


	      ))); //prepare json data
		send_message($response,$room_id); //notify all users about new connection
		
		//make room for new socket
		$found_socket = array_search($socket, $changed);
		unset($changed[$found_socket]);
	}
	
	//loop through all connected sockets
	foreach ($changed as $changed_socket) {	
		
		//check for any incomming data
		while(socket_recv($changed_socket, $buf, 1024, 0) >= 1)
		{
			$received_text = unmask($buf); //unmask data
			$tst_msg = json_decode($received_text); //json decode 
			$user_id = $tst_msg->user_id; //sender name
			$user_message = $tst_msg->message; //message text
			$room_id = $tst_msg->room_id; //color
			$msg_type = $tst_msg->msg_type; //color
// 			if($msg_type!=0){

// 							$sql = "INSERT INTO f_feedback_group_chat (feedback_group_id, user_id,msg_type, message,created)
// VALUES ('".$room_id ."', '".$user_id."','".$msg_type."', '".addslashes($user_message)."','".date('Y-m-d H:i:s')."')";

//  echo $sql;

// if ($conn->query($sql) === TRUE) {
//     echo "New record created successfully\n";
// } else {
// 	$conn = new mysqli($servername, $username, $password, $dbname);
// 	if ($conn->query($sql) === TRUE) {
//     echo "New record created successfully\n";
// }
//     echo "Error: " . $sql . "\n" . $conn->error;
// }
// 			}

			
			//prepare data to be sent to client
			$response_text = mask(json_encode(array(
				 'type'=>1,
				 'user_id'=>$user_id, 
				 'message'=>$user_message,
				 'room_id'=>$room_id,
				 'msg_type'=>$msg_type
				)));
			send_message($response_text,$room_id); //send data
			break 2; //exist this loop
		}
		
		$buf = @socket_read($changed_socket, 1024, PHP_NORMAL_READ);
		if ($buf === false) { // check disconnected client
			// remove client for $clients array
			$found_socket = array_search($changed_socket, $clients);
		    
			socket_getpeername($changed_socket, $ip);
			unset($clients[$found_socket]);
			$user_room=$user_array[$changed_socket];
			for ($ink=0; $ink < count($user_room); $ink++) { 
		         unset($room_array[$user_room[$ink]][$changed_socket]);
			}
			$user_array[$changed_socket]=array();
			//notify all users about disconnected connection
			$response = mask(json_encode(array('type'=>0, 'message'=>$ip.' disconnected','msg_type'=>1)));
			for ($ink=0; $ink < count($user_room); $ink++) { 
		        send_message($response,$user_room[$ink]); 
			}
			
		}
	}
}
// close the listening socket
socket_close($socket);

function send_message($msg,$room_id)
{
	global $clients;
	global $room_array;
	
   // print_r($clients[$room_id]);
	foreach($room_array[$room_id] as $changed_socket)
	{
		@socket_write($changed_socket,$msg,strlen($msg));
	}
	return true;
}


//Unmask incoming framed message
function unmask($text) {
	$length = ord($text[1]) & 127;
	if($length == 126) {
		$masks = substr($text, 4, 4);
		$data = substr($text, 8);
	}
	elseif($length == 127) {
		$masks = substr($text, 10, 4);
		$data = substr($text, 14);
	}
	else {
		$masks = substr($text, 2, 4);
		$data = substr($text, 6);
	}
	$text = "";
	for ($i = 0; $i < strlen($data); ++$i) {
		$text .= $data[$i] ^ $masks[$i%4];
	}
	return $text;
}

//Encode message for transfer to client.
function mask($text)
{
	$b1 = 0x80 | (0x1 & 0x0f);
	$length = strlen($text);
	
	if($length <= 125)
		$header = pack('CC', $b1, $length);
	elseif($length > 125 && $length < 65536)
		$header = pack('CCn', $b1, 126, $length);
	elseif($length >= 65536)
		$header = pack('CCNN', $b1, 127, $length);
	return $header.$text;
}

//handshake new client.
function perform_handshaking($receved_header,$client_conn, $host, $port)
{
	$headers = array();
	$lines = preg_split("/\r\n/", $receved_header);
	foreach($lines as $line)
	{
		$line = chop($line);
		if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
		{
			$headers[$matches[1]] = $matches[2];
		}
	}

	$secKey = $headers['Sec-WebSocket-Key'];
	$secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
	//hand shaking header
	$upgrade  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
	"Upgrade: websocket\r\n" .
	"Connection: Upgrade\r\n" .
	"WebSocket-Origin: $host\r\n" .
	"WebSocket-Location: ws://$host:$port/demo/shout.php\r\n".
	"Sec-WebSocket-Accept:$secAccept\r\n\r\n";
	socket_write($client_conn,$upgrade,strlen($upgrade));
}
