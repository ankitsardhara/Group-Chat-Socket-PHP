<!DOCTYPE html>
<html>
<title>Chat via PHP socket</title>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style type="text/css">

.panel{

	
margin-right: 3px;
}

.button {
    background-color: #28a2a2;
    border: none;
    color: white;
   float: right;
    text-decoration: none;
    display: block;
    font-size: 16px;
    cursor: pointer;
	width:30%;
    height:40px;
	margin-top: 5px;
	 border-radius: 5px;
}
input[type=text],input[type=number]{
		width:100%;
		margin-top:5px;
		line-height: 25px;
		border-radius: 5px;
		
	}


.chat_wrapper {
	width: 400px	;
	height:494px;
	margin-right: auto;
	margin-left: auto;
	background: #00e4e4;
	border: 1px solid #999999;
	padding: 10px;
	font: 14px 'lucida grande',tahoma,verdana,arial,sans-serif;
	border-radius: 5px;}
.chat_wrapper .message_box {
	background: #F7F7F7;
	height:350px;
    overflow: auto;
	padding: 10px 10px 20px 10px;
	border: 1px solid #999999;
}
.chat_wrapper  input{
	//padding: 2px 2px 2px 5px;
}
.system_msg{
	color: #BDBDBD;
	font-size: 10px;
	font-family: monospace;
	text-align: center;

	
}
.user_name{
font-size: 10px;
color: #88B6E0;
}
.user_message{
font-size: 12px;
	
}


@media only screen and (max-width: 720px) {
    /* For mobile phones: */
    .chat_wrapper {
        width: 95%;
	height: 40%;
	}
    

	.button{ width:100%;
	margin-right:auto;   
	margin-left:auto;
	height:40px;
}
	
	
	
	
	
				
}

</style>
</head>
<body>	
<?php 
$colours = array('007AFF','FF7000','FF7000','15E25F','CFC700','CFC700','CF1100','CF00BE','F00');
$user_colour = array_rand($colours);
if($_GET['room_id']==""){
	$room_id="1";
}else{
	$room_id=$_GET['room_id'];
}

?>


<script src="jquery-3.1.1.js"></script>


<script language="javascript" type="text/javascript" version="1.1">  
$(document).ready(function(){
	//create a new WebSocket object.
	var wsUri = "ws://localhost:9001/room/<?php echo $room_id; ?>"; 	
	websocket = new WebSocket(wsUri); 
	
	websocket.onopen = function(ev) { // connection is open 
		$('#message_box').append("<div class=\"system_msg\">Connected!</div>"); //notify user
	}

	$('#send-btn').click(function(){ //use clicks message send button	
		var mymessage = $('#message').val(); //get message text
		var user_id = $('#name').val(); //get user name
		
		if(user_id == ""){ //empty name?
			alert("Enter your Name please!");
			return;
		}
		if(mymessage == ""){ //emtpy message?
			alert("Enter Some message Please!");
			return;
		}
		document.getElementById("name").style.visibility = "hidden";
		
		var objDiv = document.getElementById("message_box");
		objDiv.scrollTop = objDiv.scrollHeight;
		//prepare json data
		var msg = {
		message: mymessage,
		user_id: user_id,
		msg_type :1,
		room_id: <?php echo $room_id; ?>
		};
		//convert and send data to server
		console.log("Send : ")
		console.log(JSON.stringify(msg));
		websocket.send(JSON.stringify(msg));
	});
	
	//#### Message received from server?
	websocket.onmessage = function(ev) {
		var msg = JSON.parse(ev.data); //PHP sends Json data
		console.log("Received : ")
		console.log(msg);
		var type = msg.type; //message type
		var umsg = msg.message; //message text
		var user_id = msg.user_id; //user name
		var msg_type = msg.msg_type; //color
	
       if(msg_type!=0){
       	if(type == '1') 
		{
			var myname = $('#name').val(); //get user name
					if(myname!=user_id){
			$('#message_box').append("<div style='text-align: right'><span class=\"user_name\"   > UserID: "+user_id+"</span> <br> <span class=\"user_message\">"+umsg+"</span></div>");
		     }else{
		     	$('#message_box').append("<div><span class=\"user_name\"   >Me</span> <br> <span class=\"user_message\">"+umsg+"</span></div>");
		     }
		}
		if(type == '0')
		{
			$('#message_box').append("<div class=\"system_msg\">"+umsg+"</div>");
		}
		$('#message').val(''); //reset text
		
		var objDiv = document.getElementById("message_box");
		objDiv.scrollTop = objDiv.scrollHeight;
	}else{
		var myname = $('#name').val(); //get user name
		if(myname!=user_id){
			$("#typing").remove(); 
			$('#message_box').append("<div id='typing' style='text-align: right' ><span class=\"user_name\" > UserID: "+user_id+"</span> <br> <span class=\"user_message\">"+umsg+"</span></div>");
		     setTimeout(function(){ $("#typing").remove(); }, 100);
		}
		
	}
		
		
		
	};
	
	websocket.onerror	= function(ev){$('#message_box').append("<div class=\"system_error\">Error Occurred - "+ev.data+"</div>");}; 
	websocket.onclose 	= function(ev){$('#message_box').append("<div class=\"system_msg\">Connection Closed</div>");}; 
});




</script>
<div class="chat_wrapper">
<div class="message_box" id="message_box"></div>
<div class="panel">
<input type="number" name="name" id="name" placeholder="Your Name" maxlength="15" />

<input type="text" name="message" id="message" placeholder="Message" maxlength="80" 
onkeydown = "ontypeing(event)"  />





</div>

<button id="send-btn" class=button>Send</button>

</div>

</body>
<script type="text/javascript">
	
	function ontypeing(event){
		if (event.keyCode == 13){
			document.getElementById('send-btn').click();
		}else{
		var user_id = $('#name').val(); //get user name
		var msg = {
		message: "Typing...",
		user_id: user_id,
		msg_type :0,
		room_id: <?php echo $room_id; ?>
		};
		//convert and send data to server
		websocket.send(JSON.stringify(msg));
	}

}
</script>
</html>