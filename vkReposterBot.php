<?php
define('BOT_TOKEN', 'CHANGE_THIS');
define('API_URL', 'https://api.telegram.org/bot'.BOT_TOKEN.'/');

class curl
{
    private $curl_obj;
    public function __construct()
    {
        if(!function_exists('curl_init'))
        {
            echo 'ERROR: Install CURL module for php';
            exit();
        }
        $this->init();
    }
    public function init()
    {
        $this->curl_obj = curl_init();
    }
    public function request($url, $method = 'GET', $params = array(), $opts = array())
    {
        $method = trim(strtoupper($method));
        // default opts
        $opts[CURLOPT_FOLLOWLOCATION] = true;
        $opts[CURLOPT_RETURNTRANSFER] = 1;
        $opts[CURLOPT_SSL_VERIFYPEER] = true;
        //http://curl.haxx.se/docs/caextract.html
        $opts[CURLOPT_CAINFO] = "cacert.pem";
        if($method==='GET')
	{
		$url .= "?".$params;
		$params = http_build_query($params);
	}
        elseif($method==='POST')
        {
            $opts[CURLOPT_POST] = 1;
            $opts[CURLOPT_POSTFIELDS] = $params;
        }
        $opts[CURLOPT_URL] = $url;
	curl_setopt_array($this->curl_obj, $opts);
        $content = curl_exec($this->curl_obj);
        if ($content===false) echo 'Ошибка curl: ' . curl_error($this->curl_obj);
        return $content;
    }
    public function close()
    {
        if(gettype($this->curl_obj) === 'resource')
            curl_close($this->curl_obj);
    }
    public function __destruct()
    {
        $this->close();
    }
}

function collect_file($fileurl){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $fileurl);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_AUTOREFERER, false);
		curl_setopt($ch, CURLOPT_REFERER, "http://www.xcontest.org");
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$result = curl_exec($ch);
		curl_close($ch);
		return($result);
}

function write_to_file($text,$new_filename){
		$fp = fopen($new_filename, 'w');
		fwrite($fp, $text);
		fclose($fp);
}

function sendPhoto($fileurl,$c,$chat_id)
{
		$method = 'sendPhoto';
		$filename = "images/".uniqid().".png";
		$temp_file_contents = collect_file($fileurl);
		write_to_file($temp_file_contents,$filename);

		if(class_exists('CURLFile')) $cfile = new CURLFile($filename);
		else $cfile = "@".$filename;
		$params = array
		(
			'chat_id' => $chat_id,
			'photo' => $cfile,
			'reply_to_message_id' => null,
			'reply_markup' => null
		);
		$r = $c->request(API_URL.$method, 'POST', $params);
		$j = json_decode($r, true);
		if($j) print_r($j);
		else echo $r;
		unlink($filename);
}

function sendLocalPhoto($filename,$c,$chat_id)
{
		$method = 'sendPhoto';
		/*$filename = "images/".uniqid().".png";
		$temp_file_contents = collect_file($fileurl);
		write_to_file($temp_file_contents,$filename);*/

		if(class_exists('CURLFile')) $cfile = new CURLFile($filename);
		else $cfile = "@".$filename;
		$params = array
		(
			'chat_id' => $chat_id,
			'photo' => $cfile,
			'reply_to_message_id' => null,
			'reply_markup' => null
		);
		$r = $c->request(API_URL.$method, 'POST', $params);
		$j = json_decode($r, true);
		if($j) print_r($j);
		else echo $r;
		//unlink($filename);
}

//VkParser
function parseUrl($url,$usePrefix=true)
{
	if ($usePrefix)
	{
			$pos = strpos($url, 'vk.com/');
			if ($pos===false) return false;
	}
	$pos = strpos($url, 'wall');
	if ($pos===false) return false;
	else return	substr($url,$pos+4-strlen($url));
}
//HELLOBOT
function apiRequestWebhook($method, $parameters) {
  if (!is_string($method)) {
    error_log("Method name must be a string\n");
    return false;
  }

  if (!$parameters) {
    $parameters = array();
  } else if (!is_array($parameters)) {
    error_log("Parameters must be an array\n");
    return false;
  }

  $parameters["method"] = $method;

  header("Content-Type: application/json");
  echo json_encode($parameters);
  return true;
}

function exec_curl_request($handle) {
  curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, true);
  //http://curl.haxx.se/docs/caextract.html
  curl_setopt($handle, CURLOPT_CAINFO, "cacert.pem");
  $response = curl_exec($handle);
  //$content = curl_exec($this->curl_obj);
        //if ($content===false) echo 'Ошибка curl: ' . curl_error($this->curl_obj);
  if ($response === false) {
    $errno = curl_errno($handle);
    $error = curl_error($handle);
    //file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id=106129214&text='.curl_error($this->curl_obj));
    echo 'Ошибка curl: ' . curl_error($this->curl_obj);
    error_log("Curl returned error $errno: $error\n");
    curl_close($handle);
    return false;
  }

  $http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
  curl_close($handle);

  if ($http_code >= 500) {
    // do not wat to DDOS server if something goes wrong
    sleep(10);
    return false;
  } else if ($http_code != 200) {
    $response = json_decode($response, true);
    error_log("Request has failed with error {$response['error_code']}: {$response['description']}\n");
    if ($http_code == 401) {
      throw new Exception('Invalid access token provided');
    }
    return false;
  } else {
    $response = json_decode($response, true);
    if (isset($response['description'])) {
      error_log("Request was successfull: {$response['description']}\n");
    }
    $response = $response['result'];
  }

  return $response;
}

function apiRequest($method, $parameters) {
  if (!is_string($method)) {
    error_log("Method name must be a string\n");
    return false;
  }

  if (!$parameters) {
    $parameters = array();
  } else if (!is_array($parameters)) {
    error_log("Parameters must be an array\n");
    return false;
  }

  foreach ($parameters as $key => &$val) {
    // encoding to JSON array parameters, for example reply_markup
    if (!is_numeric($val) && !is_string($val)) {
      $val = json_encode($val);
    }
  }
  $url = API_URL.$method.'?'.http_build_query($parameters);

  $handle = curl_init($url);
  curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($handle, CURLOPT_TIMEOUT, 60);

  return exec_curl_request($handle);
}

function apiRequestJson($method, $parameters) {
  if (!is_string($method)) {
    error_log("Method name must be a string\n");
    return false;
  }

  if (!$parameters) {
    $parameters = array();
  } else if (!is_array($parameters)) {
    error_log("Parameters must be an array\n");
    return false;
  }

  $parameters["method"] = $method;

  $handle = curl_init(API_URL);
  curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($handle, CURLOPT_TIMEOUT, 60);
  curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($parameters));
  curl_setopt($handle, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));

  return exec_curl_request($handle);
}

function processMessage($message) {
  // process incoming message
  $message_id = $message['message_id'];
  $chat_id = $message['chat']['id'];
  if (isset($message['text'])) {
    // incoming text message
    $text = $message['text'];

    if (strpos($text, "/start") === 0) {
      apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Hello', 'reply_markup' => array(
        'keyboard' => array(array('Hello', 'Hi')),
        'one_time_keyboard' => true,
        'resize_keyboard' => true)));
    } else if ($text === "Hello" || $text === "Hi") {
      apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'Nice to meet you'));
    } else if (strpos($text, "/stop") === 0) {
      // stop now
    } else {
      apiRequestWebhook("sendMessage", array('chat_id' => $chat_id, "reply_to_message_id" => $message_id, "text" => 'Cool'));
    }
  } else {
    apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'I understand only text messages'));
  }
}

function addInlineResult($resultId,$title,$message_text)
{
	global $results;
	$input_message_content=array("message_text"=>$message_text);
	$results[]=array(
            "type" => "article",
            "id" => $resultId,
            "title" => $title,
            //"message_text" => $message_text
            "input_message_content"=>$input_message_content
          );
    return $results;
}
//START
$content = file_get_contents("php://input");
$update = json_decode($content, true);
if (!$update) exit;
//PERSONAL MESSAGE
if (isset($update["message"]))
{
		//processMessage($update["message"]);
		$chat_id	= $update["message"]['chat']['id'];
		$c = new curl();
		$text	= $update["message"]['text'];
		if (substr($text,0,14)=='/rp@vkReposter'||substr($text,0,3)=='/rp')
		{
			$postId	= parseUrl($text);
			if ($postId===false) file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat_id.'&text=bad request');
			else //receive images from url
			{
				$json = file_get_contents('https://api.vk.com/method/wall.getById?posts='.$postId);
				$action = json_decode($json, true);
				$topic	= str_replace("<br>","%0A",$action['response'][0]['text']);
				file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat_id.'&text='.$topic);
				$attachments	= $action['response'][0]['attachments'];
				for ($i=0;$i<count($attachments);$i++) {
					sendPhoto($attachments[$i]['photo']['src_big'],$c,$chat_id);
				}
			}
		}
		if ($text=='/example@vkReposterBot'||$text=="/example")
		{
			if ($chat_id!=$update["message"]['from']['id'])	//sent from group
			{
				file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat_id.'&text=The response sent to personal chat');
				$chat_id	= $update["message"]['from']['id'];
			}
			sendLocalPhoto("images/vkReposterExample0.png",$c,$chat_id);
			sendLocalPhoto("images/vkReposterExample1.png",$c,$chat_id);
			file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat_id.'&text=Then, @vkReposterBot sends the post title and a series of his images');
			sendLocalPhoto("images/vkReposterExample2.png",$c,$chat_id);
		}

		if ($text=='/help@vkReposterBot'||$text=='/help')
		{
			if ($chat_id!=$update["message"]['from']['id'])	//sent from group
			{
				file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat_id.'&text=The response sent to personal chat');
				$chat_id	= $update["message"]['from']['id'];
			}
			$AnswerText	= "Привет! Моя работа - пересылать изображения записи стены вконтакте.%0a1. Скопируйте ссылку на запись вконтакте%0a2. Впишите в поле ввода Telegram:%0a/rp %0a3. Вставьте скопированную ранее ссылку%0aДолжно получиться что то вроде этого:%0a/rp https://vk.com/stolbn?w=wall-35294456_2916950%0a4. Отправьте получившееся сообщение.%0aБот ответит вам серией картинок из данной записи.";
			file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat_id.'&text='.$AnswerText);
		}


		/*if (substr($text,0,17)=='/group@vkReposter'||$text=="/group") file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat_id.'&text='.$chat_id);
		$vkpos	= strpos($text, 'vk.com/');
		$spacepos	= strpos($text, ' ');
		file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat_id.'&text='.strpos($url, 'vk.com/'));//.$vkpos.";".$spacepos.";".substr($test,$spacepos));
		//message from personal chat, for redirect to grou[
		if ($vkpos!=FALSE&&$spacepos!=FALSE&&$vkpos>$spacepos)
		{
			$chat_id	= substr($text,$spacepos);
			file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat_id.'&text='.$text);
		}*/

		//else file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat_id.'&text=cmd not found');
}
//QUERY FROM USER
if (isset($update["inline_query"])) {
    $inlineQuery = $update["inline_query"];
    $queryId = $inlineQuery["id"];
    $text = $inlineQuery["query"];
    //file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat_id.'&text=userid: '.$inlineQuery["from"]["id"]);

    if (isset($text) && $text !== "") {
    $results = array();
    //Get vk content
			$postId	= parseUrl($text,false);
			if ($postId===false) addInlineResult("1","vk content not found","wrong query");
			else //receive images from url
			{
				$json = file_get_contents('https://api.vk.com/method/wall.getById?posts='.$postId);
				$action = json_decode($json, true);
				$message_text=$action['response'][0]['text'];
				$attachments	= $action['response'][0]['attachments'];
				addInlineResult("2","send ".count($attachments)." photo with topict",$message_text);
				addInlineResult("3","send ".count($attachments)." photo without topict","vk");
			}
      apiRequestJson
       (
       "answerInlineQuery", array(
        "inline_query_id" => $queryId,
        "results" => $results,
        //"cache_time" => 86400,
        "cache_time" => 2,
       )
      );
    }
}
//CHOSEN INLINE RESULT
if (isset($update["chosen_inline_result"])) {
	//now disabled, because chat_id still uavialable in chosen_inline_result
	$chosen_inline_result=$update["chosen_inline_result"];
	//file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat_id."&text=chat: ".$chosen_inline_result["result_id"]);
	//file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/sendMessage?chat_id='.$chat_id."&text=link: ".$chosen_inline_result["query"]);
}
?>
