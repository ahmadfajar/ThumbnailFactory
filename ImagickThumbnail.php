<?php
/**
 * This file is part of Bamboo CMS Application Framework.
 *
 * (c) Ahmad Fajar <ahmad.fajar234@yahoo.com>
 *
 */

// namespace Bamboo\CoreBundle\Thumbnail;


/**
 * Class ImagickThumbnail is a class for processing image using php-imagick extension.
 *
 * @author    Ahmad Fajar
 * @since     08/02/2014, modified: 14/03/2014 04:16
 * @version   1.0.0
 * @category  CoreBundle
 * @package   Bamboo\CoreBundle\Thumbnail
 */
class ImagickThumbnail extends AbstractThumbnail
{
  /**
   * The current dimensions of the image.
   *
   * @var array
   */
  protected $currentDimensions;
  /**
   * @var \Imagick
   */
  protected $imagick;
  /**
   * The new, calculated dimensions of the image.
   *
   * @var array
   */
  protected $newDimensions;
  /**
   * The percentage to resize the image by
   *
   * @var int
   */
  protected $percent;

  /**
   * Construct the ImagickThumbnail class object.
   *
   * @param null|string $fileName the image location
   * @param array       $options  processing parameters
   * @param bool        $isDataStream
   *
   * @throws \RuntimeException
   */
  public function __construct($fileName = null, array $options = array(), $isDataStream = false)
  {
    if (!extension_loaded('imagick')) {
      throw $this->triggerError('The Imagick extension must be loaded before using this thumbnail interface!');
    }

    $this->filename     = $fileName;
    $this->isDataStream = $isDataStream;
    $this->imagick      = new \Imagick();

    if (!empty($this->filename)) {
      $this->readImage($fileName);
    }
    $this->setOptions($options);
  }

  /**
   * Magic method, calls a method from the Imagick object.
   *
   * @param string $name      the method name
   * @param array  $arguments function input parameters
   *
   * @return mixed
   * @throws \BadMethodCallException
   */
  public function __call($name, $arguments)
  {
    if (method_exists($this->imagick, $name)) {
      return call_user_func_array(array($this->imagick, $name), $arguments);
    }

    throw new \BadMethodCallException(sprintf('Unknown function calls: "%s"!', $name), RUNTIME_ERROR);
  }

  /**
   * Clean the resource identifier before the object is destroyed.
   */
  public function __destruct()
  {
    $this->imagick->destroy();
    unset($this->imagick);
  }

  /**
   * @inheritdoc
   */
  public function adaptiveResize($maxWidth, $maxHeight)
  {
    if (!is_numeric($maxWidth) || $maxWidth == 0) {
      throw $this->triggerError('$maxWidth must be numeric and greater than zero!', UNKNOWN_ERROR);
    }
    if (!is_numeric($maxHeight) || $maxHeight == 0) {
      throw $this->triggerError('$maxHeight must be numeric and greater than zero!', UNKNOWN_ERROR);
    }

    $result = $this->imagick->cropthumbnailimage($maxWidth, $maxHeight);
    $this->imagick->enhanceimage();
    $this->imagick->normalizeimage(\Imagick::CHANNEL_OPACITY & \Imagick::CHANNEL_ALPHA);

    $width                   = $this->imagick->getimagewidth();
    $height                  = $this->imagick->getimageheight();
    $this->currentDimensions = array('width' => $width, 'height' => $height);

    if ($result === false) {
      $this->setError('Unable to adaptiveResize the image.');
    }

    return $this;
  }

  /**
   * @inheritdoc
   */
  public function cropImage($width, $height, $x, $y)
  {
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

    $result                  = $this->imagick->cropimage($width, $height, $x, $y);
    $width                   = $this->imagick->getimagewidth();
    $height                  = $this->imagick->getimageheight();
    $this->currentDimensions = array('width' => $width, 'height' => $height);

    if ($result === false) {
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
    $result = $this->imagick->flipimage();
    if ($result === false) {
      $this->setError('Unable to flip the image vertically.');
    }

    return $this;
  }

  /**
   * @inheritdoc
   */
  public function flopImage()
  {
    $result = $this->imagick->flopimage();
    if ($result === false) {
      $this->setError('Unable to flop the image horizontally.');
    }

    return $this;
  }

  /**
   * Get the Imagick supported image format.
   *
   * @return array
   */
  public function getSupportedFormat()
  {
    return $this->imagick->queryformats();
  }

  /**
   * @inheritdoc
   */
  public function readImage($filename)
  {
    if (is_readable($filename)) {
      $this->filename = $filename;
      $finfo          = pathinfo($filename);
      $extension      = strtoupper($finfo['extension']);

      if (!in_array($extension, $this->getSupportedFormat())) {
        throw $this->triggerError(sprintf('Your Imagick installation does not support "%s" image types!', $extension));
      }

      $this->imagick->readimage($filename);
      $width                   = $this->imagick->getimagewidth();
      $height                  = $this->imagick->getimageheight();
      $this->currentDimensions = array('width' => $width, 'height' => $height);
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

//    if ($maxHeight > $this->currentDimensions['height'] || $maxWidth > $this->currentDimensions['width']) {
      $result = $this->imagick->resizeimage($maxWidth, $maxHeight, \Imagick::FILTER_MITCHELL, 0.65);
/*    }
    else {
      $result = $this->imagick->thumbnailimage($maxWidth, $maxHeight);
    }*/
    $this->imagick->enhanceimage();
//    $this->imagick->normalizeimage(\Imagick::CHANNEL_OPACITY & \Imagick::CHANNEL_ALPHA);

    if ($result === false) {
      $this->setError('Unable to resize the image.');
    }

    $width                   = $this->imagick->getimagewidth();
    $height                  = $this->imagick->getimageheight();
    $this->currentDimensions = array('width' => $width, 'height' => $height);

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
    $this->resize($this->newDimensions['width'], $this->newDimensions['height']);

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

    $result                  = $this->imagick->rotateimage(new \ImagickPixel(), $degrees);
    $width                   = $this->imagick->getimagewidth();
    $height                  = $this->imagick->getimageheight();
    $this->currentDimensions = array('width' => $width, 'height' => $height);

    if ($result === false) {
      $this->setError('Unable to rotate the image.');
    }

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

    if (!in_array($extension, $this->getSupportedFormat())) {
      throw $this->triggerError(sprintf('Your Imagick installation does not support "%s" image types!', $extension));
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

    $nImg = $this->imagick->getnumberimages();
    if ($nImg > 1 && $allframes) {
      $result = $this->imagick->writeimages($filename, true);
    }
    elseif ($nImg > 1 && !$allframes) {
      $result = $this->imagick->writeimages($filename, false);
    }
    else {
      $result = $this->imagick->writeimage($filename);
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

}