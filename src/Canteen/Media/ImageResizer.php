<?php

/**
*  @module Canteen\Media
*/
namespace Canteen\Media
{
	use Canteen\Errors\ResizerError;
	
	/**
	*  Handles image resize; can output to file or directly to browser.
	*  Located in the namespace __Canteen\Media__.
	*  @class ImageResizer
	*  @constructor
	*  @param {String} fileOriginal The native path to the original file
	*  @param {int} [jpegQuality=85] The JPEG compression from 0 to 100
	*/
	class ImageResizer
	{
		private $imageOriginal;
		private $fileOriginal;
		private $imageOriginalWidth;
		private $imageOriginalHeight;
		private $imageOriginalTypeCode;
		private $imageOriginalTypeAbbr;
		private $imageOriginalHtmlSizes;

		private $imageResized;
		private $fileResized;
		private $imageResizedWidth;
		private $imageResizedHeight;
		private $imageResizedTypeCode;
		private $imageResizedTypeAbbr;
		private $imageResizedHtmlSizes;

		/**
		*  The JPEG quality from 0 to 100 
		*  @property {int} jpegQuality
		*  @default 85
		*/
		public $jpegQuality = 85;
		
		/**
		*  If we should try to use the GD2 library
		*  @property {Boolean} useGD2
		*  @private
		*  @default true
		*/
		public $useGD2 = true;

		/** 
		*  Resize Mode to fill the entire area 
		*  @property {String} RESIZE_MAX_FILL
		*  @final
		*  @static
		*/
		const RESIZE_MAX_FILL = '+';

		/**  
		*  Resize mode to fill and crop the image down 
		*  @property {String} RESIZE_MAX_FILL_CROP
		*  @final
		*  @static
		*/
		const RESIZE_MAX_FILL_CROP = '[+]';

		/**  
		*  Fill to the minimum size 
		*  @property {String} RESIZE_MIN_FILL
		*  @final
		*  @static
		*/
		const RESIZE_MIN_FILL = '-';

		/** 
		*  Resize by distorting the image to file the whole size 
		*  @property {String} RESIZE_DISTORT
		*  @final
		*  @static
		*/
		const RESIZE_DISTORT= '0';
		
		/**
		*   See class definition above for docs on constructor 
		*/
		public function __construct($fileOriginal, $jpegQuality=85)
		{
			//constructor of the class
			//it takes given file and creates image out of it
			$this->clear();
			
			$this->jpegQuality = $jpegQuality;

			if (!file_exists($fileOriginal)) 
				throw new ResizerError(ResizerError::FILE_DOESNT_EXIST, $fileOriginal);

			$this->fileOriginal = $fileOriginal;
			$this->imageOriginal = $this->imageCreateFromFile($fileOriginal);
			
			if (!$this->imageOriginal)
				throw new ResizerError(ResizerError::IMAGE_NOT_CREATED_FROM_FILE, $fileOriginal);
		}
		
		/**
		*  Destroy this instance and any temporary files
		*  @method destroy
		*/
		public function destroy()
		{
			if ($this->imageOriginal)
			{
				imagedestroy($this->imageOriginal);
			}
			if ($this->imageResized)
			{
				imagedestroy($this->imageResized);
			}
			$this->clear();
		}

		/**
		*  Clear all the class member varaibles 
		*  @method clear
		*  @private
		*/
		private function clear()
		{
			$this->imageOriginal			= 0;
			$this->fileOriginal				= '';
			$this->imageOriginalWidth		= 0;
			$this->imageOriginalHeight		= 0;
			$this->imageOriginalTypeCode	= 0;
			$this->imageOriginalTypeAbbr	= '';
			$this->imageOriginalHtmlSizes	= '';

			$this->imageResized				= 0;
			$this->fileResized				= '';
			$this->imageResizedWidth		= 0;
			$this->imageResizedHeight		= 0;
			$this->imageResizedTypeCode		= -1;
			$this->imageResizedTypeAbbr		= '';
			$this->imageResizedHtmlSizes	= '';
		}

		private function imageCreateFromFile($imageFile)
		{
			$img=0;
			
			// returns array with some properties like dimensions and type
			$imgSz =  getimagesize($imageFile); 	
			
			// Now create original image from uploaded file. 
			// Be carefull! GIF is often not supported, as 
			// far as I remember from GD 1.6
			switch($imgSz[2])
			{
				case 1:
					$img = $this->imageCheckAndCreate('ImageCreateFromGif', $imageFile);
					$imgType = 'gif';
				break;
				case 2: 
					$img = $this->imageCheckAndCreate('ImageCreateFromJpeg', $imageFile);
					$imgType = 'jpg';
				break;
				case 3: 
					$img = $this->imageCheckAndCreate('ImageCreateFromPng', $imageFile);
					$imgType = 'png';
				break;
				// would be nice if this function will be finally supported
				case 4: 
					$img = $this->imageCheckAndCreate('ImageCreateFromSwf', $imageFile);
					$imgType = 'swf';
				break;
				default:
					$img = 0;
					$imgType = 'unknown';
					throw new ResizerError(ResizerError::IMG_NOT_SUPPORTED, $imageFile);
					break;
			}

			if ($img)
			{
				$this->imageOriginalWidth=$imgSz[0];
				$this->imageOriginalHeight=$imgSz[1];
				$this->imageOriginalTypeCode=$imgSz[2];
				$this->imageOriginalTypeAbbr=$imgType;
				$this->imageOriginalHtmlSizes=$imgSz[3];
			}
			else 
			{
				$this->clear();
			}
			return $img;		
		}

		private function imageCheckAndCreate($function, $imageFile) 
		{
			//inner function used from imageCreateFromFile(). 
			//Checks if the function exists and returns
			//created image or false
			if (!function_exists($function)) 
				throw new ResizerError(ResizerError::FUNC_DOESNT_EXIST, $function);
			
			return $function($imageFile);
		}

		/**
		*  Resize the image 
		*  @method resize
		*  @param {int} desiredWidth The desired output width, can be '*' for autosize
		*  @param {int} desiredHeight The desired output height, can be '*' for autosize
		*  @param {String} [mode=self::RESIZE_MIN_FILL] The resize mode for the image
		*/
		public function resize($desiredWidth, $desiredHeight, $mode=self::RESIZE_MIN_FILL)
		{
			//this is core function--it resizes created image
			//if any of parameters == '*' then no resizing on this parameter
			//>> mode = self::RESIZE_MAX_FILL then image is resized to cover the region specified by desiredWidth, _height
			//>> mode = '[+]' then image is resized to cover the region specified and then cropped
			//>> mode = '-' then image is resized to fit into the region specified by desiredWidth, _height
			// width-to-height ratio is all the time the same
			//>>mode=0 then image will be exactly resized to $desiredWidth _height.
			//geometrical distortion can occur in this case.
			// say u have picture 400x300 and there is circle on the picture
			//now u resized in mode=0 to 800x300 -- circle shape will be distorted and will look like ellipse.
			//GD2 provides much better quality but is not everywhere installed
			
			if ($desiredWidth == '*' && $desiredHeight == '*')
			{
				$this->imageResized = $this->imageOriginal;
				return true;
			}

			$crop = ($mode == self::RESIZE_MAX_FILL_CROP);

			switch($mode)
			{
				case self::RESIZE_MIN_FILL:
				case self::RESIZE_MAX_FILL:
				case self::RESIZE_MAX_FILL_CROP:
				
					//multipliers
					if ($desiredWidth != '*') $mult_x = $desiredWidth / $this->imageOriginalWidth;		
					if ($desiredHeight != '*') $mult_y = $desiredHeight / $this->imageOriginalHeight;
									
					$ratio = $this->imageOriginalWidth / $this->imageOriginalHeight;

					if ($desiredWidth == '*')
					{
						$newHeight = $desiredHeight;
						$newWidth = $ratio * $desiredHeight;
					}
					elseif ($desiredHeight == '*')
					{
						$newHeight = $desiredWidth / $ratio;
						$newWidth =  $desiredWidth;
					}
					else
					{
						if ($mode == self::RESIZE_MIN_FILL)
						{
							//image must be smaller than given $desired_ region
							//test which multiplier gives us best result
							if ($this->imageOriginalHeight * $mult_x < $desiredHeight)
							{
								//$mult_x does the job
								$newWidth = $desiredWidth;
								$newHeight = $this->imageOriginalHeight * $mult_x;
							}
							else
							{
								//$mult_y does the job
								$newWidth = $this->imageOriginalWidth * $mult_y;
								$newHeight = $desiredHeight;
							}
						}
						else
						{
							//mode == self::RESIZE_MAX_FILL
							// cover the region
							//image must be bigger than given $desired_ region
							//test which multiplier gives us best result
							if ($this->imageOriginalHeight * $mult_x > $desiredHeight)
							{
								//$mult_x does the job
								$newWidth = $desiredWidth;
								$newHeight = $this->imageOriginalHeight * $mult_x;
							}
							else
							{
								//$mult_y does the job
								$newWidth = $this->imageOriginalWidth * $mult_y;
								$newHeight = $desiredHeight;
							}
						}
					}
				break;

				case self::RESIZE_DISTORT :
					//fit the region exactly.
					if ($desiredWidth == '*') $desiredWidth = $this->imageOriginalWidth;		
					if ($desiredHeight == '*') $desiredHeight = $this->imageOriginalHeight;	
					$newWidth = $desiredWidth;
					$newHeight = $desiredHeight;

				break;
				default: 
					throw new ResizerError(ResizerError::UNKNOWN_RESIZE_MODE, $mode);
			}

			// OK here we have $newWidth _height
			//create destination image checking for GD2 functions:
			if ($this->useGD2)
			{
				if (!function_exists('imagecreatetruecolor'))
					throw new ResizerError(ResizerError::GD2_UNAVALABLE, 'ImageCreateTruecolor()');
				
				$this->imageResized = imagecreatetruecolor($newWidth, $newHeight);
				
				if (!$this->imageResized)
					throw new ResizerError(ResizerError::GD2_NOT_CREATED);				
			} 
			else 
			{
				$this->imageResized = imagecreate($newWidth, $newHeight);
				
				if (!$this->imageResized)
					throw new ResizerError(ResizerError::IMG_NOT_CREATED);
			}

			//Resize
			if ($this->useGD2)
			{
				if (!function_exists('imagecopyresampled'))
					throw new ResizerError(ResizerError::GD2_UNAVALABLE, 'ImageCopyResampled()');
				
				$res = imagecopyresampled($this->imageResized, 
					$this->imageOriginal, 
					0, 0, //dest coord
					0, 0, //source coord
					$newWidth, $newHeight, //dest sizes
					$this->imageOriginalWidth, $this->imageOriginalHeight // src sizes
				);
				
				if (!$res) throw new ResizerError(ResizerError::GD2_NOT_RESIZED);				
			} 
			else 
			{
				$res = imagecopyresized($this->imageResized, 
					$this->imageOriginal, 
					0, 0, //dest coord
					0, 0, //source coord
					$newWidth, $newHeight, //dest sizes
					$this->imageOriginalWidth, $this->imageOriginalHeight // src sizes
				);
				
				if (!$res) throw new ResizerError(ResizerError::IMG_NOT_RESIZED); 
			}

			if ($crop)
			{
				if ($this->useGD2)
				{
					if (!function_exists('imagecreatetruecolor'))
						throw new ResizerError(ResizerError::GD2_UNAVALABLE, 'ImageCreateTruecolor()');
					
					$thumb = imagecreatetruecolor($desiredWidth, $desiredHeight);
					
					if (!$thumb) throw new ResizerError(ResizerError::GD2_NOT_CREATED);				
				} 
				else 
				{
					$thumb = imagecreate($desiredWidth, $desiredHeight);
					if (!$thumb) throw new ResizerError(ResizerError::IMG_NOT_CREATED);
				}

				if ($this->useGD2)
				{
					if (!function_exists('imagecopyresampled'))
						throw new ResizerError(ResizerError::GD2_UNAVALABLE, 'ImageCopyResampled()');
					
					$res = imagecopyresampled($thumb, 
						$this->imageResized, 
						0, 0, 
						($newWidth - $desiredWidth)/2, ($newHeight - $desiredHeight)/2, 
						$desiredWidth, $desiredHeight, 
						$desiredWidth, $desiredHeight
					);
					
					if (!$res) throw new ResizerError(ResizerError::GD2_NOT_RESIZED);
				} 
				else 
				{
					$res = imagecopyresized($thumb, 
						$this->imageResized, 
						0, 0, 
						($newWidth - $desiredWidth)/2, ($newHeight - $desiredHeight)/2, 
						$desiredWidth, $desiredHeight, 
						$desiredWidth, $desiredHeight
					);
					
					if (!$res) throw new ResizerError(ResizerError::IMG_NOT_RESIZED); 
				}
				imagedestroy($this->imageResized);
				$this->imageResized = $thumb;
			}
		}
		
		/**
		*  Output the original image
		*  @method outputOriginal
		*  @param {String} destinationFile The output location
		*  @param {String} imageType The image type, jpg or png
		*  @return {Boolean} If we were successfully able to save
		*/
		public function outputOriginal($destinationFile, $imageType='jpg')
		{ 
			//outputs original image 
			//if destination file is empty  image will be output to browser 
			// right now $imageType can be JPG or PNG	 
			return $this->outputImage($destinationFile, $imageType, $this->imageOriginal); 
		} 
		
		/**
		*  Output the resized image
		*  @method outputResized
		*  @param {String} destinationFile The output location
		*  @param {String} imageType The image type, jpg or png
		*  @return {Boolean} If we were successfully able to save
		*/
		public function outputResized($destinationFile, $imageType='jpg')
		{ 
			//if destination file is empty  image will be output to browser 
			// right now $imageType can be JPG or PNG	
			$res = $this->outputImage($destinationFile, $imageType, $this->imageResized); 
		
			if (trim($destinationFile))
			{ 
				$sz = getimagesize($destinationFile); 
				$this->fileResized = $destinationFile; 
				$this->imageResizedWidth = $sz[0]; 
				$this->imageResizedHeight = $sz[1]; 
				$this->imageResizedTypeCode = $sz[2]; 
				$this->imageResizedHtmlSizes = $sz[3];
				
				//only jpeg and png are really supported, but I'd like to think of future 
				switch($this->imageResizedHtmlSizes)
				{ 
					case 0: 
						$this->imageResizedTypeAbbr = 'gif'; 
					break; 
					case 1: 
						$this->imageResizedTypeAbbr = 'jpg'; 
					break; 
					case 2: 
						$this->imageResizedTypeAbbr = 'png'; 
					break; 
					case 3: 
						$this->imageResizedTypeAbbr = 'swf'; 
					break; 
					default: 
						$this->imageResizedTypeAbbr = 'unknown'; 
					break; 
				} 

			} 
			return $res; 
		} 
		
		/**
		*  Abstract method to save an image to a destination
		*  @method outputImage
		*  @private
		*  @param {String} destinationFile The output file path
		*  @param {String} imageType jpg or png
		*  @param {Object} image The image resource
		*  @return {Boolean} If we saved the file successfully
		*/
		private function outputImage($destinationFile, $imageType, $image)
		{ 
			// if destination file is empty  image will be output to browser 
			// right now $imageType can be JPEG or PNG	  
			$destinationFile = trim($destinationFile); 
			$res = false;
			
			if (!$image) throw new ResizerError(ResizerError::NO_IMAGE_FOR_OUTPUT); 
			
			switch($imageType) 
			{ 
				case 'jpeg': 
				case 'jpg': 
					if (!$destinationFile) header('Content-type: image/jpeg');
					$res = ImageJpeg($image, $destinationFile, $this->jpegQuality); 
					break; 
				case 'png': 
					if (!$destinationFile) header('Content-type: image/png');
					$res = Imagepng($image, $destinationFile); 
					break; 
				default: 
					throw new ResizerError(ResizerError::UNKNOWN_OUTPUT_FORMAT, $imageType); 
					break; 
			}
			if (!$res) throw new ResizerError(ResizerError::UNABLE_TO_OUTPUT, $destinationFile); 
			return $res; 
		} 
	}
}