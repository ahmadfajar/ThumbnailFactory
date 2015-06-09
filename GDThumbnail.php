<?php
/**
 * This file is part of Bamboo CMS Application Framework.
 *
 * (c) Ahmad Fajar <ahmad.fajar234@yahoo.com>
 *
 */

// namespace Bamboo\CoreBundle\Thumbnail;


/**
 * Class GDThumbnail is a class for processing image using GD 2.0+
 *
 * @author    Ahmad Fajar
 * @since     08/02/2014, modified: 08/03/2014 16:59
 * @version   1.0.0
 * @category  CoreBundle
 * @package   Bamboo\CoreBundle\Thumbnail
 */
class GDThumbnail extends AbstractThumbnail
{
  /**
   * The current dimensions of the image
   *
   * @var array
   */
  protected $currentDimensions;
  /**
   * The maximum height an image can be after resizing (in pixels)
   *
   * @var int
   */
  protected $maxHeight;
  /**
   * The maximum width an image can be after resizing (in pixels)
   *
   * @var int
   */
  protected $maxWidth;
  /**
   * The new, calculated dimensions of the image
   *
   * @var array
   */
  protected $newDimensions;
  /**
   * The prior image (before manipulation)
   *
   * @var resource
   */
  protected $oldImage;
  /**
   * The percentage to resize the image by
   *
   * @var int
   */
  protected $percent;
  /**
   * The working image (used during manipulation)
   *
   * @var resource
   */
  protected $workingImage;

  /**
   * Construct the GDThumbnail class object.
   *
   * @param null|string $fileName the image location
   * @param array       $options  processing parameters
   * @param bool        $isDataStream
   *
   * @throws \RuntimeException
   */
  public function __construct($fileName = null, array $options = array(), $isDataStream = false)
  {
    if (!extension_loaded('gd')) {
      throw $this->triggerError('The GD extension must be loaded before using this thumbnail interface!');
    }

    $this->filename     = $fileName;
    $this->isDataStream = $isDataStream;

    if (!empty($this->filename)) {
      $this->readImage($fileName);
    }
    $this->setOptions($options);
  }

  /**
   * Clean the resource identifier before the object is destroyed.
   */
  public function __destruct()
  {
    if (is_resource($this->oldImage)) {
      imagedestroy($this->oldImage);
    }

    if (is_resource($this->workingImage)) {
      imagedestroy($this->workingImage);
    }
  }

  /**
   * @inheritdoc
   */
  public function adaptiveResize($maxWidth, $maxHeight)
  {
    // make sure our arguments are valid
    if (!is_numeric($maxWidth) || $maxWidth == 0) {
      throw $this->triggerError('$maxWidth must be numeric and greater than zero!', UNKNOWN_ERROR);
    }
    if (!is_numeric($maxHeight) || $maxHeight == 0) {
      throw $this->triggerError('$maxHeight must be numeric and greater than zero!', UNKNOWN_ERROR);
    }
    $height = intval($maxHeight);
    $width  = intval($maxWidth);

    // make sure we're not exceeding our image size if we're not supposed to
    if ($this->options['resizeUp'] === false) {
      $this->maxHeight = ($height > $this->currentDimensions['height']) ? $this->currentDimensions['height'] : $height;
      $this->maxWidth  = ($width > $this->currentDimensions['width']) ? $this->currentDimensions['width'] : $width;
    }
    else {
      $this->maxHeight = $height;
      $this->maxWidth  = $width;
    }

    $this->calcImageSizeStrict($this->currentDimensions['width'], $this->currentDimensions['height']);
    // resize the image to be close to our desired dimensions
    $this->resize($this->newDimensions['width'], $this->newDimensions['height']);

    // reset the max dimensions...
    if ($this->options['resizeUp'] === false) {
      $this->maxHeight = ($height > $this->currentDimensions['height']) ? $this->currentDimensions['height'] : $height;
      $this->maxWidth  = ($width > $this->currentDimensions['width']) ? $this->currentDimensions['width'] : $width;
    }
    else {
      $this->maxHeight = $height;
      $this->maxWidth  = $width;
    }

    // create the working image
    if (function_exists('imagecreatetruecolor')) {
      $this->workingImage = imagecreatetruecolor($this->maxWidth, $this->maxHeight);
    }
    else {
      $this->workingImage = imagecreate($this->maxWidth, $this->maxHeight);
    }

    $this->preserveAlpha();
    $cropWidth  = $this->maxWidth;
    $cropHeight = $this->maxHeight;
    $cropX      = 0;
    $cropY      = 0;

    // now, figure out how to crop the rest of the image...
    if ($this->currentDimensions['width'] > $this->maxWidth) {
      $cropX = intval(($this->currentDimensions['width'] - $this->maxWidth) / 2);
    }
    elseif ($this->currentDimensions['height'] > $this->maxHeight) {
      $cropY = intval(($this->currentDimensions['height'] - $this->maxHeight) / 2);
    }

    $ret = imagecopyresampled($this->workingImage,
                              $this->oldImage,
                              0,
                              0,
                              $cropX,
                              $cropY,
                              $cropWidth,
                              $cropHeight,
                              $cropWidth,
                              $cropHeight
    );

    // update all the variables and resources to be correct
    $this->oldImage                    = $this->workingImage;
    $this->currentDimensions['width']  = $this->maxWidth;
    $this->currentDimensions['height'] = $this->maxHeight;

    if ($ret === false) {
      $this->setError('Unable to adaptiveResize the image.');
    }

    return $this;
  }

  /**
   * @inheritdoc
   */
  public function cropImage($width, $height, $x, $y)
  {
    // validate input
    if (!is_numeric($width)) {
      throw $this->triggerError('$width must be numeric!', UNKNOWN_ERROR);
    }
    if (!is_numeric($height)) {
      throw $this->triggerError('$height must be numeric!', UNKNOWN_ERROR);
    }
    if (!is_numeric($x)) {
      throw $this->triggerError('$x must be numeric!', UNKNOWN_ERROR);
    }
    if (!is_numeric($y)) {
      throw $this->triggerError('$y must be numeric!', UNKNOWN_ERROR);
    }

    // do some calculations
    $width  = ($this->currentDimensions['width'] < $width) ? $this->currentDimensions['width'] : $width;
    $height = ($this->currentDimensions['height'] < $height) ? $this->currentDimensions['height'] : $height;

    // ensure everything's in bounds
    if (($x + $width) > $this->currentDimensions['width']) {
      $x = ($this->currentDimensions['width'] - $width);
    }
    if (($y + $height) > $this->currentDimensions['height']) {
      $y = ($this->currentDimensions['height'] - $height);
    }

    if ($x < 0) {
      $x = 0;
    }
    if ($y < 0) {
      $y = 0;
    }

    // create the working image
    if (function_exists('imagecreatetruecolor')) {
      $this->workingImage = imagecreatetruecolor($width, $height);
    }
    else {
      $this->workingImage = imagecreate($width, $height);
    }

    $this->preserveAlpha();
    $ret = imagecopyresampled($this->workingImage,
                              $this->oldImage,
                              0,
                              0,
                              $x,
                              $y,
                              $width,
                              $height,
                              $width,
                              $height
    );

    $this->oldImage                    = $this->workingImage;
    $this->currentDimensions['width']  = $width;
    $this->currentDimensions['height'] = $height;

    if ($ret === false) {
      $this->setError('Unable to crop the image.');
    }

    return $this;
  }

  /**
   * @inheritdoc
   */
  public function cropImageFromCenter($width, $height = null)
  {
    if (!is_numeric($width)) {
      throw $this->triggerError('$cropWidth must be numeric!', UNKNOWN_ERROR);
    }
    if ($height !== null && !is_numeric($height)) {
      throw $this->triggerError('$cropHeight must be numeric!', UNKNOWN_ERROR);
    }

    if ($height === null) {
      $height = $width;
    }

    $cropWidth  = ($this->currentDimensions['width'] < $width) ? $this->currentDimensions['width'] : $width;
    $cropHeight = ($this->currentDimensions['height'] < $height) ? $this->currentDimensions['height'] : $height;
    $cropX      = intval(($this->currentDimensions['width'] - $cropWidth) / 2);
    $cropY      = intval(($this->currentDimensions['height'] - $cropHeight) / 2);
    $this->cropImage($width, $height, $cropX, $cropY);

    return $this;
  }

  /**
   * @inheritdoc
   */
  public function flipImage()
  {
    throw $this->triggerError('Method is not implemented yet!');
  }

  /**
   * @inheritdoc
   */
  public function flopImage()
  {
    throw $this->triggerError('Method is not implemented yet!');
  }

  /**
   * Get the GD supported image format.
   *
   * @return array
   */
  public function getSupportedFormat()
  {
    $gdInfo  = gd_info();
    $support = array();

    foreach ($gdInfo as $key => $info) {
      if (is_bool($info) && $info === true) {
        $tmp    = explode(' ', $key);
        $format = $tmp[0];
        if (($format != 'FreeType' || $format != 'T1Lib') && !in_array($format, $support)) {
          $support[] = $format;
        }
      }
    }

    return $support;
  }

  /**
   * @inheritdoc
   */
  public function readImage($filename)
  {
    if (is_readable($filename)) {
      $this->filename = $filename;
      $this->determineFormat();

      if ($this->hasErrors()) {
        throw $this->triggerError($this->getError());
      }
      if ($this->isDataStream === false) {
        $this->verifyFormatCompatiblity();
      }

      $fnc                     = 'imagecreatefrom' . strtolower($this->format);
      $this->oldImage          = call_user_func($fnc, $this->filename);
      $this->currentDimensions = array('width'  => imagesx($this->oldImage),
                                       'height' => imagesy($this->oldImage));
    }
    else {
      throw $this->triggerError(sprintf('The image location of "%s" is unreadable!', $filename));
    }

    return $this;
  }

  /**
   * @inheritdoc
   */
  public function resize($maxWidth = 0, $maxHeight = 0)
  {
    // make sure our arguments are valid
    if (!is_numeric($maxWidth)) {
      throw $this->triggerError('$maxWidth must be numeric!', UNKNOWN_ERROR);
    }
    if (!is_numeric($maxHeight)) {
      throw $this->triggerError('$maxHeight must be numeric!', UNKNOWN_ERROR);
    }
    $maxHeight = intval($maxHeight);
    $maxWidth  = intval($maxWidth);

    // make sure we're not exceeding our image size if we're not supposed to
    if ($this->options['resizeUp'] === false) {
      $this->maxHeight = ($maxHeight > $this->currentDimensions['height']) ? $this->currentDimensions['height'] : $maxHeight;
      $this->maxWidth  = ($maxWidth > $this->currentDimensions['width']) ? $this->currentDimensions['width'] : $maxWidth;
    }
    else {
      $this->maxHeight = $maxHeight;
      $this->maxWidth  = $maxWidth;
    }

    // get the new dimensions...
    $this->calcImageSize($this->currentDimensions['width'], $this->currentDimensions['height']);

    // create the working image
    if (function_exists('imagecreatetruecolor')) {
      $this->workingImage = imagecreatetruecolor($this->newDimensions['width'], $this->newDimensions['height']);
    }
    else {
      $this->workingImage = imagecreate($this->newDimensions['width'], $this->newDimensions['height']);
    }

    $this->preserveAlpha();

    // and create the newly sized image
    $ret = imagecopyresampled($this->workingImage,
                              $this->oldImage,
                              0,
                              0,
                              0,
                              0,
                              $this->newDimensions['width'],
                              $this->newDimensions['height'],
                              $this->currentDimensions['width'],
                              $this->currentDimensions['height']
    );

    // update all the variables and resources to be correct
    $this->oldImage          = $this->workingImage;
    $this->currentDimensions = $this->newDimensions;

    if ($ret === false) {
      $this->setError('Unable to resize the image.');
    }

    return $this;
  }

  /**
   * @inheritdoc
   */
  public function resizePercent($percent = 0)
  {
    if (!is_numeric($percent)) {
      throw $this->triggerError('$percent must be numeric!', UNKNOWN_ERROR);
    }

    $this->percent = intval($percent);

    $this->calcImageSizePercent($this->currentDimensions['width'], $this->currentDimensions['height']);

    if (function_exists('imagecreatetruecolor')) {
      $this->workingImage = imagecreatetruecolor($this->newDimensions['width'], $this->newDimensions['height']);
    }
    else {
      $this->workingImage = imagecreate($this->newDimensions['width'], $this->newDimensions['height']);
    }

    $this->preserveAlpha();

    $ret = imagecopyresampled($this->workingImage,
                              $this->oldImage,
                              0,
                              0,
                              0,
                              0,
                              $this->newDimensions['width'],
                              $this->newDimensions['height'],
                              $this->currentDimensions['width'],
                              $this->currentDimensions['height']
    );

    $this->oldImage          = $this->workingImage;
    $this->currentDimensions = $this->newDimensions;

    if ($ret === false) {
      $this->setError('Unable to resize the image.');
    }

    return $this;
  }

  /**
   * @inheritdoc
   */
  public function rotateImage($direction = 'CW')
  {
    if ($direction == 'CW') {
      $this->rotateImageNDegrees(90);
    }
    else {
      $this->rotateImageNDegrees(-90);
    }

    return $this;
  }

  /**
   * @inheritdoc
   */
  public function rotateImageNDegrees($degrees)
  {
    if (!is_numeric($degrees)) {
      throw $this->triggerError('$degrees must be numeric!', UNKNOWN_ERROR);
    }
    if (!function_exists('imagerotate')) {
      throw $this->triggerError('Your version of GD does not support image rotation.');
    }

    $this->workingImage                = imagerotate($this->oldImage, $degrees, 0);
    $newWidth                          = $this->currentDimensions['height'];
    $newHeight                         = $this->currentDimensions['width'];
    $this->oldImage                    = $this->workingImage;
    $this->currentDimensions['width']  = $newWidth;
    $this->currentDimensions['height'] = $newHeight;

    return $this;
  }

  /**
   * @inheritdoc
   */
  public function save($filename = null, $allframes = false)
  {
    if (empty($filename)) $filename = $this->filename;
    $finfo     = pathinfo($filename);
    $extension = strtoupper($finfo['extension']);

    if ($extension == 'JPG') {
      $extension = 'JPEG';
    }
    if (!in_array($extension, $this->getSupportedFormat())) {
      throw $this->triggerError(sprintf('Your GD installation does not support "%s" image types!', $extension));
    }

    // make sure the directory is writeable
    if (!is_writeable(dirname($filename))) {
      // try to correct the permissions
      if ($this->options['correctPermissions'] === true) {
        @chmod(dirname($filename), 0777);
        if (!is_writeable(dirname($filename))) {
          throw $this->triggerError('The given directory is not writeable, and could not correct permissions: ' . $filename);
        }
      }
      else {
        throw $this->triggerError('The given directory is not writeable: ' . $filename);
      }
    }

    $func = 'image' . strtolower($extension);
    switch ($extension) {
      case 'JPEG':
        $result = call_user_func($func, $this->oldImage, $filename, $this->options['jpegQuality']);
        break;
      default :
        $result = call_user_func($func, $this->oldImage, $filename);
        break;
    }

    if ($result === false) {
      $this->setError('Unable to save image with the given format.');
    }

    return $result;
  }

  /**
   * @inheritdoc
   */
  public function setFilename($filename)
  {
    return $this->readImage($filename);
  }

  /**
   * Calculates a new width and height for the image based on $this->maxWidth and the provided dimensions
   *
   * @param int $width
   * @param int $height
   *
   * @return array
   */
  protected function calcHeight($width, $height)
  {
    $newHeightPercentage = (100 * $this->maxHeight) / $height;
    $newWidth            = ($width * $newHeightPercentage) / 100;

    return array('width' => ceil($newWidth), 'height' => ceil($this->maxHeight));
  }

  /**
   * Calculates the new image dimensions
   *
   * These calculations are based on both the provided dimensions and $this->maxWidth and $this->maxHeight
   *
   * @param int $width
   * @param int $height
   */
  protected function calcImageSize($width, $height)
  {
    $newSize = array('width' => $width, 'height' => $height);

    if ($this->maxWidth > 0) {
      $newSize = $this->calcWidth($width, $height);

      if ($this->maxHeight > 0 && $newSize['height'] > $this->maxHeight) {
        $newSize = $this->calcHeight($newSize['width'], $newSize['height']);
      }
    }

    if ($this->maxHeight > 0) {
      $newSize = $this->calcHeight($width, $height);

      if ($this->maxWidth > 0 && $newSize['width'] > $this->maxWidth) {
        $newSize = $this->calcWidth($newSize['width'], $newSize['height']);
      }
    }

    $this->newDimensions = $newSize;
  }

  /**
   * Calculates new dimensions based on $this->percent and the provided dimensions
   *
   * @param int $width
   * @param int $height
   */
  protected function calcImageSizePercent($width, $height)
  {
    if ($this->percent > 0) {
      $this->newDimensions = $this->calcPercent($width, $height);
    }
  }

  /**
   * Calculates new image dimensions, not allowing the width and height to be less
   * than either the max width or height.
   *
   * @param int $width
   * @param int $height
   */
  protected function calcImageSizeStrict($width, $height)
  {
    // first, we need to determine what the longest resize dimension is..
    if ($this->maxWidth >= $this->maxHeight) {
      // and determine the longest original dimension
      if ($width > $height) {
        $newDimensions = $this->calcHeight($width, $height);

        if ($newDimensions['width'] < $this->maxWidth) {
          $newDimensions = $this->calcWidth($width, $height);
        }
      }
      elseif ($height >= $width) {
        $newDimensions = $this->calcWidth($width, $height);

        if ($newDimensions['height'] < $this->maxHeight) {
          $newDimensions = $this->calcHeight($width, $height);
        }
      }
    }
    elseif ($this->maxHeight > $this->maxWidth) {
      if ($width >= $height) {
        $newDimensions = $this->calcWidth($width, $height);

        if ($newDimensions['height'] < $this->maxHeight) {
          $newDimensions = $this->calcHeight($width, $height);
        }
      }
      elseif ($height > $width) {
        $newDimensions = $this->calcHeight($width, $height);

        if ($newDimensions['width'] < $this->maxWidth) {
          $newDimensions = $this->calcWidth($width, $height);
        }
      }
    }

    if (isset($newDimensions)) {
      $this->newDimensions = $newDimensions;
    }
  }

  /**
   * Calculates a new width and height for the image based on $this->percent and the provided dimensions
   *
   * @param int $width
   * @param int $height
   *
   * @return array
   */
  protected function calcPercent($width, $height)
  {
    $newWidth  = ($width * $this->percent) / 100;
    $newHeight = ($height * $this->percent) / 100;

    return array('width' => ceil($newWidth), 'height' => ceil($newHeight));
  }

  /**
   * Calculates a new width and height for the image based on $this->maxWidth and the provided dimensions
   *
   * @param int $width
   * @param int $height
   *
   * @return array
   */
  protected function calcWidth($width, $height)
  {
    $newWidthPercentage = (100 * $this->maxWidth) / $width;
    $newHeight          = ($height * $newWidthPercentage) / 100;

    return array('width' => intval($this->maxWidth), 'height' => intval($newHeight));
  }

  /**
   * Determines the file format by mime-type.
   */
  protected function determineFormat()
  {
    if ($this->isDataStream === true) {
      $this->format = 'STRING';

      return;
    }
    $formatInfo = getimagesize($this->filename);

    // non-image files will return false
    if ($formatInfo === false) {
      if ($this->remoteImage) {
        $this->setError('Could not determine format of remote image: ' . $this->filename);
      }
      else {
        $this->setError(sprintf('File "%s" is not a valid image!', $this->filename));
      }

      return;
    }

    $mimeType = isset($formatInfo['mime']) ? $formatInfo['mime'] : null;

    if (!empty($mimeType)) {
      $tmp    = explode('/', $mimeType);
      $format = strtoupper(end($tmp));
    }
    else {
      $this->setError('Image format is not supported: ' . $mimeType);

      return;
    }

    if ($format == 'JPG' || $format == 'JPEG') {
      $this->format = 'JPEG';
    }
    else {
      $this->format = $format;
    }
  }

  /**
   * Preserves the alpha or transparency for PNG and GIF files.
   *
   * Alpha / transparency will not be preserved if the appropriate options are set to false.
   * Also, the GIF transparency is pretty skunky (the results aren't awesome), but it works like a
   * champ... that's the nature of GIFs tho, so no huge surprise.
   */
  protected function preserveAlpha()
  {
    if ($this->format == 'PNG' && $this->options['preserveAlpha'] === true) {
      imagealphablending($this->workingImage, false);

      $colorTransparent = imagecolorallocatealpha($this->workingImage,
                                                  $this->options['alphaMaskColor'][0],
                                                  $this->options['alphaMaskColor'][1],
                                                  $this->options['alphaMaskColor'][2],
                                                  0
      );

      imagefill($this->workingImage, 0, 0, $colorTransparent);
      imagesavealpha($this->workingImage, true);
    }
    // preserve transparency in GIFs... this is usually pretty rough tho
    if ($this->format == 'GIF' && $this->options['preserveTransparency'] === true) {
      $colorTransparent = imagecolorallocate($this->workingImage,
                                             $this->options['transparencyMaskColor'][0],
                                             $this->options['transparencyMaskColor'][1],
                                             $this->options['transparencyMaskColor'][2]
      );

      imagecolortransparent($this->workingImage, $colorTransparent);
      imagetruecolortopalette($this->workingImage, true, 256);
    }
  }

  /**
   * Makes sure the correct GD implementation exists for the file type.
   */
  protected function verifyFormatCompatiblity()
  {
    $gdInfo = gd_info();

    switch ($this->format) {
      case 'GIF':
        $isCompatible = $gdInfo['GIF Create Support'];
        break;
      case 'JPG':
      case 'JPEG':
        $isCompatible = (isset($gdInfo['JPG Support']) || isset($gdInfo['JPEG Support'])) ? true : false;
        $this->format = 'JPEG';
        break;
      case 'PNG':
      case 'XBM':
      case 'XPM':
      case 'WBMP':
        $isCompatible = $gdInfo[$this->format . ' Support'];
        break;
      default:
        $isCompatible = false;
        break;
    }

    if (!$isCompatible) {
      $isCompatible = $gdInfo['JPEG Support'];

      if (!$isCompatible) {
        throw $this->triggerError(sprintf('Your GD installation does not support "%s" image types!', $this->format));
      }
    }
  }

}