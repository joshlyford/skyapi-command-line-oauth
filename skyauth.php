<?php
// Register an OAuth app at
// https://developer.okta.com/signup/

$configs = [
    'Ip' =>'127.0.0.1',
    'Port' => '8080'
];

if(file_exists('config.json')){
    $string = file_get_contents("config.json");
    $json_a = json_decode($string, true);
    $configs = array_merge($configs, $json_a);
} else {
    echo "Please create a config.json file from the config.example.json file\n";
    die();
}

print_r($configs);

$redirect_uri = 'http://'.$configs['Ip'].':'.$configs['Port'].'/authorization-code/callback';
$socket_str = 'tcp://'.$configs['Ip'].':'.$configs['Port'];


$state = bin2hex(random_bytes(5));

$authorize_url ='https://oauth2.sky.blackbaud.com/authorization?'.http_build_query([
  'response_type' => 'code',
  'client_id' => $configs['ClientId'],
  'redirect_uri' => $redirect_uri,
  'state' => $state,
]);

echo "Open the following URL in a browser to continue\n";
echo $authorize_url."\n";
shell_exec("open '".$authorize_url."'");

// Start the mini HTTP server and wait for their browser to hit the redirect URL
// Store the query string parameters in a variable
$auth = startHttpServer($socket_str);

if($auth['state'] != $state) {
  echo "Wrong 'state' parameter returned\n";
  exit(2);
}

$code = $auth['code'];

echo "Received code=$code state=$state\n";

echo "Getting an access token...\n\n";
$response = http('https://oauth2.sky.blackbaud.com/token', [
  'grant_type' => 'authorization_code',
  'code' => $code,
  'redirect_uri' => $redirect_uri,
  'client_id' => $configs['ClientId'],
  'client_secret' => $configs['ClientSecret'],
]);

if(!isset($response->access_token)) {
  echo "Error fetching access token\n";
  exit(2);
}

$access_token = $response->access_token;
echo "Access Token:\n". $response->access_token."\n\n";
echo "Refresh Token: ". $response->refresh_token."\n\n";
echo "Environment Name: ". $response->environment_name."\n\n";
echo "Environment Id: ". $response->environment_id."\n\n";
echo "User Email: ". $response->email."\n\n";

// You have reached the end
die();



function startHttpServer($socketStr) {
  // Adapted from http://cweiske.de/shpub.htm

  $responseOk = "HTTP/1.0 200 OK\r\n"
    . "Content-Type: text/plain\r\n"
    . "\r\n"
    . "Ok. You may close this tab and return to the shell.\r\n";
  $responseErr = "HTTP/1.0 400 Bad Request\r\n"
    . "Content-Type: text/plain\r\n"
    . "\r\n"
    . "Bad Request\r\n";

  ini_set('default_socket_timeout', 60 * 5);

  $server = stream_socket_server($socketStr, $errno, $errstr);

  if(!$server) {
    Log::err('Error starting HTTP server');
    return false;
  }

  do {
    $sock = stream_socket_accept($server);
    if(!$sock) {
      Log::err('Error accepting socket connection');
      exit(1);
    }
    $headers = [];
    $body    = null;
    $content_length = 0;
    //read request headers
    while(false !== ($line = trim(fgets($sock)))) {
      if('' === $line) {
        break;
      }
      $regex = '#^Content-Length:\s*([[:digit:]]+)\s*$#i';
      if(preg_match($regex, $line, $matches)) {
        $content_length = (int)$matches[1];
      }
      $headers[] = $line;
    }
    // read content/body
    if($content_length > 0) {
      $body = fread($sock, $content_length);
    }
    // send response
    list($method, $url, $httpver) = explode(' ', $headers[0]);
    if($method == 'GET') {
      #echo "Redirected to $url\n";
      $parts = parse_url($url);
      #print_r($parts);
      if(isset($parts['path']) && $parts['path'] == '/authorization-code/callback'
        && isset($parts['query'])
      ) {
        parse_str($parts['query'], $query);
        if(isset($query['code']) && isset($query['state'])) {
          fwrite($sock, $responseOk);
          fclose($sock);
          return $query;
        }
      }
    }
    fwrite($sock, $responseErr);
    fclose($sock);
  } while (true);
}

function http($url, $params=false) {
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  if($params)
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
  return json_decode(curl_exec($ch));
}
