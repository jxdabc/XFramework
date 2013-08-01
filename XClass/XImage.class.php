<?php
	class XImageException extends XException {}
	
	class XImage
	{
		public function __construct ($file)
		{
			$info = getimagesize($file);
			if ($info == FALSE)
				throw new XImageException('GETTING IMAGE FILE INFO FAILED. ', -1);

			switch ($info[2])
			{
				case 1: $this->image = imagecreatefromgif($file); break;
				case 2: $this->image = imagecreatefromjpeg($file); break;
				case 3: $this->image = imagecreatefrompng($file); break;
				case 15: $this->image = imagecreatefromwbmp($file); break;
				case 16: $this->image = imagecreatefromxbm($file); break;
			}

			if (!$this->image)
				throw new XImageException('IMAGE FORMAT NOT SUPPORTED. ', -2);

			list ($this->width, $this->height) = $info;
		}

		public function resampleTo ($width, $height)
		{
			$newImage = imagecreatetruecolor($width, $height);
			if (!imagecopyresampled($newImage, $this->image, 0, 0, 0, 0, $width, $height, $this->width, $this->height))
				throw new XImageException('RESAMPLING TO THE NEW SIZE FAILED. ', -3);
			$this->image = $newImage;
			$this->width = $width;
			$this->height = $height;
		}

		public function outputJPEG ($file = NULL)
		{
			if ($file == NULL)
				header('Content-Type: image/jpeg');

			if (!imagejpeg($this->image, $file))
				throw new XImageException('OUTPUT THE IMAGE AS JPEG ' . 
					($file == NULL ? 'STREAM ' : 'FILE ') . 
					'FAILED', -4);
		}

		private $image = NULL;
		private $width = 0;
		private $height = 0;
	}

?>