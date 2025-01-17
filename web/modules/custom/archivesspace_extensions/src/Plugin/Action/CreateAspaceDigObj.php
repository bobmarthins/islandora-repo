<?php

namespace Drupal\archivesspace_extensions\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\archivesspace\ArchivesSpaceSession;

/**
 * Create a handle for an object.
 *
 * @Action(
 *   id = "create_aspace_dig_obj",
 *   label = @Translation("Create/Update an Archivesspace Digital Object"),
 *   type = "node"
 * )
 */
class CreateAspaceDigObj extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * Logger.
   *
   * @var Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * ArchivesSpaceSession that will allow us to issue API requests.
   *
   * @var \Drupal\archivesspace\ArchivesSpaceSession
   */
  protected $archivesspaceSession;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param Psr\Log\LoggerInterface $logger
   *   Logger.
   */
  public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        LoggerInterface $logger
    ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
    $this->archivesspaceSession = new ArchivesSpaceSession();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
          $configuration,
          $plugin_id,
          $plugin_definition,
          $container->get('logger.channel.islandora')
      );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if (!$entity) {
      return;
    }
    $entity_type = $entity->getEntityTypeId();

    if (!$entity_type == 'node') {
      return;
    }

    $this->logger->info("In the Archivesspace DO Action!");

    $archival_obj = $entity->get('field_source')->referencedEntities();
    if ($archival_obj) {
      // @todo get repository id from configuration?
      $entity_uri = $entity->toUrl('canonical', ['https' => TRUE, 'absolute' => TRUE])->toString();
      $archival_obj = $archival_obj[0];
      $archival_obj_ref_id = $archival_obj->get('field_as_ref_id')->value;
      $params = [
        "ref_id" => [$archival_obj_ref_id],
      ];
      $ao_results = $this->archivesspaceSession->request('GET', '/repositories/2/find_by_id/archival_objects', $params);
      $ao_uri = $ao_results['archival_objects'][0]['ref'];
      $ao_info = $this->archivesspaceSession->request('GET', $ao_uri);
      // \Drupal::logger('aspace_digital_obj_action')->info(print_r($ao_info, TRUE));
      if ($entity->get('field_digital_object_id')->value != NULL) {
        $do_id = $entity->get('field_digital_object_id')->value;
        $do_results = $this->getDigitalObject($do_id, FALSE);
        $do_results['file_versions'][0]['file_uri'] = $entity_uri;
        $do_post_request = $this->createUpdateDigitalObject($do_results, $do_id);
        $do_uri = $do_post_request['uri'];
      }
      else {
        $ao_instances = $ao_info['instances'];
        \Drupal::logger('aspace_digital_obj_action')->info(print_r($ao_instances, TRUE));

        // Create a digital object with a file version with the repository URI.
        $identifiers = $entity->get('field_typed_identifier')->referencedEntities();
        $item_id = $identifiers[0]->get('field_identifier_value')->value;
        $do_id = str_replace(' ', '_', $item_id);
        \Drupal::logger('aspace_digital_obj_action')->info("create new digital object");
        $do_json = [
          'jsonmodel_type' => 'digital_object',
          'title' => $entity->getTitle(),
          'file_versions' => [
                  [
                    'file_uri' => $entity_uri,
                  ],
          ],
          'linked_instances' => [
                  [
                    'ref' => $ao_uri,
                  ],
          ],
          'digital_object_id' => $do_id,
        ];
        $create_response = $this->createUpdateDigitalObject($do_json);
        $do_uri = $create_response['uri'];

      }
      $this->updateArchivalObject($ao_uri, $ao_info, $do_id, $do_uri);
      // Store the digital object id on the entity.
      $entity->set('field_digital_object_id', [
        'value' => $do_uri,
      ]);
      $entity->save();
    }

  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowed();
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   *
   */
  private function createUpdateDigitalObject($do_json, $do_id = NULL) {
    $url = '/repositories/2/digital_objects';
    if ($do_id) {
      $url .= "/" . $do_id;
    }
    $response = $this->archivesspaceSession->request('POST', $url, $do_json);
    \Drupal::logger('aspace_digital_obj_action')->info(print_r($response, TRUE));
    if ($response['status'] == 'Created') {
      $this->messenger()->addStatus('Archivesspace digital object created');
    }
    if ($response['status'] == 'Updated') {
      $this->messenger()->addStatus('Archivesspace digital object updated');
    }
    return $response;
  }

  /**
   *
   */
  private function updateArchivalObject($ao_uri, $ao_json, $do_id, $do_uri) {
    $set_uri = FALSE;
    if (count($ao_json['instances']) > 0) {
      foreach ($ao_json['instances'] as $key => $instance) {
        if ($instance['instance_type'] == 'digital_object' && $instance['digital_object']['ref'] == $do_uri) {
          $ao_json['instances'][$key]['digital_object']['ref'] = $do_uri;
          $set_uri = TRUE;
        }
      }
    }
    if (!$set_uri) {
      $ao_json['instances'][] = [
        'lock_version' => 0,
        'instance_type' => 'digital_object',
        'jsonmodel_type' => 'instance',
        'created_by' => 'admin',
        'is_representative' => FALSE,
        'digital_object' => [
          'ref' => $do_uri,
        ],
      ];
    }

    $response = $this->archivesspaceSession->request('POST', $ao_uri, $ao_json);
    \Drupal::logger('aspace_action_update_ao_object')->info(print_r($response, TRUE));
    return $response;
  }

  /**
   *
   *
   */
  private function getDigitalObject($do_id, $full_uri = FALSE) {
    if (!$full_uri) {
      $full_uri = "/repositories/2/digital_objects/" . $do_id;
    }
    else {
      $full_uri = $do_id;
    }
    $response = $this->archivesspaceSession->request('GET', $full_uri);
    return $response;
  }

  /**
   *
   */
  private function getIdFromJson($do_json) {
    $uri = $do_json['uri'];
    $parts = explode('/', $uri);
    return $parts[count($parts) - 1];
  }

}
