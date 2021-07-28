<?php

namespace Drupal\swat\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\file\Entity\File;

/**
 * Defines ResponseController class.
 */
class ResponseController extends ControllerBase {

  /**
   * Rendering form.
   */
  public function load(): array {
    $simpleform = Drupal::formBuilder()
      ->getForm('Drupal\swat\Form\ResponseForm');
    return [
      $simpleform,
    ];
  }

  /**
   * Show information from database.
   */
  public function show(): array {
    $form = $this->load();
    $constn = Database::getConnection();
    $query = $constn->select('swat', 's');
    $query->fields('s', [
      'id',
      'name',
      'email',
      'timestamp',
      'photo',
      'avatar',
      'number',
      'feedback',
    ]);
    $query->orderBy('s.timestamp', 'DESC');
    $data = $query->execute()->fetchAllAssoc('id');
    $result = [];
    $data = json_decode(json_encode($data), TRUE);
    foreach ($data as $value) {
      $full_name = $value['name'];
      $id = $value['id'];
      $email = $value['email'];
      $timestamp = $value['timestamp'];
      $phone = $value['number'];
      $time = date('F/d/Y G:i:s', $timestamp);
      $feedback = $value['feedback'];
      $avatarphoto = File::load($value['avatar']);
      $feedbackphoto = File::load($value['photo']);
      if ($avatarphoto !== NULL) {
        $ava = $avatarphoto->getFileUri();
      }
      else {
        $ava = 'public://default/default.jpeg';
      }
      if ($feedbackphoto !== NULL) {
        $feedfoto = $feedbackphoto->getFileUri();
      }
      else {
        $feedfoto = '';
      }
      $avatarka = [
        '#type' => 'image',
        '#theme' => 'image_style',
        '#style_name' => 'medium',
        '#uri' => $ava,
      ];
      $userphoto = [
        '#type' => 'image',
        '#theme' => 'image_style',
        '#style_name' => 'medium',
        '#uri' => $feedfoto,
      ];
      $result[] = [
        "id" => $id,
        "name" => $full_name,
        "email" => $email,
        "feedback" => $feedback,
        "photo" => $userphoto,
        "ava" => $avatarka,
        "time" => $time,
        "phone" => $phone,
        "uri" => file_url_transform_relative(file_create_url($feedfoto)),
      ];
    }
    return [
      '#form' => $form,
      '#theme' => 'swat',
      '#items' => $result,
    ];
  }

}
