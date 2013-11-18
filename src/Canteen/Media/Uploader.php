<?php

/**
*  @module Canteen\Media
*/
namespace Canteen\Media
{
	use Canteen\Utilities\StringUtils;
	use Canteen\Errors\UploaderError;
	
	/**
	*  The Uploader is generalized way to upload any number of file types.
	*  Located in the namespace __Canteen\Media__.
	*  @class Uploader
	*  @constructor
	*  @param {String} inputName The name of the input to check for
	*  @param {String} uploadDir The upload direction location
	*  @param {Array} mimeTypes The acceptable mime types
	*  @param {Array} fileExts The collection of valid file types
	*  @param {String} [filename=''] Optionally specify a filename
	*/
	class Uploader
	{
		/** 
		*  The maximum size of the file upload in bytes 
		*  @property {int} maxSize
		*  @protected
		*/
		protected $maxSize;

		/** 
		*  If the upload was successful 
		*  @property {Boolean} success
		*/
		protected $success = false;

		/** 
		*  The type of file (mime type) 
		*  @property {String} fileType
		*  @protected
		*/
		protected $fileType;

		/** 
		*  The file extension 
		*  @property {String} fileExt
		*  @protected
		*/
		protected $fileExt;

		/** 
		*  The path to the target output file 
		*  @property {String} filePath
		*  @protected
		*/
		protected $filePath;

		/** 
		*  The size of the uploaded file
		*  @property {int} filesize
		*  @protected
		*/
		protected $filesize;

		/** 
		*  The temporary upload file name 
		*  @property {String} tempName
		*  @protected
		*/
		private $tempName;

		/** 
		*  If the uploaded file was moved
		*  @property {Boolean} uploadResult
		*  @protected
		*/
		private $uploadResult;

		/** 
		*  The upload location 
		*  @property {String} uploadDir
		*  @protected
		*/
		protected $uploadDir;

		/** 
		*  PHP's memory limit in bytes 
		*  @property {int} memoryLimit
		*  @protected
		*/
		private $memoryLimit;

		/** 
		*  The name of the file 
		*  @property {String} filename
		*/
		public $filename;

		/** 
		*  Acceptable mime types 
		*  @property {Array} _mimeTypes
		*  @private
		*/ 
		private $_mimeTypes;

		/** 
		*  Acceptable file extensions 
		*  @property {Array} _fileExts
		*  @private
		*/						
		private $_fileExts;
		
		/**
		*   See class definition above for docs on constructor 
		*/
		public function __construct($inputName, $uploadDir, $mimeTypes=array(), $fileExts=array(), $filename='')
		{
			$this->_mimeTypes = $mimeTypes;
			$this->_fileExts = $fileExts;
			
			$file = isset($_FILES[$inputName]) $_FILES[$inputName] : false;
			
			//check for input name
			if (!$file || !isset($file['name']))
			{
				throw new UploaderError(UploaderError::NO_INPUT);
			}
			else
			{
				//directory needs to be valid
				if (!is_dir($uploadDir)) 
				{
					throw new UploaderError(UploaderError::UPLOAD_DIR);
				}
				else
				{
					$this->uploadDir = $uploadDir;
					$this->fileType = $file['type'];
					$this->filename = $this->fixFilename($file['name']);
					$this->fileExt = substr(strrchr($this->filename, '.'), 1);

					//Assign file name or use upload name
					$this->filename = $filename ? $filename . '.' . $this->fileExt : $this->filename;

					$this->filesize = $file['size'];
					$this->filePath = $this->uploadDir . $this->filename;
					$this->tempName = $file['tmp_name'];

					$this->memoryLimit = preg_replace('/[^0-9]/','', ini_get('memory_limit')) * 1024 * 1024;
				}
			}			
		}

		/**
		*  Get the file extension
		*  @method getExtension
		*  @return {String} The file extension
		*/
		public function getExtension() 
		{
			return $this->fileExt;
		}
		
		/**
		*  Fix the file name by removing non-valid characters
		*  @method fixFilename
		*  @private
		*  @param {String} file The input file name
		*  @return {String} The sanitized filename
		*/	
		private function fixFilename($file) 
		{
			//replace spaces with hyphens
			$file = str_replace(' ','-', $file);

			//lowercase and remove non alpha-numeric characters
			$file = strtolower(preg_replace('/[^a-zA-Z0-9\-\_\.]/', '', $file));

			return $file;
		}

		/**
		*  Upload the file
		*  @method upload
		*  @param {int} [maxSize=1048576] The maximum file size in bytes (default 1MB)
		*  @param {Boolean} [overwrite=false] If we should overwrite the existing file
		*/
		public function upload($maxSize=1048576, $overwrite=false) 
		{
			// get max file size
			$this->maxSize = is_numeric($maxSize) ? $maxSize : $this->maxSize;
			
			$this->initCheck($overwrite);
			$this->uploadResult = move_uploaded_file($this->tempName, $this->filePath);
			$this->postCheck();
			
			$this->success = true;
		} 

		/**
		*  Run the initial check for the file type and extensions
		*  @method initCheck
		*  @private
		*  @param {Boolean} overwrite If we can overwrite the file
		*  @return {Boolean} If the check was successful
		*/
		private function initCheck($overwrite) 
		{	
			// no mime types are defined
			if (!count($this->_mimeTypes))
			{
				throw new UploaderError(UploaderError::UNDEFINED_MIMES);
			}
			// no file extensions are defined
			else if (!count($this->_fileExts))
			{
				throw new UploaderError(UploaderError::UNDEFINED_EXTS);
			}
			//check if file already exists
			else if (file_exists ($this->filePath) && !$overwrite) 
			{
				throw new UploaderError(UploaderError::ALREADY_EXISTS,  $this->filename);
			} 
			//Check the uploaded filesize
			else if ($this->filesize > $this->maxSize) 
			{
				throw new UploaderError(UploaderError::MAX_SIZE, MediaUtils::filesizeFormat($this->maxSize));
			} 
			//Make sure mime type is legit
			else if (!in_array($this->fileType, $this->_mimeTypes)) 
			{
				throw new UploaderError(UploaderError::MIME_TYPE. $this->fileType);
			} 
			//Make sure file type is legit
			else if (!in_array($this->fileExt, $this->_fileExts)) 
			{
				throw new UploaderError(UploaderError::FILE_EXT, $this->fileExt);
			}
			
			return true;
		}

		/**
		*  Post check after upload to make sure everything went well
		*  @method postCheck
		*  @private
		*  @return {Boolean} True if everything went well
		*/
		private function postCheck() 
		{		
			//bad return from move_uploaded_file function
		  	if (!$this->uploadResult) 
			{
		  		throw new UploaderError(UploaderError::BAD_UPLOAD);
		  	} 
			//file was not moved to destination
			else if (!file_exists($this->filePath)) 
			{
		  		throw new UploaderError(UploaderError::NOT_MOVED, $this->filePath);
		  	}  
			//check file permissions
			else if (!chmod ($this->filePath, 0755)) 
			{
		  		throw new UploaderError(UploaderError::PERMISSION);
		  	} 
			return true;
		}   

		/**
		*  If the upload was a success
		*  @method success
		*  @return {Boolean} If the upload was a success
		*/
		public function success() 
		{
			return $this->success;
		}
	}
}