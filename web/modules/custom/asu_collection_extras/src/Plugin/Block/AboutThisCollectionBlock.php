<?php

namespace Drupal\asu_collection_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\media\Entity\Media;

// ### The next two classes will be needed if using Drupal routes to make links.
//use Drupal\Core\Url;
//use Drupal\Core\Link;

/**
 * Provides a 'About this collection' Block.
 *
 * @Block(
 *   id = "about_this_collection_block",
 *   admin_label = @Translation("About this collection"),
 *   category = @Translation("Views"),
 * )
 */
class AboutThisCollectionBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /*
     * The sections in this block should be statistics on the children of the
     * collection using the "Is Member Of" relationship -- not just the "Is
     * Primary Member Of" and these should link to a canned-search for the
     * specific statistic when possible:
     *
     *  - # of child items
     *  - # of resource types
     *  - # of titles

     *  - usage
     *  - collection created
     *  - collection last updated (by looking at all of the children)
     *
     * When needing to check a node's content type:
     *    $is_collection = ($node->bundle() == 'collection');
     */
    // Since this block should be set to display on node/[nid] pages that are
    // either "Repository Item", "ASU Repository Item", or "Collection",
    // the underlying node can be accessed via the path.
    $collection_node = \Drupal::routeMatch()->getParameter('node');
    if ($collection_node) {
      $collection_created = $collection_node->get('revision_timestamp')->getString();
    } else {
      $collection_created = 0;
    }
    // This needs to only return items that are Published and related to the
    // collection, but there doesn't seem to be a way to have multiple AND / OR
    // conjunctions in a single query.
    $children_nids = getAllCollectionChildren($collection_node);

    $collection_views = $items = $max_timestamp = 0;
    $nodes = $islandora_models = $stat_boxes = [];
    $files = 0;
    $original_file_tid = key(\Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['name' => "Original File"]));
    foreach ($children_nids as $child_nid) {
      if ($child_nid) {
        // @todo load complex objects for this node -- if any... and inner-loop these

        $items++;
        // For "# file (Titles)", get media - extract the and count the original files.
        $files += $this->getOriginalFileCount($child_nid, $original_file_tid);
        $node = \Drupal::entityTypeManager()->getStorage('node')->load($child_nid);
        if (!$node->get('field_resource_type')->isEmpty()){
          $res_types = $node->get('field_resource_type')->referencedEntities();
          foreach ($res_types as $tp) {
            $nm = $tp->getName();
            if (array_key_exists($nm, $islandora_models)){
              $islandora_models[$nm]++;
            }
            else {
              $islandora_models[$nm] = 1;
            }
          }
        }
        $this_revisiontimestamp = $node->get('revision_timestamp')->getString();
        $max_timestamp = ($this_revisiontimestamp > $max_timestamp) ?
          $this_revisiontimestamp : $max_timestamp;
        $node_views = \Drupal::service('islandora_matomo.default')->getViewsForNode($child_nid);
        $collection_views += $node_views;
      }
    }
    $stat_boxes[] = $this->makeBox("<strong>" . $files . "</strong><br>files");
    $stat_boxes[] = $this->makeBox("<strong>" . count($islandora_models) . "</strong><br>resource types");
    $stat_boxes[] = $this->makeBox("<strong>" . $items . "</strong><br>items");
    $stat_boxes[] = $this->makeBox("<strong>" . $collection_views . "</strong><br>usage");
    $stat_boxes[] = $this->makeBox("<strong>" . (($collection_created) ? date('Y', $collection_created): 'unknown') .
      "</strong><br>collection created");
    $stat_boxes[] = $this->makeBox("<strong>" . (($max_timestamp) ? date('M d, Y', $max_timestamp): 'unknown') .
      "</strong><br>last updates");
    return [
      '#cache' => ['max-age' => 0],
      '#markup' =>
        (count($stat_boxes) > 0) ?
        implode('', $stat_boxes) .
        '<br class="clearfloat">' :
        "",
      'lib' => [
        '#attached' => [
          'library' => [
            'asu_collection_extras/interact',
          ],
        ],
      ]
    ];
  }

  private function makeBox($string) {
    return '<div class="stats_box">' . $string . '</div>';
  }

  private function getOriginalFileCount($related_nid, $original_file_tid) {
    $files = 0;
    $mids = \Drupal::entityQuery('media')
      ->condition('field_media_of', $related_nid)
      ->condition('field_media_use', $original_file_tid)
      ->execute();
    foreach ($mids as $mid) {
      $media = Media::load($mid);
      $files += (is_object($media) ? 1 : 0);
    }
    return $files;
  }

  public function nothing($x) {

  }

}