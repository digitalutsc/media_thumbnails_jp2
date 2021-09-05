<?php

namespace Drupal\media_thumbnails_jp2\Plugin\MediaThumbnail;

use Drupal\media_thumbnails\Plugin\MediaThumbnailBase;

/**
 * Media thumbnail plugin for jp2 documents.
 *
 * @MediaThumbnail(
 *   id = "media_thumbnail_jp2",
 *   label = @Translation("Media Thumbnail Jp2"),
 *   mime = {
 *     "image/jp2",
 *   }
 * )
 */
class MediaThumbnailJp2 extends MediaThumbnailBase {

  /**
   * Creates a managed thumbnail file using the passed source file uri.
   *
   * {@inheritdoc}
   */
  public function createThumbnail($sourceUri) {

    // Check the required php extension.
    if (!extension_loaded('imagick')) {
      $this->logger->warning($this->t('Imagick php extension not loaded.'));
      return NULL;
    }

    // Imagick doesn't support stream wrappers!
    $path = $this->fileSystem->realpath($sourceUri);

    // Read the Jp2.
    $im = new \Imagick();
    try {
      $im->readimage($path);
    }
    catch (\ImagickException $e) {
      $this->logger->warning($e->getMessage());
      return NULL;
    }

    // Handle transparency stuff.
    $im->setImageBackgroundColor('white');
    $im->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
    try {
      $im->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
    }
    catch (\ImagickException $e) {
      $this->logger->warning($e->getMessage());
      return NULL;
    }

    // Resize the thumbnail to the globally configured width.
    $width = $this->configuration['width'] ?? 500;
    if ($im->getImageWidth() > $width) {
      try {
        $im->scaleImage($width, 0);
      }
      catch (\ImagickException $e) {
        $this->logger->warning($e->getMessage());
        return NULL;
      }
    }

    // Convert the image to JPG.
    $im->setImageFormat('jpg');
    $image = $im->getImageBlob();
    $im->clear();
    $im->destroy();

    // Return a new managed file object using the generated thumbnail.
    return file_save_data($image, $sourceUri . '.jpg');

  }

}
