<?php

/**
*  @module Canteen\Errors
*/
namespace Canteen\Errors
{	
	/**
	*  Exceptions specific to the media Uploader class
	*  Located in the namespace __Canteen\Errors__.
	*  
	*  @class UploaderError
	*  @extends Exception
	*  @constructor
	*  @param {int} code The error code
	*  @param {String|Array} [data=''] The optional data associated with this error
	*/
	class UploaderError extends \Exception
	{
		/** 
		*  Cannot resize until upload has been completed
		*  @property {int} CANNOT_RESIZE
		*  @static
		*  @final
		*/
		const CANNOT_RESIZE = 500;
		
		/** 
		*  Filesize is over the server limit
		*  @property {int} MAX_SIZE
		*  @static
		*  @final
		*/
		const MAX_SIZE = 501;
		
		/** 
		*  File extension not allowed
		*  @property {int} FILE_EXT
		*  @static
		*  @final
		*/
		const FILE_EXT = 502;
		
		/** 
		*  Mime type not allowed
		*  @property {int} MIME_TYPE
		*  @static
		*  @final
		*/
		const MIME_TYPE = 503;
		
		/** 
		*  File was not moved
		*  @property {int} NOT_MOVED
		*  @static
		*  @final
		*/
		const NOT_MOVED = 504;
		
		/** 
		*  Upload result was unsuccessful
		*  @property {int} BAD_UPLOAD
		*  @static
		*  @final
		*/
		const BAD_UPLOAD = 505;
		
		/** 
		*  Change permission to writable
		*  @property {int} PERMISSION
		*  @static
		*  @final
		*/
		const PERMISSION = 506;
		
		/** 
		*  No file to upload
		*  @property {int} NO_INPUT
		*  @static
		*  @final
		*/
		const NO_INPUT = 507;
		
		/** 
		*  Upload was not successful
		*  @property {int} UNSUCCESSFUL
		*  @static
		*  @final
		*/
		const UNSUCCESSFUL = 508;
		
		/** 
		*  Thumbnail creation failed
		*  @property {int} THUMBNAIL
		*  @static
		*  @final
		*/
		const THUMBNAIL = 509;
		
		/** 
		*  Upload directory does not exist
		*  @property {int} UPLOAD_DIR
		*  @static
		*  @final
		*/
		const UPLOAD_DIR = 510;
		
		/** 
		*  File already exists
		*  @property {int} ALREADY_EXISTS
		*  @static
		*  @final
		*/
		const ALREADY_EXISTS = 511;
		
		/** 
		*  No MIME types defined for this uploader
		*  @property {int} UNDEFINED_MIMES
		*  @static
		*  @final
		*/
		const UNDEFINED_MIMES = 512;
		
		/** 
		*  No file extensions defined for this uploader
		*  @property {int} UNDEFINED_EXTS
		*  @static
		*  @final
		*/
		const UNDEFINED_EXTS = 513;
		
		/** 
		*  Not enough memory to complete this task
		*  @property {int} MEMORY_LIMIT
		*  @static
		*  @final
		*/
		const MEMORY_LIMIT = 514;
			
		/**
		*  The collection of messages
		*  @property {Array} messages
		*  @private
		*  @static
		*  @final
		*/
		private static $messages = array(
			self::CANNOT_RESIZE	=> 'Cannot resize until upload has been completed',
	        self::MAX_SIZE => 'Filesize is over %s',
	        self::FILE_EXT => 'File extension not allowed %s',
	        self::MIME_TYPE => 'Mime type not allowed %s',
	        self::NOT_MOVED => 'File was not moved %s ',
	        self::BAD_UPLOAD => 'Upload result was unsuccessful',
	        self::PERMISSION => 'Change permission to 755',
	        self::NO_INPUT => 'No file to upload',
	        self::UNSUCCESSFUL => 'Upload was not successful',
	        self::THUMBNAIL => 'Thumbnail creation failed',
	        self::UPLOAD_DIR => 'Upload directory does not exist %s',
	        self::ALREADY_EXISTS => 'File already exists %s',
	        self::UNDEFINED_MIMES => 'No MIME types defined for this uploader',
	        self::UNDEFINED_EXTS => 'No file extensions defined for this uploader',
	        self::MEMORY_LIMIT => 'Not enough memory to complete this task'
		);
		
		/** 
		*  The label for an error that is unknown or unfound in messages
		*  @property {String} UNKNOWN
		*  @static
		*  @final
		*/
		const UNKNOWN = 'Unknown error';
		
		/**
		*   See class definition above for docs on constructor 
		*/
		public function __construct($code, $data='')
		{
			$message = isset(self::$messages[$code]) ? self::$messages[$code] : self::UNKNOWN;
			
			// If the string contains substitution strings
			// we should apply the subs
			if (preg_match('/\%s/', $message))
			{
				$args = array_merge(array($message), is_array($data) ? $data : array($data));
				$message = call_user_func_array('sprintf', $args);
			}
			// Just add the extra data at the end of the message
			else if (!empty($data))
			{
				$message .= ' : ' . $data;	
			}	
			parent::__construct($message, $code);
		}
	}
}