<?php

namespace Drupal\swat\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Implements an admin form.
 */
class AdminForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $conn = Database::getConnection();
    $query = $conn->select('swat', 's');
    $query->fields('s', ['id', 'name', 'email', 'timestamp', 'photo', 'avatar', 'number', 'feedback']);
    $query->orderBy('s.timestamp', 'DESC');
    $data = $query->execute()->fetchAllAssoc('id');
    $data = json_decode(json_encode($data), TRUE);
    $header = [
      'name' => t('User name'),
      'ava' => t('User avatar'),
      'feedback' => t('Feedback'),
      'photo' => t('Photo'),
      'email' => t('Email'),
      'number' => t('Phone number'),
      'time' => t('Added'),
      'delete' => '',
      'edit' => '',
    ];
    $result = [];
    foreach ($data as $value) {
      $full_name = $value['name'];
      $id = $value['id'];
      $email = $value['email'];
      $timestamp = $value['timestamp'];
      $time = date('F/d/Y G:i:s', $timestamp);
      $avatarphoto = File::load($value['avatar']);
      $feedbackphoto = File::load($value['photo']);
      $delete = [
        'delete' => t("<a class=\"btn delete btn-outline-danger use-ajax\" data-dialog-options='{\"width\":400}' data-dialog-type=\"modal\" href=\"/response/delete/$id?destination=/admin/structure/response\">Delete</a>"),
      ];
      $edit = [
        'edit' => t("<a class=\"btn edit btn-outline-warning use-ajax\" data-dialog-type=\"modal\" data-dialog-options='{\"width\":400}' href=\"/response/edit/$id?destination=/admin/structure/response\">Edit</a>"),
      ];
      if ($avatarphoto !== null) {
        $ava = $avatarphoto->getFileUri();
      }
      else {
        $ava = 'public://default/default.jpeg';
      }
      if ($feedbackphoto !== null) {
        $feedfoto = $feedbackphoto->getFileUri();
      }
      else {
        $feedfoto = '';
      }
      $avatarka = [
        'data' => [
          '#type' => 'image',
          '#theme' => 'image_style',
          '#style_name' => 'thumbnail',
          '#uri' => $ava,
        ],
      ];
      $userphoto = [
        'data' => [
          '#type' => 'image',
          '#theme' => 'image_style',
          '#style_name' => 'thumbnail',
          '#uri' => $feedfoto,
        ],
      ];
      $result[] = [
        "id" => $id,
        "name" => $full_name,
        "ava" => $avatarka,
        "feedback" => $value['feedback'],
        "photo" => $userphoto,
        "email" => $email,
        "number" => $value['number'],
        "time" => $time,
        "delete" => $delete,
        "edit" => $edit,
      ];
    }

    $form['table'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $result,
      '#empty' => t('No feedbacks found'),
    ];

    $form['action']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete selected'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form['table']['#value'] == NULL) {
      $form_state->setErrorByName('title', $this->t('Choose what you want to delete!'));
    }
  }

  /**
   * {@inheritdoc}
   * Delete selected records.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $chekboxes = $form['table']['#value'];
    foreach ($chekboxes as $rows) {
      $allIdDelete[] = $form['table']['#options'][$rows]["id"];
    }
    foreach ($allIdDelete as $rows) {
      $query = Drupal::database()->delete('swat');
      $query->condition('id', $rows);
      $query->execute();
    }
  }

}
