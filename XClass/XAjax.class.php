<?php
	class XAjax
	{
		public static function pseudoGet($asArray = true)
		{
			return json_decode($_GET['j'], $asArray);
		}

		public static function get($asArray = true) 
		{
			global $HTTP_RAW_POST_DATA;
			return json_decode($HTTP_RAW_POST_DATA, $asArray);
		}

		public static function send($obj) 
		{
			header('content-Type: application/json;utf8');
			echo json_encode($obj);
		}
	}
?>