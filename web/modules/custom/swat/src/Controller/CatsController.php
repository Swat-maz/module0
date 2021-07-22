<?php

namespace Drupal\swat\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\file\Entity\File;

/**
 * Defines CatsController class.
 */
class CatsController extends ControllerBase {

  /**
   * Display the markup.
   *
   * @return array
   *   Return markup array.
   */
  public function load() {
    $simpleform = \Drupal::formBuilder()->getForm('Drupal\swat\Form\CatsForm');
    return [
      $simpleform,
    ];
  }

  /**
   * Show information from db.
   */
  public function show() {
    $form = $this->load();
    $conn = Database::getConnection();
    $query = $conn->select('swat', 's');
    $query->fields('s', ['id', 'name', 'email', 'timestamp', 'photo']);
    $query->orderBy('s.timestamp', 'DESC');
    $data = $query->execute()->fetchAllAssoc('id');
    $data = json_decode(json_encode($data), TRUE);
    $result = [];
    foreach ($data as $value) {
      $full_name = $value['name'];
      $id = $value['id'];
      $email = $value['email'];
      $timestamp = $value['timestamp'];
      $time = date('d/m/Y G:i:s', $timestamp);
      $file = File::load($value['photo']);
      $picture = [
        '#type' => 'image',
        '#theme' => 'image_style',
        '#style_name' => 'large',
        '#uri' => $file->getFileUri(),
      ];
      $result[] = [
        "id" => $id,
        "name" => $full_name,
        "email" => $email,
        "photo" => $picture,
        "time" => $time,
        "uri" => file_url_transform_relative(file_create_url($file->getFileUri())),
      ];
    }
    return [
      '#theme' => 'swattcat',
      '#items' => $result,
      '#title' => $this->t('Hello! You can add here a photo of your cat.'),
      '#form' => $form,
    ];
  }

}
