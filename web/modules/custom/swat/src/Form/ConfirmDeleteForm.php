<?php

/**
 * Contains \Drupal\swat\Form\CatsForm.
 *
 * @file
 */

namespace Drupal\swat\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Add my class to delete info from db.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class ConfirmDeleteForm extends ConfirmFormBase {

  /**
   * ID of the item to delete.
   *
   * @var int
   */
  protected $id;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $id = NULL) {
    $this->id = $id;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return "confirm_delete_form";
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('cats_page.content');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you want to delete this post?');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = \Drupal::database()->delete('swat');
    $query->condition('id', $this->id);
    $query->execute();
  }

}
