<?php

/**
*  @module Canteen\Media
*/
namespace Canteen\Media
{
	use Canteen\Errors\UploaderError;
	
	/**
	*  Class to upload images (JPEGs or PNGs only).
	*  Located in the namespace __Canteen\Media__.
	*  @class ImageUploader
	*  @extends MediaUploader
	*  @constructor
	*  @param {String} inputName The name of the input to check for
	*  @param {String} uploadDir The upload direction location
	*  @param {String} [filename=''] Optionally specify a filename
	*/
	class ImageUploader extends MediaUploader
	{		
		/** 
		*  The name of the thumbnail
		*  @property {String} thumbnailName
		*/
		public $thumbnailName;
		
		/**
		*   See class definition above for docs on constructor 
		*/
		public function __construct($inputName, $uploadDir, $filename='')
		{
			parent::__construct(
				$inputName, 
				$uploadDir, 
				array(
					'image/jpeg',
					'image/jpg',
					'image/png'
				), 
				array(
					'jpg',
					'jpeg',
					'png'
				), 
				$filename
			);
		}
	
		/**
		*  Resize the image
		*  @method resize
		*  @param {int} width The width of the image
		*  @param {int} height The height of the image
		*  @param {String} [mode=ImageResizer::RESIZE_MIN_FILL] The mode of the resize
		*/
		public function resize($width, $height, $mode=ImageResizer::RESIZE_MIN_FILL) 
		{
			if ($this->success) 
			{
				$srcProps = getimagesize($this->filePath);
				$originalWidth = $srcProps[0];
				$originalHeight = $srcProps[1];

				if ($originalWidth > $width || $originalHeight > $height) 
				{
					$img = new ImageResizer($this->filePath);
					$img->resize($width, $height, $mode);
					$img->outputResized($this->filePath);
					$img->destroy();
					unset($img);
				}
			}
		}

		/**
		*  Create a thumbnail image
		*  @method createThumbnail
		*  @param {int} width The width of the thumbnail
		*  @param {int} height The height of the thumbnail
		*  @param {String} [postfix='_tn'] The string to affix to the end of the file name
		*  @param {String} [mode=ImageResizer::RESIZE_MIN_FILL] The mode of the resize
		*  @return {Boolean} If the upload was successful
		*/
		public function createThumbnail($width, $height, $postfix='_tn', $mode=ImageResizer::RESIZE_DISTORT) 
		{
			$this->thumbnailName = str_replace('.' . $this->fileExt, $postfix.'.'.$this->fileExt, $this->filename);

			$img = new ImageResizer($this->filePath);
			$img->resize($width, $height, $mode);
			$img->outputResized($this->uploadDir.$this->thumbnailName);  
			$img->destroy();
			unset($img);

			//make sure resize was successful	
			if (!file_exists($this->uploadDir.$this->thumbnailName)) 
			{
			   throw new UploaderError(UploaderError::THUMBNAIL);
			}
			return 1;
		}
	}
}