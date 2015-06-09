<?php
/**
 * This file is part of Bamboo CMS Application Framework.
 *
 * (c) Ahmad Fajar <ahmad.fajar234@yahoo.com>
 *
 */

// namespace Bamboo\CoreBundle\Thumbnail;


/**
 * Interface ThumbnailInterface is a class interface for processing image.
 *
 * @author    Ahmad Fajar
 * @since     08/02/2014, modified: 08/03/2014 22:31
 * @version   1.0.0
 * @category  CoreBundle
 * @package   Bamboo\CoreBundle\Thumbnail
 */
interface ThumbnailInterface
{
  /**
   * Adaptively Resizes the Image.
   *
   * This function attempts to get the image to as close to the provided dimensions as possible, and then crops the
   * remaining overflow (from the center) to get the image to be the size specified.
   *
   * @param int $maxWidth  The maximum width of the image in pixels
   * @param int $maxHeight The maximum height of the image in pixels
   *
   * @return $this fluent interface, returns itself
   * @throws \InvalidArgumentException
   */
  public function adaptiveResize($maxWidth, $maxHeight);

  /**
   * Vanilla Cropping - Crops from x,y with specified width and height.
   *
   * @param int $width  The width of the crop
   * @param int $height The height of the crop
   * @param int $x      The X coordinate of the cropped region's top left corner
   * @param int $y      The Y coordinate of the cropped region's top left corner
   *
   * @return $this fluent interface, returns itself
   * @throws \InvalidArgumentException
   */
  public function cropImage($width, $height, $x, $y);

  /**
   * Crops an image from the center with provided dimensions.
   *
   * If no height is given, the width will be used as a height, thus creating a square crop.
   *
   * @param int      $width  The width of the image in pixels
   * @param null|int $height The height of the image in pixels
   *
   * @return $this fluent interface, returns itself
   * @throws \InvalidArgumentException
   */
  public function cropImageFromCenter($width, $height = null);

  /**
   * Creates a vertical mirror of image.
   *
   * @return $this fluent interface, returns itself
   */
  public function flipImage();

  /**
   * Creates a horizontal mirror image.
   *
   * @return $this fluent interface, returns itself
   */
  public function flopImage();

  /**
   * Get last error message.
   *
   * @return string
   */
  public function getError();

  /**
   * Get all error messages.
   *
   * @return array
   */
  public function getErrors();

  /**
   * Get the Imagick supported image format.
   *
   * @return array
   */
  public function getSupportedFormat();

  /**
   * Test whether there is an error while processing image or not.
   *
   * @return bool
   */
  public function hasErrors();

  /**
   * Read image from an actual file.
   *
   * @param string $filename the image location or filename
   *
   * @return $this fluent interface, returns itself
   * @throws \RuntimeException
   * @throws \InvalidArgumentException
   */
  public function readImage($filename);

  /**
   * Resizes an image to be no larger than $maxWidth or $maxHeight.
   *
   * If either param is set to zero, then that dimension will not be considered as a part of the resize.
   * Additionally, if $this->options['resizeUp'] is set to true (false by default), then this function will
   * also scale the image up to the maximum dimensions provided.
   *
   * @param int $maxWidth  The maximum width of the image in pixels
   * @param int $maxHeight The maximum height of the image in pixels
   *
   * @return $this fluent interface, returns itself
   * @throws \InvalidArgumentException
   */
  public function resize($maxWidth = 0, $maxHeight = 0);

  /**
   * Resizes an image by a given percent uniformly.
   *
   * Percentage should be whole number representation (i.e. 1-100).
   *
   * @param int $percent
   *
   * @return $this fluent interface, returns itself
   * @throws \InvalidArgumentException
   */
  public function resizePercent($percent = 0);

  /**
   * Rotates image either 90 degrees clockwise or counter-clockwise.
   *
   * @param string $direction
   *
   * @return $this fluent interface, returns itself
   * @throws \RuntimeException
   * @throws \InvalidArgumentException
   */
  public function rotateImage($direction = 'CW');

  /**
   * Rotates image with specified number of degrees.
   *
   * @param $degrees
   *
   * @return $this fluent interface, returns itself
   * @throws \RuntimeException
   * @throws \InvalidArgumentException
   */
  public function rotateImageNDegrees($degrees);

  /**
   * Writes an image to the specified filename. If the filename parameter is NULL then it will overwrite
   * the original image.
   *
   * @param string $filename  Filename where to write the image.
   *                          The extension of the filename defines the type of the file
   * @param bool   $allframes If the object contains multiple images/frames whether it will
   *                          write all the frames or not
   *
   * @return bool TRUE on success, otherwise FALSE
   */
  public function save($filename = null, $allframes = false);

} 