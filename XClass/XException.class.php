<?php
	class XException extends XCoreException
	{
		public function __construct($message = '', $code = 0, $previous = NULL)
		{
			parent::__construct($message, $code, $previous);
		}
	}

?>