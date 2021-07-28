<?php

/**
 * Contains \Drupal\swat\Form\ResponseForm.
 *
 * @file
 */

namespace Drupal\swat\Form;

use Drupal;
use Drupal\Core\Database\Database;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\file\Entity\File;
use Drupal\Core\Ajax\CloseModalDialogCommand;

/**
 * Add my class to delete info from database.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class EditForm extends FormBase {

  /**
   * ID of the item to edit.
   *
   * @var int
   */
  protected $id;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'edit_form';
  }

  /**
   * Take information from database to fill default value in edit form.
   */
  public function getInfo($type) {
    $conn = Database::getConnection();
    $query = $conn->select('swat', 's')
      ->condition('id', $this->id);
    $query->fields('s', ['id', 'name', 'email', 'photo', 'avatar', 'number', 'feedback']);
    $data = $query->execute()->fetchAllAssoc('id');
    $data = json_decode(json_encode($data), TRUE);
    foreach ($data as $value) {
      $full_names = $value['name'];
      $emails = $value['email'];
      $files = $value['photo'];
      $phone = $value['number'];
      $feedback = $value['feedback'];
      $ava = $value['avatar'];
    }
    if ($type == 1) {
      return $full_names;
    }
    elseif ($type == 2) {
      return $emails;
    }
    elseif ($type == 3) {
      return $files;
    }
    elseif ($type == 4) {
      return $phone;
    }
    elseif ($type == 5) {
      return $feedback;
    }
    elseif ($type == 6) {
      return $ava;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $id = NULL): array {
    $this->id = $id;

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your name:'),
      '#default_value' => $this->getInfo(1),
      '#required' => TRUE,
    ];

    $form['email'] = [
      '#title' => $this->t('Email:'),
      '#default_value' => $this->getInfo(2),
      '#type' => 'email',
      '#required' => TRUE,
    ];

    $form['telephone'] = [
      '#title' => $this->t('Telephone number:'),
      '#type' => 'tel',
      '#placeholder' => '+380123456789',
      '#pattern' => '[+][3][8][0-9]{10}',
      '#maxlength' => 13,
      '#default_value' => $this->getInfo(4),
      '#description' => $this->t('Your phone number'),
      '#required' => TRUE,
    ];

    $form['feedback'] = [
      '#title' => $this->t('Your feedback:'),
      '#type' => 'textarea',
      '#required' => TRUE,
      '#default_value' => $this->getInfo(5),
    ];

    $form['avatar'] = [
      '#type' => 'managed_file',
      '#title' => t('Your avatar:'),
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg'],
        'file_validate_size' => [2097152],
      ],
      '#theme' => 'image_widget',
      '#default_value' => [$this->getInfo(6)],
      '#preview_image_style' => 'medium',
      '#upload_location' => 'public://avatar',
    ];

    $form['image'] = [
      '#type' => 'managed_file',
      '#title' => t('Image:'),
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg'],
        'file_validate_size' => [2097152],
      ],
      '#theme' => 'image_widget',
      '#preview_image_style' => 'medium',
      '#default_value' => [$this->getInfo(3)],
      '#upload_location' => 'public://image',
    ];


    $form['action']['#type'] = 'actions';
    $form['action']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Confirm'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::myAjaxCallback',
        'event' => 'click',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];
    $form['action']['cancel'] = [
      '#type' => 'button',
      '#value' => $this->t('Close'),
      '#button_type' => 'info',
      '#ajax' => [
        'callback' => '::myAjaxCallbackCancel',
        'event' => 'click',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!preg_match('/^[A-Za-z]*$/', $form_state->getValue('title'))) {
      $form_state->setErrorByName('title', $this->t('For name use only letters A-Za-z'));
    }
    if (strlen($form_state->getValue('title')) < 2) {
      $form_state->setErrorByName('title', $this->t('Name is too short.'));
    }
    else {
      $this->messenger()->deleteAll();
    }
  }

  /**
   * Ajax cancel button.
   */
  public function myAjaxCallbackCancel(array &$form, FormStateInterface $form_state): AjaxResponse {
    $command = new CloseModalDialogCommand();
    $response = new AjaxResponse();
    $response->addCommand($command);
    return $response;
  }

  /**
   * Ajax submit button.
   */
  public function myAjaxCallback(array &$form, FormStateInterface $form_state): AjaxResponse {
    $ajax_response = new AjaxResponse();
    $message = [
      '#theme' => 'status_messages',
      '#message_list' => $this->messenger()->all(),
      '#status_headings' => [
        'status' => t('Status message'),
        'error' => t('Error message'),
        'warning' => t('Warning message'),
      ],
    ];
    $messages = Drupal::service('renderer')->render($message);
    $ajax_response->addCommand(new HtmlCommand('#form-system-messages', $messages));
    if (!isset($message['#message_list']['error'])) {
      $currentpage = $_GET['destination'];
      $ajax_response->addCommand(new RedirectCommand($currentpage));
    }
    return $ajax_response;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setUserInput([]);
    $imagea = $form_state->getValue('avatar');
    $image = $form_state->getValue('image');
    if ($imagea !== []) {
      $file = File::load($imagea[0]);
      $file->setPermanent();
      $file->save();
      $avatar = $form_state->getValue('avatar')[0];
    }
    else {
      $avatar = 0;
    }
    if ($image !== []) {
      $file = File::load($image[0]);
      $file->setPermanent();
      $file->save();
      $photo = $form_state->getValue('image')[0];
    }
    else {
      $photo = 0;
    }
    Drupal::service('database')->update('swat')
      ->condition('id', $this->id)
      ->fields([
        'name' => $form_state->getValue('title'),
        'email' => $form_state->getValue('email'),
        'feedback' => $form_state->getValue('feedback'),
        'number' => $form_state->getValue('telephone'),
        'photo' => $photo,
        'avatar' => $avatar,
      ])
      ->execute();
    $this->messenger()
      ->addMessage($this->t('Your feedback update'));
    $form_state->setRebuild(FALSE);
  }

}
