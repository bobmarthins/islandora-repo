<?php

namespace Drupal\asu_item_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'Explore this item' Block.
 *
 * @Block(
 *   id = "explore_this_item_block",
 *   admin_label = @Translation("Explore this item"),
 *   category = @Translation("Views"),
 * )
 */
class ExploreThisItemBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The routeMatch definition.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The requestStack definition.
   *
   * @var requestStack
   */
  protected $requestStack;

  /**
   * The entityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructor for About this Collection Block.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entityTypeManager definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The Drupal form builder.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, RequestStack $request_stack, EntityTypeManager $entityTypeManager, FormBuilderInterface $formBuilder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->requestStack = $request_stack;
    $this->entityTypeManager = $entityTypeManager;
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('request_stack'),
      $container->get('entity_type.manager'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Depending on what the islandora_object model is, the links will differ.
    $node = $this->routeMatch->getParameter('node');
    if ($node) {
      $nid = $node->id();
    }
    else {
      $nid = 0;
    }

    $field_model_tid = $node->get('field_model')->getString();
    $field_model_term = $this->entityTypeManager->getStorage('taxonomy_term')->load($field_model_tid);
    $field_model = (isset($field_model_term) && is_object($field_model_term)) ?
      $field_model_term->getName() : '';

    $output_links = [];
    if ($field_model == 'Image') {
      $url = Url::fromUri($this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . '/items/' . $nid . '/view');
      $link = Link::fromTextAndUrl($this->t('View Image'), $url);
      // Get the node's service file information from the node - just use the
      // openseadragon view.
      $link = $link->toRenderable();
      $output_links[] = render($link);
    }
    elseif ($field_model == 'Complex Object') {
      $search_form = $this->formBuilder->getForm('Drupal\asu_item_extras\Form\ExploreForm');
      $renderArray['form'] = $search_form;
      return $renderArray;
    }
    elseif ($field_model == 'Paged Content' || $field_model == 'Page' ||
      $field_model == 'Digital Document') {
      // "Start reading" and "Show all pages" links as well as a search box.
      // get the node's openseadragon viewer url.
      $url = Url::fromUri($this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . '/items/' . $nid . '/view');
      $link = Link::fromTextAndUrl($this->t('Explore Document'), $url);
      $link = $link->toRenderable();
      $output_links[] = render($link);
    }
    $return = [
      '#markup' =>
      ((count($output_links) > 0) ?
        "<nav><ul class=''><li>" . implode("</li><li>", $output_links) . "</li></ul></nav>" :
        ""),
      'searchform' => $search_form,
    ];
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // With this when your node change your block will rebuild.
    if ($node = $this->routeMatch->getParameter('node')) {
      // If there is node add its cachetag.
      return Cache::mergeTags(parent::getCacheTags(), ['node:' . $node->id()]);
    }
    else {
      // Return default tags instead.
      return parent::getCacheTags();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // If you depends on \Drupal::routeMatch().
    // You must set context of this block with 'route' context tag.
    // Every new route this block will rebuild.
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

}
