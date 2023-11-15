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

    $width = $this->configuration['width'] ?? 500;

    $filename = basename($path);

    // Read the Jp2.
    try {
      $cmd = "convert " . $path . " -resize " . $width . "x" . $width . " /tmp/" . $filename . ".jpg";
      exec($cmd);
    }
    catch (\ImagickException $e) {
      $this->logger->warning($e->getMessage());
      return NULL;
    }
    $format = ".jpg";
    $im = new \Imagick();

    if(file_exists("/tmp/" . $filename . ".jpg")){
      $im->readimage("/tmp/" . $filename . ".jpg");
      $image = $im->getImageBlob();
      $im->clear();
      $im->destroy();
       // Return a new managed file object using the generated thumbnail.
      return \Drupal::service('file.repository')->writeData($image, $sourceUri . $format);
    }

    $sourceUri = \Drupal::config('media.settings')->get('icon_base_uri') . '/' . 'generic.png';
    $path = $this->fileSystem->realpath($sourceUri);
    $im->readimage($path);
    $image = $im->getImageBlob();
    $im->clear();
    $im->destroy();
    // Return generic thumbnail.
    return \Drupal::service('file.repository')->writeData($image, $sourceUri);
  }

}
