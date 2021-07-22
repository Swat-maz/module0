<?php

/**
 * Contains \Drupal\swat\Form\CatsForm.
 *
 * @file
 */

namespace Drupal\swat\Form;

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
class CatsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'cats_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['email'] = [
      '#title' => $this->t('Your email:'),
      '#type' => 'email',
      '#description' => $this->t('Only contain Latin letters, an underscore, or a hyphen.'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::myAjaxEmailCallback',
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your cat’s name:'),
      '#description' => $this->t('The minimum length of the name is 2 characters, and the maximum is 32.'),
      '#required' => TRUE,
    ];

    $form['image'] = [
      '#type' => 'managed_file',
      '#title' => t('Image'),
      '#required' => TRUE,
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg'],
        'file_validate_size' => [2097152],
      ],
      '#theme' => 'image_widget',
      '#preview_image_style' => 'medium',
      '#upload_location' => 'public://photochki',
    ];

    $form['system_messages'] = [
      '#markup' => '<div id="form-system-messages"></div>',
      '#weight' => 100,
    ];

    $form['action']['#type'] = 'actions';

    $form['action']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add cat'),
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
      $form_state->setErrorByName('title', $this->t('For cat name use only letters A-Za-z'));
    }
    if (strlen($form_state->getValue('title')) < 2) {
      $form_state->setErrorByName('title', $this->t('Name is too short.'));
    }
    if (strlen($form_state->getValue('title')) > 32) {
      $form_state->setErrorByName('title', $this->t('Name is too long.'));
    }
    if (filter_var($form_state->getValue('email'), FILTER_VALIDATE_EMAIL) && preg_match('/[#$%^&*()+=!\[\]\';,\/{}|":<>?~\\\\0-9]/', $form_state->getValue('email'))) {
      $form_state->setErrorByName('email', $this->t('Use only contain Latin letters, an underscore, or a hyphen'));
    }
    else {
      $this->messenger()->deleteAll();
    }
  }

  /**
   * Ajax submit button.
   */
  public function myAjaxCallback(array &$form, FormStateInterface $form_state) {
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
    $messages = \Drupal::service('renderer')->render($message);
    $ajax_response->addCommand(new HtmlCommand('#form-system-messages', $messages));
    if (!isset($message['#message_list']['error']))
    $ajax_response->addCommand(new RedirectCommand('/swat/cats'));
    return $ajax_response;
  }

  /**
   * Validation email with ajax.
   */
  public function myAjaxEmailCallback(array &$form, FormStateInterface $form_state) {
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
    $image = $form_state->getValue('image');
    $file = File::load($image[0]);
    $file->setPermanent();
    $file->save();
    \Drupal::service('database')->insert('swat')
      ->fields([
        'name' => $form_state->getValue('title'),
        'uid' => $this->currentUser()->id(),
        'email' => $form_state->getValue('email'),
        'photo' => $form_state->getValue('image')[0],
        'timestamp' => time(),
      ])
      ->execute();
    $this->messenger()
      ->addMessage($this->t('Your cat name "@name" save', ['@name' => $form_state->getValue('title')]));
    $form_state->setRebuild(FALSE);
  }

}
