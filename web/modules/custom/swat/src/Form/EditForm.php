<?php

/**
 * Contains \Drupal\swat\Form\CatsForm.
 *
 * @file
 */

namespace Drupal\swat\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\file\Entity\File;

/**
 * Add my class to delete info from db.
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
    return 'edit_cats_form';
  }

  /**
   * Take information from db to fill default value in edit form.
   */
  public function getInfo($type) {
    $conn = Database::getConnection();
    $query = $conn->select('swat', 's')
      ->condition('id', $this->id);
    $query->fields('s', ['id', 'name', 'email', 'timestamp', 'photo']);
    $data = $query->execute()->fetchAllAssoc('id');
    $data = json_decode(json_encode($data), TRUE);
    foreach ($data as $value) {
      $full_names = $value['name'];
      $emails = $value['email'];
      $files = $value['photo'];
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
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $id = NULL) {
    $this->id = $id;
    $form['email'] = [
      '#title' => $this->t('Email:'),
      '#default_value' => $this->getInfo(2),
      '#type' => 'email',
      '#required' => TRUE,
    ];
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cat’s name:'),
      '#default_value' => $this->getInfo(1),

      '#required' => TRUE,
    ];

    $form['image'] = [
      '#type' => 'managed_file',
      '#title' => t('Image:'),
      '#required' => TRUE,
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg'],
        'file_validate_size' => [2097152],
      ],
      '#theme' => 'image_widget',
      '#preview_image_style' => 'medium',
      '#default_value' => [$this->getInfo(3)],
      '#upload_location' => 'public://photochki',
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
   * Ajax cancel button.
   */
  public function myAjaxCallbackCancel(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
    $ajax_response->addCommand(new RedirectCommand('/swat/cats'));
    return $ajax_response;
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
    if (!isset($message['#message_list']['error'])) {
      $ajax_response->addCommand(new RedirectCommand('/swat/cats'));
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
    $image = $form_state->getValue('image');
    $file = File::load($image[0]);
    $file->setPermanent();
    $file->save();
    \Drupal::service('database')->update('swat')
      ->condition('id', $this->id)
      ->fields([
        'name' => $form_state->getValue('title'),
        'email' => $form_state->getValue('email'),
        'photo' => $form_state->getValue('image')[0],
      ])
      ->execute();
    $this->messenger()
      ->addMessage($this->t('Your cat name "@name" update', ['@name' => $form_state->getValue('title')]));
    $form_state->setRebuild(FALSE);
  }

}
