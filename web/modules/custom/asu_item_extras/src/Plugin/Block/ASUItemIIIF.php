<?php

namespace Drupal\asu_item_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a 'International Image Interoperability Framework' Block.
 *
 * @Block(
 *   id = "asu_item_iiif",
 *   admin_label = @Translation("International Image Interoperability Framework"),
 *   category = @Translation("Views"),
 * )
 */
class ASUItemIIIF extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /*
     * The links within this block should be:
     *  - Citing this image
     *  - Responsibilities of use
     *  - Licensing and Permissions
     *  - Linking and Embedding
     *  - Copies and Reproductions
     */
    // Since this block should be set to display on node/[nid] pages that are
    // "ASU Repository Item", or possibly "Collection", the underlying
    // node can be accessed via the path.
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node) {
      $nid = $node->id();
    } else {
      $nid = 0;
    }
    $node_url = Url::fromRoute('<current>', array());
    $iiif_section = $this->get_IIIF_section($node_url);
    return [
      'iiif-section' => [
        '#type' => 'container',
        $iiif_section
      ]];
  }

  private function get_IIIF_section($url) {
    static $id_suffix;
    $id_suffix = !($id_suffix) ? '' : $id_suffix + 1;
    return [
      'iiif-container' => [
        '#type' => 'item',
        '#id' => 'iiif_box',
        'container' => [
          '#type' => 'container',
          'left-block' => [
            '#type' => 'item',
            '#prefix' => '<div class="float_l_18">',
            '#suffix' => '</div>',
            '#markup' => '            <a href="https://iiif.io/technical-details/" target="_blank">
                <img class="img " src="' .
                \Drupal::request()->getSchemeAndHttpHost() . "/" .
                drupal_get_path("module", "asu_item_extras") . '/images/IIIF-logo-colored-text.svg">
              </a>',
          ],
          // Drupal requires javascript to be attached to the render elements.
          'right-block' => [
            '#type' => 'item',
            '#attached' => [
              'library' => [
                'asu_item_extras/interact',
              ],
            ],
            'input-box' => [
              '#type' => 'textfield',
              '#id' => 'iiif_editbox' . $id_suffix,
              '#value' => \Drupal::request()->getSchemeAndHttpHost() . $url->toString() . '/manifest',
            ],
            '#prefix' => '<div class="float_l_80"><p><span>We support the </span><a href="https://iiif.io/technical-details/" target="_blank">IIIF</a><span> Presentation API</span></p>',
            '#suffix' => '<!-- Unnamed (Rectangle) -->
            <div>
              <a id="copy_manifest_link" class="copy_button">Copy link</a>
            </div>
          </div>',
          ],
        ]
      ]
    ];
  }

}