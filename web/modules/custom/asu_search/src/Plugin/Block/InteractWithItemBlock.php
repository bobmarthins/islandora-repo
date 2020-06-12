<?php

namespace Drupal\asu_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides an 'Interact with item' Block.
 *
 * @Block(
 *   id = "interact_with_item_block",
 *   admin_label = @Translation("Interact with this item"),
 *   category = @Translation("Views"),
 * )
 */
class InteractWithItemBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /*
     * The title of the block could be dependant on the underlying Islandora
     * Model used. In liey of that, the title should just be "About this item".
     *
     * The links within this block should be:
     *  - view full metadata
     *  Print and share sub-block
     *  - Permalink
     *  Citations, Rights and Reuse sub-block
     *  International Image Interoperability Framework lookup
     *
     */
    // Since this block should be set to display on node/[nid] pages that are
    // either "Repository Item", "ASU Repository Item", or "Collection",
    // the underlying node can be accessed via the path.
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node) {
      $nid = $node->id();
    } else {
      $nid = 0;
    }
    $output_links = array();
    // Add a link for the "Overview" of this node.
    $variables['nodeid'] = $nid;
    $node_url = Url::fromRoute('<current>', array());
    $link = Link::fromTextAndUrl(t('Overview'), $node_url);
    $link = $link->toRenderable();
    $output_links[] = render($link);
    // Add a link to the "View full metadata" anchor for this node.
    $url = Url::fromUri(\Drupal::request()->getSchemeAndHttpHost() . '/node/' . $nid . '/full-metadata');
    $link = Link::fromTextAndUrl(t('View full metadata'), $url);
    $link = $link->toRenderable();
    $output_links[] = render($link);
    // Add a link to get the Permalink for this node. Could this be a javascript
    // event that will send the current node's URL to the copy buffer?
    $url = Url::fromUri(\Drupal::request()->getSchemeAndHttpHost() . '/node/' . $nid);
    $output_links[] = 'Permalink <span class="copy_permalink_link"><img src="' .
          \Drupal::request()->getSchemeAndHttpHost() . "/" .
            drupal_get_path("module", "asu_search") .
            '/images/permalink_glyph.png" class="link_cursor" width="18" height="18" /></span>';
    $iiif_section = $this->get_IIIF_section($node_url);
    $citations_section = $this->get_citations_section($node_url, $node);
    $print_and_share_section = $this->get_print_and_share_section($node_url);
    return [
      'unordered-list' => [
        '#type' => 'item',
        '#attached' => [
          'library' => [
            'asu_search/interact',
          ],
        ],
        '#markup' =>
          ((count($output_links) > 0) ?
            "<ul class=''><li>" . implode("</li><li>", $output_links) . "</li></ul>" :
            ""),
        'permalink' => [
          '#type' => 'textfield',
          '#id' => 'permalink_interact_editbox',
          '#attributes' => [
            'class' => array('disabled_small_prompt'),
            'readonly' => TRUE,
          ],
          '#value' => $url->toString(),
        ],
      ],
      $citations_section,
      $print_and_share_section,
      'iiif-section' => [
        '#type' => 'container',
        $iiif_section
      ]
    ];
  }

  private function get_IIIF_section($url) {
    return [
      'iiif-container' => [
        '#type' => 'item',
        '#title' => t('International Image Interoperability Framework'),
        '#prefix' => '<br class="clearfloat" />',
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
                drupal_get_path("module", "asu_search") . '/images/iiif_logo.png">
              </a>',
          ],
          // Why does drupal make everything so difficult anymore... QUIT STRIPPING
          // out my inline javascript onclick events!
          'right-block' => [
            '#type' => 'item',
            'input-box' => [
              '#type' => 'textfield',
              '#id' => 'iiif_editbox',
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

  private function get_citations_section($node_url, $node) {
    // To get the node's custom field value...
    //    $citation = (is_object($node)) ?
    //      $node->get("field_preferred_citation")->getValue() : "";
    //    $citation_string = (is_array($citation)) ?
    //      $citation[0]['value'] : "";
    $url_string = \Drupal::request()->getSchemeAndHttpHost() . $node_url->toString();
    $output_links = array();
    $url = Url::fromUri($url_string . '/citation/#citing');
    $link = Link::fromTextAndUrl(t('Citing this image'), $url)->toRenderable();
    $output_links[] = render($link);
    $url = Url::fromUri($url_string . '/citation/#responsibilities');
    $link = Link::fromTextAndUrl(t('Responsibilities of use'), $url)->toRenderable();
    $output_links[] = render($link);
    $url = Url::fromUri($url_string . '/citation/#licensing');
    $link = Link::fromTextAndUrl(t('Licensing and Permissions'), $url)->toRenderable();
    $output_links[] = render($link);
    $url = Url::fromUri($url_string . '/citation/#linking');
    $link = Link::fromTextAndUrl(t('Linking and Embedding'), $url)->toRenderable();
    $output_links[] = render($link);
    $url = Url::fromUri($url_string . '/citation/#copies');
    $link = Link::fromTextAndUrl(t('Copies and Reproductions'), $url)->toRenderable();
    $output_links[] = render($link);
    $render_this = [
      '#markup' =>
        ((count($output_links) > 0) ?
          "<ul class=''><li>" . implode("</li><li>", $output_links) . "</li></ul>" :
          ""),
//      '#theme' => 'item_list',
//      '#items' => $links,
    ];
//    $rendered_list = render($render_this);
    return [
      'citations-container' => [
        '#type' => 'item',
        '#title' => t('Citations, Rights and Reuse'),
        '#id' => 'citations_box',
        '#prefix' => /* $citation_string . */ '<div class="float_l_49">',
        '#suffix' => '</div>',
        'container' => [
          '#type' => 'container',
          'the-items' => [
            '#type' => 'item',
            $render_this
          ]]]];
  }

  private function get_print_and_share_section($url) {
    return [
      'print-container' => [
        '#type' => 'item',
        '#title' => t('Print and share'),
        '#id' => 'print_and_share_box',
        '#prefix' => '<div class="float_l_49">',
        '#suffix' => '</div>',
        'container' => [
          '#type' => 'container',
          'sharelinks' => [
            '#type' => 'item',
            '#markup' => asu_search_get_print_and_share_block($url),
            '#suffix' => '<br class="clearfloat" />',
          ]]]];
  }
}