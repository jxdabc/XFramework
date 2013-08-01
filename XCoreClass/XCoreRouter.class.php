<?php
	class XCoreRouterException extends XCoreException {}
	class XCoreRouter 
	{
		public static function go()
		{
			if (!array_key_exists('PATH_INFO', $_SERVER) || !$_SERVER['PATH_INFO']) 
				$path_info = ltrim(XCoreConfig::getDefaultAction(), '/');
			else			
				$path_info = ltrim($_SERVER['PATH_INFO'], '/');
			$path_info = preg_split('/\//', $path_info);

			if (count($path_info) != 2)
				self::goErrorPageAndDie(404, 'Page not found. (path format error)');
			else
			{
				$controller = $path_info[0];
				$controller = strtolower($controller);
				$controller = ucwords($controller);
				$controller = 'CTRL' . $controller;

				$action = $path_info[1];
				$action = strtolower($action);
			}

			// Do NOT use auto-loaded class for security. 
			$targetFile = 'Controller/' . $controller . '.ctrl.php';
			if (!is_file($targetFile))
				self::goErrorPageAndDie(404, 'Page not found. (controller not found)');
			require_once ($targetFile);

			$controller = new $controller();
			if (!method_exists($controller, $action))
				self::goErrorPageAndDie(404, 'Page not found. (action not found)');
			call_user_func_array(array($controller, $action), array());
		}
		
		public static function goErrorPageAndDie($code, $error)
		{
			$pages = XCoreConfig::getErrorPages();
			
			$errorMessage = $error instanceof Exception ? 
				get_class($error) . '::' . $error->getMessage() : $error;
			$stack = $error instanceof Exception ?
				$error->getTraceAsString() : '';
				
			if ($stack == '') 
			{
				$backtrace = debug_backtrace();
				foreach ($backtrace as $no => $item)
				{
					$file = array_key_exists('file', $item) ? basename($item['file']) : '';
					$line = array_key_exists('line', $item) ? '('.$item['line'].')' : '';
					$class = array_key_exists('class', $item) ? $item['class'].'::' : '';
					$fun = array_key_exists('function', $item) ? $item['function'] : '';
					
					$stack .= "#{$no} {$file}{$line} {$class}{$fun}\n";
				}
			}
				
			header("HTTP/1.1 {$code} ERROR");
			
			if (array_key_exists($code, $pages)) 
			{
				$view = new XCoreView($pages[$code]);
				$view->errorCode = $code;
				$view->errorMessage = $errorMessage;
				$view->stack = $stack;
				$view->debug = XCoreConfig::isDebug();
				
				$view->output();
				die();
			}
			else
			{   
				$output = <<<OUTPUT
<pre>
XFramework!

ERROR: {$errorMessage}({$code})
STACK: 
{$stack}
</pre>
OUTPUT;
				die($output);
			}
		}
	}
?>