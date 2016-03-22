<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Main page</title>
</head>

<form method='post'>
</form>

<?php
function parseUrl($url)
{
	$pos = strpos($url, 'vk.com/');
	if ($pos===false) return false;
	$pos = strpos($url, 'wall');
	if ($pos===false) return false;
	else return	substr($url,$pos+4-strlen($url));
}
function prepareStringForReturn($value)
{
	$value	= str_replace('+','%2B',$value);
	$value	= str_replace(' ','%20',$value);
	$value	= str_replace('\n','%0a',$value);
	return $value;
}
//receive data from bot
$json = file_get_contents('php://input');
$action = json_decode($json, true);
$message	= $action['message']['text'];
$chat		= $action['message']['chat']['id'];
$token	= 'HIDDEN';
if (substr($message,0,14)=='/rp@vkReposter'||substr($message,0,3)=='/rp') 
{
	$postId	= parseUrl($message);	
	if ($postId===false) file_get_contents('https://api.telegram.org/bot'.$token.'/sendMessage?chat_id='.$chat.'&text=BadRequest');
	else //receive images from url
	{
		$json = file_get_contents('https://api.vk.com/method/wall.getById?posts='.$postId);
		$action = json_decode($json, true);
		file_get_contents('https://api.telegram.org/bot'.$token.'/sendMessage?chat_id='.$chat.'&text='.prepareStringForReturn($action['response'][0]['text']));
		$attachments	= $action['response'][0]['attachments'];
		for ($i=0;$i<count($attachments);$i++) {
			file_get_contents('https://api.telegram.org/bot'.$token.'/sendMessage?chat_id='.$chat.'&text='.$attachments[$i]['photo']['src_big']);
		}
		
	}
}
if ($message=='/help@vkReposterBot'||$message=='/help') 
{
	$AnswerText	= "Привет! Моя работа - пересылать изображения записи стены вконтакте.%0a1. Скопируйте ссылку на запись вконтакте%0a2. Впишите в поле ввода Telegram:%0a/rp %0a3. Вставьте скопированную ранее ссылку%0aДолжно получиться что то вроде этого:%0a/rp https://vk.com/stolbn?w=wall-35294456_2916950%0a4. Отправьте получившееся сообщение.%0aБот ответит вам серией картинок из данной записи.";
	file_get_contents('https://api.telegram.org/bot'.$token.'/sendMessage?chat_id='.$chat.'&text='.prepareStringForReturn($AnswerText));
}
	
if ($message=='/about@vkReposterBot'||$message=='/about') 
{
	$AnswerText	= "Developer Alexey Yurasov%0aformat37@gmail.com%0a@AlexMoscow";
	file_get_contents('https://api.telegram.org/bot'.$token.'/sendMessage?chat_id='.$chat.'&text='.prepareStringForReturn($AnswerText));
}
?>

</html>
