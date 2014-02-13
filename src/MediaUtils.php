<?php

/**
*  @module Canteen\Media
*/
namespace Canteen\Media
{
	/**
	*  Media specific utilities functions.
	*  Located in the namespace __Canteen\Media__.
	*  @class MediaUtils 
	*/
	abstract class MediaUtils
	{
		/**
		*  Convert number of a number of bytes into a readable filesize format
		*  @method filesizeFormat
		*  @static
		*  @param {int} bytes The number of file bytes
		*  @return {String} The readable filesize format (e.g., '5MB', '100KB')
		*/
		public static function filesizeFormat($bytes) 
		{
			if ( 0 > $bytes ) return $bytes;

			$names = array( 'B', 'KB', 'MB', 'GB', 'TB');
			$values = array( 1, 1024, 1048576, 1073741824, 1099511627776);
			$i = floor(log($bytes)/6.9314718055994530941723212145818);

			if ( $bytes != 0 )
				return number_format($bytes/$values[$i]).' '.$names[$i];
			else
				return $bytes; 
		}
	}
}
