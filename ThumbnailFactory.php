<?php
/**
 * This file is part of Bamboo CMS Application Framework.
 *
 * (c) Ahmad Fajar <ahmad.fajar234@yahoo.com>
 *
 */

// namespace Bamboo\CoreBundle\Thumbnail;


/**
 * Class ThumbnailFactory
 *
 * @author    Ahmad Fajar
 * @since     08/02/2014, modified: 14/03/1014 01:26
 * @version   1.0.0
 * @category  CoreBundle
 * @package   Bamboo\CoreBundle\Thumbnail
 */
final class ThumbnailFactory
{
  /**
   * Create a thumbnail interface in a handy ways.
   *
   * @param null|string $filename the image location
   * @param array       $options  processing parameters
   * @param bool        $isDataStream
   *
   * @return ThumbnailInterface
   * @throws \RuntimeException
   */
  public static function create($filename = null, array $options = array(), $isDataStream = false)
  {
    if (extension_loaded('gmagick')) {
      return new GmagickThumbnail($filename, $options, $isDataStream);
    }
    elseif (extension_loaded('imagick')) {
      return new ImagickThumbnail($filename, $options, $isDataStream);
    }
    elseif (extension_loaded('gd')) {
      return new GDThumbnail($filename, $options, $isDataStream);
    }

    throw new \RuntimeException(
      "The installed PHP doesn't support image manipulation. Please install gmagick or imagick or GD extension.",
      RUNTIME_ERROR
    );
  }

} 