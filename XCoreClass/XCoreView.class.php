<?php
	class XCoreViewException extends XCoreException{}
	class XCoreView
	{
		public function __construct($tplFile)
		{
			$basePath = $this->getTPLPath();
			$basePath = rtrim($basePath, ' /');

			$this->tplFile = $basePath . '/' . ltrim($tplFile, ' /') . '.phtml';

			$this->rootPath = XCoreEnv::getBaseRequestURI();
			$this->basePath = $this->rootPath . $basePath . '/';
			$this->fullPath = $this->rootPath . $this->tplFile;
			$this->fileBase = dirname($this->fullPath) . '/';

		}

		public function output()
		{
			if (!is_file($this->tplFile))
				throw new XCoreViewException('Temple file not found. ');

			require_once ($this->tplFile);
		}

		// Override this to specify a new template path. 
		protected function getTPLPath () 
		{
			return 'TPL';
		}

		private $tplFile;
		
		public $rootPath;
		public $basePath;
		public $fullPath;
	}
?>