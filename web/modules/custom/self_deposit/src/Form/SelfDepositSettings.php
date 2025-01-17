<?php

namespace Drupal\self_deposit\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides settings for the self deposit functionality.
 */
class SelfDepositSettings extends ConfigFormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'self_deposit.selfdepositsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('self_deposit.selfdepositsettings');
    $form['collection_for_deposits'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Collection for deposits'),
      '#description' => $this->t('The collection for self deposits to get uploaded into.'),
      '#target_type' => 'node',
      '#selection_settings' => ['target_bundles' => ['collection']],
      '#default_value' => $config ? $this->entityTypeManager->getStorage('node')->load($config->get('collection_for_deposits')) : '',
    ];

    $form['barrett_collection_for_deposits'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Barrett Collection for deposits'),
      '#description' => $this->t('The Barrett collection for self deposits to get uploaded into.'),
      '#target_type' => 'node',
      '#selection_settings' => ['target_bundles' => ['collection']],
      '#default_value' => $config ? $this->entityTypeManager->getStorage('node')->load($config->get('barrett_collection_for_deposits')) : '',
    ];

    $form['audio_media_model'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Model for Audio Media'),
      '#description' => $this->t('The default model to apply to nodes with attached Audio media'),
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => ['target_bundles' => ['islandora_models']],
      '#default_value' => $config ? $this->entityTypeManager->getStorage('taxonomy_term')->load($config->get('audio_media_model')) : '',
    ];

    $form['image_media_model'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Model for Image Media'),
      '#description' => $this->t('The default model to apply to nodes with attached Image media'),
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => ['target_bundles' => ['islandora_models']],
      '#default_value' => $config ? $this->entityTypeManager->getStorage('taxonomy_term')->load($config->get('image_media_model')) : '',
    ];

    $form['video_media_model'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Model for Video Media'),
      '#description' => $this->t('The default model to apply to nodes with attached Video media'),
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => ['target_bundles' => ['islandora_models']],
      '#default_value' => $config ? $this->entityTypeManager->getStorage('taxonomy_term')->load($config->get('video_media_model')) : '',
    ];

    $form['file_media_model'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Model for File Media'),
      '#description' => $this->t('The default model to apply to nodes with attached File media'),
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => ['target_bundles' => ['islandora_models']],
      '#default_value' => $config ? $this->entityTypeManager->getStorage('taxonomy_term')->load($config->get('file_media_model')) : '',
    ];

    $form['document_media_model'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Model for Document Media'),
      '#description' => $this->t('The default model to apply to nodes with attached Document media'),
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => ['target_bundles' => ['islandora_models']],
      '#default_value' => $config ? $this->entityTypeManager->getStorage('taxonomy_term')->load($config->get('document_media_model')) : '',
    ];

    $form['perf_archive_default_genre'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Default Genre for Performance Archive Form'),
      '#description' => $this->t('The default genre to apply to node forms in the Performance Archive form'),
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => ['target_bundles' => ['genre']],
      '#default_value' => $config ? $this->entityTypeManager->getStorage('taxonomy_term')->load($config->get('perf_archive_default_genre')) : '',
    ];

    $form['perf_archive_default_copyright'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Default Copyright Statement for Performance Archive Form'),
      '#description' => $this->t('The default copyright statement to apply to node forms in the Performance Archive form'),
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => ['target_bundles' => ['copyright_statements']],
      '#default_value' => $config ? $this->entityTypeManager->getStorage('taxonomy_term')->load($config->get('perf_archive_default_copyright')) : '',
    ];

    $form['perf_archive_default_reuse'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Default Reuse Permissions for Performance Archive Form'),
      '#description' => $this->t('The default reuse permissions to apply to node forms in the Performance Archive form'),
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => ['target_bundles' => ['reuse_permissions']],
      '#default_value' => $config ? $this->entityTypeManager->getStorage('taxonomy_term')->load($config->get('perf_archive_default_reuse')) : '',
    ];

    $form['perf_archive_default_model'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Default Model for Performance Archive Form'),
      '#description' => $this->t('The default model to apply to node forms in the Performance Archive form'),
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => ['target_bundles' => ['islandora_models']],
      '#default_value' => $config ? $this->entityTypeManager->getStorage('taxonomy_term')->load($config->get('perf_archive_default_model')) : '',
    ];

    $form['perf_archive_default_identifier_type'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Default Identifier Type for Performance Archive Form'),
      '#description' => $this->t('The default identifier type to apply to node forms in the Performance Archive form'),
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => ['target_bundles' => ['identifier_types']],
      '#default_value' => $config ? $this->entityTypeManager->getStorage('taxonomy_term')->load($config->get('perf_archive_default_identifier_type')) : '',
    ];

    $form['perf_archive_default_collection'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Default Collection for Performance Archive Form'),
      '#description' => $this->t('The default collection to apply to node forms in the Performance Archive form'),
      '#target_type' => 'node',
      '#selection_settings' => ['target_bundles' => ['collection']],
      '#default_value' => $config ? $this->entityTypeManager->getStorage('node')->load($config->get('perf_archive_default_collection')) : '',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('self_deposit.selfdepositsettings')
      ->set('collection_for_deposits', $form_state->getValue('collection_for_deposits'))
      ->set('barrett_collection_for_deposits', $form_state->getValue('barrett_collection_for_deposits'))
      ->set('audio_media_model', $form_state->getValue('audio_media_model'))
      ->set('image_media_model', $form_state->getValue('image_media_model'))
      ->set('video_media_model', $form_state->getValue('video_media_model'))
      ->set('file_media_model', $form_state->getValue('file_media_model'))
      ->set('document_media_model', $form_state->getValue('document_media_model'))
      ->set('perf_archive_default_genre', $form_state->getValue('perf_archive_default_genre'))
      ->set('perf_archive_default_copyright', $form_state->getValue('perf_archive_default_copyright'))
      ->set('perf_archive_default_reuse', $form_state->getValue('perf_archive_default_reuse'))
      ->set('perf_archive_default_model', $form_state->getValue('perf_archive_default_model'))
      ->set('perf_archive_default_identifier_type', $form_state->getValue('perf_archive_default_identifier_type'))
      ->set('perf_archive_default_collection', $form_state->getValue('perf_archive_default_collection'))
      ->save();
  }

}
