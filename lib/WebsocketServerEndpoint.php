<?php
    /*
    $host = 'localhost'; //host
    $port = '9000'; //port
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

    //start endless loop, so that our script doesn't stop
    while (true) {
        //manage multipal connections
        $changed = $clients;
        //returns the socket resources in $changed array
        socket_select($changed, $null, $null, 0, 10);

        //check for new socket
        if (in_array($socket, $changed)) {
            $socket_new = socket_accept($socket); //accpet new socket
            $clients[] = $socket_new; //add socket to client array

            $header = socket_read($socket_new, 1024); //read data sent by the socket
            perform_handshaking($header, $socket_new, $host, $port); //perform websocket handshake

            socket_getpeername($socket_new, $ip); //get ip address of connected socket
            $response = mask(json_encode(array('type'=>'system', 'message'=>$ip.' connected'))); //prepare json data
            send_message($response); //notify all users about new connection

            //make room for new socket
            $found_socket = array_search($socket, $changed);
            unset($changed[$found_socket]);
        }

        //loop through all connected sockets
        foreach ($changed as $changed_socket) {

            //check for any incomming data
            while(socket_recv($changed_socket, $buf, 1024, 0) >= 1) {
                $received_text = unmask($buf); //unmask data
                $tst_msg = json_decode($received_text); //json decode
                $user_name = $tst_msg->name; //sender name
                $user_message = $tst_msg->message; //message text

                //prepare data to be sent to client
                $response_text = mask(json_encode(array('type'=>'usermsg', 'name'=>$user_name, 'message'=>$user_message)));
                send_message($response_text); //send data
                break 2; //exist this loop
            }

            $buf = @socket_read($changed_socket, 1024, PHP_NORMAL_READ);
            if ($buf === false) { // check disconnected client
                // remove client for $clients array
                $found_socket = array_search($changed_socket, $clients);
                socket_getpeername($changed_socket, $ip);
                unset($clients[$found_socket]);

                //notify all users about disconnected connection
                $response = mask(json_encode(array('type'=>'system', 'message'=>$ip.' disconnected')));
                send_message($response);
            }
        }
    }
    // close the listening socket
    socket_close($sock);

    function send_message($msg) {
        global $clients;
        foreach($clients as $changed_socket) {
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
    function mask($text) {
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
    function perform_handshaking($receved_header,$client_conn, $host, $port) {
        $headers = array();
        $lines = preg_split("/\r\n/", $receved_header);
        foreach($lines as $line) {
            $line = chop($line);
            if(preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
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
    */
?>

<?php  /*  >php -q server.php  */
    error_reporting(E_ALL);
    set_time_limit(0);
    ob_implicit_flush();

    $master  = WebSocket("localhost",12345);
    $sockets = array($master);
    $users   = array();
    $debug   = false;

    while(true){
      $changed = $sockets;
      socket_select($changed,$write=NULL,$except=NULL,NULL);
      foreach($changed as $socket) {
        if($socket==$master) {
          $client=socket_accept($master);
          if($client<0) {
            console("socket_accept() failed");
            continue;
          } else {
            connect($client);
          }
        } else {
          $bytes = @socket_recv($socket,$buffer,2048,0);
          if($bytes==0){ disconnect($socket); }
          else {
            $user = getuserbysocket($socket);
            if(!$user->handshake) {
                dohandshake($user,$buffer);
            } else {
                process($user,$buffer);
            }
          }
        }
      }
    }

    //---------------------------------------------------------------
    function process($user,$msg){
      $action = unwrap($msg);
      say("< ".$action);
      switch($action){
        case "hello" : send($user->socket,"hello human");                       break;
        case "hi"    : send($user->socket,"zup human");                         break;
        case "name"  : send($user->socket,"my name is Multivac, silly I know"); break;
        case "age"   : send($user->socket,"I am older than time itself");       break;
        case "date"  : send($user->socket,"today is ".date("Y.m.d"));           break;
        case "time"  : send($user->socket,"server time is ".date("H:i:s"));     break;
        case "thanks": send($user->socket,"you're welcome");                    break;
        case "bye"   : send($user->socket,"bye");                               break;
        default      : send($user->socket,$action." not understood");           break;
      }
    }

    function send($client,$msg){
      say("> ".$msg);
      $msg = wrap($msg);
      socket_write($client,$msg,strlen($msg));
    }

    function WebSocket($address,$port){
      $master=socket_create(AF_INET, SOCK_STREAM, SOL_TCP)     or die("socket_create() failed");
      socket_set_option($master, SOL_SOCKET, SO_REUSEADDR, 1)  or die("socket_option() failed");
      socket_bind($master, $address, $port)                    or die("socket_bind() failed");
      socket_listen($master,20)                                or die("socket_listen() failed");
      echo "Server Started : ".date('Y-m-d H:i:s')."\n";
      echo "Master socket  : ".$master."\n";
      echo "Listening on   : ".$address." port ".$port."\n\n";
      return $master;
    }

    function connect($socket){
      global $sockets,$users;
      $user = new User();
      $user->id = uniqid();
      $user->socket = $socket;
      array_push($users,$user);
      array_push($sockets,$socket);
      console($socket." CONNECTED!");
    }

    function disconnect($socket){
      global $sockets,$users;
      $found=null;
      $n=count($users);
      for($i=0;$i<$n;$i++){
        if($users[$i]->socket==$socket){ $found=$i; break; }
      }
      if(!is_null($found)){ array_splice($users,$found,1); }
      $index = array_search($socket,$sockets);
      socket_close($socket);
      console($socket." DISCONNECTED!");
      if($index>=0){ array_splice($sockets,$index,1); }
    }

    function dohandshake($user,$buffer){
      console("\nRequesting handshake...");
      console($buffer);
      list($resource,$host,$origin,$strkey1,$strkey2,$data) = getheaders($buffer);
      console("Handshaking...");

      $pattern = '/[^\d]*/';
      $replacement = '';
      $numkey1 = preg_replace($pattern, $replacement, $strkey1);
      $numkey2 = preg_replace($pattern, $replacement, $strkey2);

      $pattern = '/[^ ]*/';
      $replacement = '';
      $spaces1 = strlen(preg_replace($pattern, $replacement, $strkey1));
      $spaces2 = strlen(preg_replace($pattern, $replacement, $strkey2));

      if ($spaces1 == 0 || $spaces2 == 0 || $numkey1 % $spaces1 != 0 || $numkey2 % $spaces2 != 0) {
            socket_close($user->socket);
            console('failed');
            return false;
      }

      $ctx = hash_init('md5');
      hash_update($ctx, pack("N", $numkey1/$spaces1));
      hash_update($ctx, pack("N", $numkey2/$spaces2));
      hash_update($ctx, $data);
      $hash_data = hash_final($ctx,true);

      $upgrade  = "HTTP/1.1 101 WebSocket Protocol Handshake\r\n" .
                  "Upgrade: WebSocket\r\n" .
                  "Connection: Upgrade\r\n" .
                  "Sec-WebSocket-Origin: " . $origin . "\r\n" .
                  "Sec-WebSocket-Location: ws://" . $host . $resource . "\r\n" .
                  "\r\n" .
                  $hash_data;

      socket_write($user->socket,$upgrade.chr(0),strlen($upgrade.chr(0)));
      $user->handshake=true;
      console($upgrade);
      console("Done handshaking...");
      return true;
    }

    function getheaders($req){
      $r=$h=$o=null;
      if(preg_match("/GET (.*) HTTP/"   ,$req,$match)){ $r=$match[1]; }
      if(preg_match("/Host: (.*)\r\n/"  ,$req,$match)){ $h=$match[1]; }
      if(preg_match("/Origin: (.*)\r\n/",$req,$match)){ $o=$match[1]; }
      if(preg_match("/Sec-WebSocket-Key2: (.*)\r\n/",$req,$match)){ $key2=$match[1]; }
      if(preg_match("/Sec-WebSocket-Key1: (.*)\r\n/",$req,$match)){ $key1=$match[1]; }
      if(preg_match("/\r\n(.*?)\$/",$req,$match)){ $data=$match[1]; }
      return array($r,$h,$o,$key1,$key2,$data);
    }

    function getuserbysocket($socket){
      global $users;
      $found=null;
      foreach($users as $user){
        if($user->socket==$socket){ $found=$user; break; }
      }
      return $found;
    }

    function     say($msg=""){ echo $msg."\n"; }
    function    wrap($msg=""){ return chr(0).$msg.chr(255); }
    function  unwrap($msg=""){ return substr($msg,1,strlen($msg)-2); }
    function console($msg=""){ global $debug; if($debug){ echo $msg."\n"; } }

    class User{
      var $id;
      var $socket;
      var $handshake;
    }

?>