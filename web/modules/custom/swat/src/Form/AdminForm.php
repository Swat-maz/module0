<?php

namespace Drupal\swat\Form;

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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $conn = Database::getConnection();
    $query = $conn->select('swat', 's');
    $query->fields('s', ['id', 'name', 'email', 'timestamp', 'photo']);
    $query->orderBy('s.timestamp', 'DESC');
    $data = $query->execute()->fetchAllAssoc('id');
    $data = json_decode(json_encode($data), TRUE);
    $header = [
      'name' => t('Cat name'),
      'photo' => t('Cat photo'),
      'email' => t('Email'),
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
      $time = date('d/m/Y G:i:s', $timestamp);
      $file = File::load($value['photo']);
      $delete = [
        'delete' => t("<a class=\"btn delete btn-outline-danger use-ajax\" data-dialog-options='{\"width\":400}' data-dialog-type=\"modal\" href=\"/swat/cats/delete/$id?destination=/admin/structure/cats/list\">Delete</a>"),
      ];
      $edit = [
        'edit' => t("<a class=\"btn edit btn-outline-warning use-ajax\" data-dialog-type=\"modal\" data-dialog-options='{\"width\":400}' href=\"/swat/cats/edit/$id?destination=/admin/structure/cats/list\">Edit</a>"),
      ];

      $picture = [
        'data' => [
          '#type' => 'image',
          '#theme' => 'image_style',
          '#style_name' => 'small',
          '#uri' => $file->getFileUri(),
        ],
      ];
      $result[] = [
        "id" => $id,
        "name" => $full_name,
        "email" => $email,
        "photo" => $picture,
        "time" => $time,
        "delete" => $delete,
        "edit" => $edit,
      ];
    }

    $form['table'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $result,
      '#empty' => t('No cats found'),
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
      $form_state->setErrorByName('title', $this->t('Choose what you want to delete0!'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $chekboxes = $form['table']['#value'];
    foreach ($chekboxes as $rows) {
      $allIdDelete[] = $form['table']['#options'][$rows]["id"];
    }
    foreach ($allIdDelete as $rows) {
      $query = \Drupal::database()->delete('swat');
      $query->condition('id', $rows);
      $query->execute();
    }
  }

}
