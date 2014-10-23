<?php
	class XView extends XCoreView
	{
		public function __construct($tplFile)
		{
			parent::__construct($tplFile);
		}

		public function inclu($tplFile, $viewClass = 'XView')
		{
			$view = new $viewClass($tplFile);

			foreach (XUtil::getObjVars($this) as $k => $v)
				$view->$k = $v;

			$view->output();
		}
	}
?>