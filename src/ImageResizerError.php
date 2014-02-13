<?php

/**
*  @module Canteen\Media
*/
namespace Canteen\Media
{
	/**
	*  Exceptions specific to the image Resizer Utility.  
	*  Located in the namespace __Canteen\Media__.
	*  
	*  @class ImageResizerError
	*  @extends Exception
	*  @constructor
	*  @param {int} code The error code
	*  @param {String|Array} [data=''] The optional data associated with this error
	*/
	class ImageResizerError extends \Exception
	{		
		/** 
		*  Unable to output file
		*  @property {int} UNABLE_TO_OUTPUT
		*  @static
		*  @final
		*/
		const UNABLE_TO_OUTPUT = 600;
		
		/** 
		*  Source file doesn't exit
		*  @property {int} FILE_DOESNT_EXIST
		*  @static
		*  @final
		*/
		const FILE_DOESNT_EXIST = 601;
		
		/** 
		*  Function doesn't exist, for API checks
		*  @property {int} FUNC_DOESNT_EXIST
		*  @static
		*  @final
		*/
		const FUNC_DOESNT_EXIST = 602;
		
		/** 
		*  GD2 is installed, function ImageCreateTruecolor() exists, but image is not created
		*  @property {int} GD2_NOT_CREATED
		*  @static
		*  @final
		*/
		const GD2_NOT_CREATED = 603;
		
		/** 
		*  Image is not created ImageCreate(). (GD2 support is OFF)
		*  @property {int} IMG_NOT_CREATED
		*  @static
		*  @final
		*/
		const IMG_NOT_CREATED = 604;
		
		/** 
		*  You specified to use GD2, but not all GD2 functions are present.
		*  @property {int} GD2_UNAVALABLE
		*  @static
		*  @final
		*/
		const GD2_UNAVALABLE = 605;
		
		/** 
		*  GD2 is installed, function ImageCopyResampled() exists, but image is not resized
		*  @property {int} GD2_NOT_RESIZED
		*  @static
		*  @final
		*/
		const GD2_NOT_RESIZED = 606;
		
		/** 
		*  Image was not resized. (GD2 support is OFF)
		*  @property {int} IMG_NOT_RESIZED
		*  @static
		*  @final
		*/
		const IMG_NOT_RESIZED = 607;
		
		/** 
		*  The image format cannot be output
		*  @property {int} UNKNOWN_OUTPUT_FORMAT
		*  @static
		*  @final
		*/
		const UNKNOWN_OUTPUT_FORMAT = 608;
		
		/** 
		*  Image you are trying to output does not exist
		*  @property {int} NO_IMAGE_FOR_OUTPUT
		*  @static
		*  @final
		*/
		const NO_IMAGE_FOR_OUTPUT = 609;
		
		/** 
		*  Can not create image. Sorry, this image type is not supported yet.
		*  @property {int} IMG_NOT_SUPPORTED
		*  @static
		*  @final
		*/
		const IMG_NOT_SUPPORTED = 610;
		
		/**
		*  The collection of messages
		*  @property {Array} messages
		*  @private
		*  @static
		*  @final
		*/
		private static $messages = array(
			self::UNABLE_TO_OUTPUT => 'Unable to output %s',
			self::FILE_DOESNT_EXIST => 'This file does not exist %s',
			self::FUNC_DOESNT_EXIST => 'This function does not exist %s',
			self::GD2_NOT_CREATED => 'GD2 is installed, function ImageCreateTruecolor() exists, but image is not created',
			self::IMG_NOT_CREATED => 'Image is not created ImageCreate(). (GD2 support is OFF)',
			self::GD2_UNAVALABLE => 'You specified to use GD2 [%s], but not all GD2 functions are present.',
			self::GD2_NOT_RESIZED => 'GD2 is installed, function ImageCopyResampled() exists, but image is not resized',
			self::IMG_NOT_RESIZED => 'Image was not resized. (GD2 support is OFF)',
			self::UNKNOWN_OUTPUT_FORMAT => 'This image format cannot be output %s',
			self::NO_IMAGE_FOR_OUTPUT => 'Image you are trying to output does not exist.',
			self::IMG_NOT_SUPPORTED => 'Can not create image. Sorry, this image type is not supported yet.'
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