<?php
/**
 * This file is part of Bamboo CMS Application Framework.
 *
 * (c) Ahmad Fajar <ahmad.fajar234@yahoo.com>
 *
 */

// namespace Bamboo\CoreBundle\Thumbnail;


/**
 * Class GmagickThumbnail is a class for processing image using php-gmagick extension.
 *
 * @author    Ahmad Fajar
 * @since     13/02/2014, modified: 14/03/2014 02:09
 * @version   1.0.0
 * @category  CoreBundle
 * @package   Bamboo\CoreBundle\Thumbnail
 */
class GmagickThumbnail extends AbstractThumbnail
{
  /**
   * The current dimensions of the image.
   *
   * @var array
   */
  protected $currentDimensions;
  /**
   * @var \Gmagick
   */
  protected $gmagick;
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
   * The percentage to resize the image by
   *
   * @var int
   */
  protected $percent;

  /**
   * Construct the GmagickThumbnail class object.
   *
   * @param null|string $fileName the image location
   * @param array       $options  processing parameters
   * @param bool        $isDataStream
   *
   * @throws \RuntimeException
   */
  public function __construct($fileName = null, array $options = array(), $isDataStream = false)
  {
    if (!extension_loaded('gmagick')) {
      throw $this->triggerError('The Gmagick extension must be loaded before using this thumbnail interface!');
    }

    $this->filename     = $fileName;
    $this->isDataStream = $isDataStream;
    $this->gmagick      = new \Gmagick();

    if (!empty($this->filename)) {
      $this->readImage($fileName);
    }
    $this->setOptions($options);
  }

  /**
   * Magic method, calls a method from the Gmagick object.
   *
   * @param string $name      the method name
   * @param array  $arguments function input parameters
   *
   * @return mixed
   * @throws \BadMethodCallException
   */
  public function __call($name, $arguments)
  {
    if (method_exists($this->gmagick, $name)) {
      return call_user_func_array(array($this->gmagick, $name), $arguments);
    }

    throw new \BadMethodCallException(sprintf('Unknown function calls: "%s"!', $name), RUNTIME_ERROR);
  }

  /**
   * Clean the resource identifier before the object is destroyed.
   */
  public function __destruct()
  {
    $this->gmagick->destroy();
    unset($this->gmagick);
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

    $this->maxHeight = (int)$maxHeight;
    $this->maxWidth  = (int)$maxWidth;

    $this->calcImageSizeStrict($this->currentDimensions['width'], $this->currentDimensions['height']);
    $this->resize($this->newDimensions['width'], $this->newDimensions['height']);

    // reset the max dimensions...
    $this->maxHeight = (int)$maxHeight;
    $this->maxWidth  = (int)$maxWidth;

    // now, figure out how to crop the rest of the image...
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

    $this->cropImage($cropWidth, $cropHeight, $cropX, $cropY);

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

    $result                  = $this->gmagick->cropimage($width, $height, $x, $y);
    $width                   = $this->gmagick->getimagewidth();
    $height                  = $this->gmagick->getimageheight();
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
    $result = $this->gmagick->flipimage();
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
    $result = $this->gmagick->flopimage();
    if ($result === false) {
      $this->setError('Unable to flop the image horizontally.');
    }

    return $this;
  }

  /**
   * @inheritdoc
   */
  public function getSupportedFormat()
  {
    return $this->gmagick->queryformats();
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
        throw $this->triggerError(sprintf('Your Gmagick installation does not support "%s" image types!', $extension));
      }

      $this->gmagick->readimage($filename);
      $width                   = $this->gmagick->getimagewidth();
      $height                  = $this->gmagick->getimageheight();
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

    $result = $this->gmagick->resizeimage($maxWidth, $maxHeight, \Gmagick::FILTER_MITCHELL, 0.65);
    $this->gmagick->enhanceimage();
    $this->gmagick->normalizeimage(\Gmagick::CHANNEL_OPACITY & \Gmagick::CHANNEL_MATTE);

    $width                   = $this->gmagick->getimagewidth();
    $height                  = $this->gmagick->getimageheight();
    $this->currentDimensions = array('width' => $width, 'height' => $height);

    if ($result === false) {
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

    $result                  = $this->gmagick->rotateimage(new \GmagickPixel(), $degrees);
    $width                   = $this->gmagick->getimagewidth();
    $height                  = $this->gmagick->getimageheight();
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

    $nImg = $this->gmagick->hasnextimage();
    if ($nImg && $allframes) {
      $result = $this->gmagick->writeimage($filename, true);
    }
    elseif ($nImg && !$allframes) {
      $result = $this->gmagick->writeimage($filename, false);
    }
    else {
      $result = $this->gmagick->write($filename);
    }
    if ($result === false) {
      $this->setError('Unable to save image with the given format.');
    }

    return $result;
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

}