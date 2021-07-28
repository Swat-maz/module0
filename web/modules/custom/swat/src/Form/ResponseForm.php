<?php

/**
 * Contains \Drupal\swat\Form\ResponseForm.
 *
 * @file
 */

namespace Drupal\swat\Form;

use Drupal;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\file\Entity\File;

/**
 * Add my class.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class  ResponseForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'response_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your name:'),
      '#maxlength' => 100,
      '#description' => $this->t('The minimum length of the name is 2 characters, and the maximum is 100.'),
      '#required' => TRUE,
    ];

    $form['email'] = [
      '#title' => $this->t('Email:'),
      '#type' => 'email',
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::myAjaxEmailCallback',
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];

    $form['telephone'] = [
      '#title' => $this->t('Telephone number:'),
      '#type' => 'tel',
      '#placeholder' => '+380123456789',
      '#pattern' => '[+][3][8][0-9]{10}',
      '#maxlength' => 13,
      '#description' => $this->t('Your phone number'),
      '#required' => TRUE,
    ];

    $form['feedback'] = [
      '#title' => $this->t('Your feedback:'),
      '#type' => 'textarea',
      '#required' => TRUE,
    ];
    $form['avatar'] = [
      '#type' => 'managed_file',
      '#title' => t('Your avatar:'),
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg'],
        'file_validate_size' => [2097152],
      ],
      '#theme' => 'image_widget',
      '#preview_image_style' => 'medium',
      '#upload_location' => 'public://avatar',
    ];

    $form['image'] = [
      '#type' => 'managed_file',
      '#title' => t('Image for yours feedback:'),
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg'],

        'file_validate_size' => [5242880],
      ],
      '#theme' => 'image_widget',
      '#preview_image_style' => 'medium',
      '#upload_location' => 'public://image',
    ];

    $form['system_messages'] = [
      '#markup' => '<div id="form-system-messages"></div>',
      '#weight' => 100,
    ];

    $form['action']['#type'] = 'actions';

    $form['action']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send feedback'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::myAjaxCallback',
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
    if (!isset($message['#message_list']['error']))
    $ajax_response->addCommand(new RedirectCommand('/response'));
    return $ajax_response;
  }

  /**
   * Validation email with ajax.
   */
  public function myAjaxEmailCallback(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    if (filter_var($form_state->getValue('email'), FILTER_VALIDATE_EMAIL) && !preg_match('/[#$%^&*()+=!\[\]\';,\/{}|":<>?~\\\\0-9]/', $form_state->getValue('email'))) {
      $response->addCommand(new HtmlCommand('#edit-email--description', 'Your email address is correct'));
    }
    else {
      $response->addCommand(new HtmlCommand('#edit-email--description', 'VALUE IS NOT CORRECT'));
    }
    return $response;
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

    Drupal::service('database')->insert('swat')
      ->fields([
        'name' => $form_state->getValue('title'),
        'uid' => $this->currentUser()->id(),
        'email' => $form_state->getValue('email'),
        'feedback' => $form_state->getValue('feedback'),
        'number' => $form_state->getValue('telephone'),
        'photo' => $photo,
        'avatar' => $avatar,
        'timestamp' => time(),
      ])
      ->execute();
    $this->messenger()
      ->addMessage($this->t('Save your feedback'));
    $form_state->setRebuild(FALSE);
  }

}
