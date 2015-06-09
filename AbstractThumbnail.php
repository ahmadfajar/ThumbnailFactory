<?php
/**
 * This file is part of Bamboo CMS Application Framework.
 *
 * (c) Ahmad Fajar <ahmad.fajar234@yahoo.com>
 *
 */

// namespace Bamboo\CoreBundle\Thumbnail;


/**
 * Class AbstractThumbnail is an abstract class for processing image.
 *
 * @author    Ahmad Fajar
 * @since     08/02/2014, modified: 08/03/2014 16:59
 * @version   1.0.0
 * @category  CoreBundle
 * @package   Bamboo\CoreBundle\Thumbnail
 */
abstract class AbstractThumbnail implements ThumbnailInterface
{
  /**
   * Error containers
   *
   * @var array
   */
  protected $errors = array();
  /**
   * @var string
   */
  protected $filename;
  /**
   * The file format or its mime-type
   *
   * @var string
   */
  protected $format;
  /**
   * Whether or not the current image is an actual file, or the raw file data.
   *
   * By "raw file data" it's meant that we're actually passing the result of something
   * like file_get_contents() or perhaps from a database blob.
   *
   * @var bool
   */
  protected $isDataStream = false;
  /**
   * Options parameters for image processing
   *
   * @var array
   */
  protected $options = array();
  /**
   * Whether or not the image is hosted remotely
   *
   * @var bool
   */
  protected $remoteImage = false;

  /**
   * Whether or not the current image is an actual file, or the raw file data.
   *
   * By "raw file data" it's meant that we're actually passing the result of something
   * like file_get_contents() or perhaps from a database blob.
   *
   * @return bool
   */
  public function IsDataStream()
  {
    return $this->isDataStream;
  }

  /**
   * Get last error message.
   *
   * @return string
   */
  public function getError()
  {
    return end($this->errors);
  }

  /**
   * Get all error messages.
   *
   * @return array
   */
  public function getErrors()
  {
    return $this->errors;
  }

  /**
   * Get current image filename.
   *
   * @return string
   */
  public function getFilename()
  {
    return $this->filename;
  }

  /**
   * Read image from an actual file.
   *
   * @param string $filename the image location
   *
   * @return $this fluent interface, return itself
   * @throws \RuntimeException
   * @throws \InvalidArgumentException
   */
  public function setFilename($filename)
  {
    $this->filename = $filename;

    return $this;
  }

  /**
   * Get the image file format.
   *
   * @return string
   */
  public function getFormat()
  {
    return $this->format;
  }

  /**
   * Set image file format.
   *
   * @param string $format image format: GIF, JPEG, PNG, etc...
   *
   * @return $this fluent interface, return itself
   */
  public function setFormat($format)
  {
    $this->format = $format;

    return $this;
  }

  /**
   * Get object options parameters.
   *
   * @return array
   */
  public function getOptions()
  {
    return $this->options;
  }

  /**
   * Set object state from options array.
   *
   * @param  array $options
   *
   * @return $this fluent interface, return itself
   */
  public function setOptions(array $options = null)
  {
    $defaultOptions = array
    (
      'resizeUp'              => false,
      'jpegQuality'           => 100,
      'correctPermissions'    => false,
      'preserveAlpha'         => true,
      'alphaMaskColor'        => array(255, 255, 255),
      'preserveTransparency'  => true,
      'transparencyMaskColor' => array(0, 0, 0)
    );
    if (!empty($this->options)) {
      $defaultOptions = $this->options;
    }

    $options = array_merge($defaultOptions, $options);
    foreach ($options as $key => $value) {
      $this->setOption($key, $value);
    }

    return $this;
  }

  /**
   * Test whether there is an error while processing image or not.
   *
   * @return bool
   */
  public function hasErrors()
  {
    return (empty($this->errors) ? false : true);
  }

  /**
   * Set the current image is an actual file, or the raw file data.
   *
   * By "raw file data" it's meant that we're actually passing the result of something
   * like file_get_contents() or perhaps from a database blob.
   *
   * @param boolean $isDataStream
   *
   * @return $this  fluent interface, return itself
   */
  public function setIsDataStream($isDataStream)
  {
    $this->isDataStream = $isDataStream;

    return $this;
  }

  /**
   * Set single option for the object.
   *
   * @param string $key   property name
   * @param string $value property value
   *
   * @return $this fluent interface, return itself
   */
  public function setOption($key, $value)
  {
    $method = 'set' . ucfirst($key);
    if (method_exists($this, $method)) {
      // Setter exists; use it
      $this->$method ($value);
      $this->options[$key] = $value;
    }
    else {
      $this->options[$key] = $value;
    }

    return $this;
  }

  /**
   * Set error message.
   *
   * @param string $message
   */
  protected function setError($message)
  {
    $this->errors[] = $message;
  }

  /**
   * Sets the error message and throw a RuntimeException or InvalidArgumentException.
   *
   * @param string $message
   * @param int    $const   the error type
   *
   * @return \RuntimeException|\InvalidArgumentException
   */
  protected function triggerError($message, $const = RUNTIME_ERROR)
  {
    $this->setError($message);

    if ($const === RUNTIME_ERROR) {
      return new \RuntimeException($message, RUNTIME_ERROR);
    }

    return new \InvalidArgumentException($message, RUNTIME_ERROR);
  }

} 