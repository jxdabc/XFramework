<?php
	class XHttpClientException extends XException {}
?>
<?php
	class XHttpClient
	{
		public function __construct($host)
		{
			$this->host = $host;
			$this->cookies = array();
			$this->context = array();
			$this->context['http'] = array();
			$this->httpOptions = & $this->context['http'];

			$this->httpOptions['protocol_version'] = '1.1';

			$this->responsedHeader = '';
			$this->responsedContent = '';
		}

		function setCookies($cookieArray)
		{
			$this->cookies = $cookieArray;
		}

		public function get($path, $data = '')
		{
			$this->httpOptions['method'] = 'GET';
			$this->httpOptions['content'] = '';

			$this->buildRequestHeader();
			
			if ($data == '')  $this->doQuery($this->host . $path);
			else  $this->doQuery($this->host . $path . '?' . $data);
		}

		function post($path, $data = '')
		{
			if($data == '')
				throw new XHttpClientException("NO DATA WHEN POST. METHOD: POST, URL: {$this->host}{$path}, CONTENT:{$data}");

			$this->httpOptions['method'] = 'POST';

			if (is_array($data))
			{
				$this->httpOptions['content'] = json_encode($data);
				$this->buildRequestHeader('application/json');
			}
			else
			{
				$this->httpOptions['content'] = $data;
				$this->buildRequestHeader('text/plain');
			}
			

			$this->buildRequestHeader(true);

			$this->doQuery($this->host . $path);
		}

		public function getContent()
		{
			return $this->responsedContent;
		}

		public function getAllHeaders()
		{
			return $this->responsedHeader;
		}


		private function buildRequestHeader($contentType = NULL)
		{
			$header = array();

			$header[] = 'Connection: close';
			$header[] = 'User-Agent: XHttpClient/3.0.0';
			if($contentType != NULL)
				$header[] = 'Content-Type: ' . $contentType;

			//Cookies here. 
			$cookies = '';
			foreach ($this->cookies as $k => $v)
				$cookies .= "$k=$v; ";
			if ($cookies != '')
				$header[] = 'Cookie: '.$cookies;

			$this->httpOptions['header'] = implode("\r\n", $header);
		}

		private function doQuery($url)
		{
			$rtn = file_get_contents($url, false, stream_context_create($this->context));
			if($rtn === false)
				throw new XHttpClientException("Query Failed. METHOD: {$this->httpOptions['method']}, URL: {$url}, CONTENT: {$this->httpOptions['content']}");

			$this->responsedContent = $rtn;
			$this->responsedHeader = $http_response_header;
		}

		private $responsedHeader;
		private $responsedContent;
		
		private $host;
		private $cookies;
		private $context;
		private $httpOptions;
	}
?>
