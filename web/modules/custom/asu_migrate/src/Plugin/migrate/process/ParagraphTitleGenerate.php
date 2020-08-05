<?php

namespace Drupal\asu_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\Row;
use Drupal\taxonomy\Entity\Term;

/**
 * Create new paragraph.
 *
 * @MigrateProcessPlugin(
 *   id = "paragraph_title_generate"
 * )
 *
 * @code
 *    plugin: paragraph_title_generate
 *     paragraph_type: 'complex_title'
 *    split_into_parts: true
 *     fields:
 *      field_nonsort: ""
 *       field_rest_of_title: ""
 *      field_subtitle: ""
 */
class ParagraphTitleGenerate extends ParagraphGenerate {
  // @todo would be great to pull these from a config.
  protected $nonsorts = [
      'the', 'an', 'a'
  ];

  /**
   * {@inheritdoc}
   */
  public function transform($title_string, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $split = $this->configuration['split_into_parts'];
    $fields = $this->configuration['fields'];
    $fields['field_rest_of_title'] = $title_string;
    if ($split) {
      if (str_contains($fields['field_rest_of_title'], ':')) {
        $tparts = explode(':', $fields['field_rest_of_title']);
        $tparts = array_map('trim', $tparts);
        $fields['field_subtitle'] = array_pop($tparts);
        $fields['field_rest_of_title'] = implode(":", $tparts);
      }
      foreach ($this->nonsorts as $ns) {
        $ns = $ns . " ";
        if (substr(strtolower($fields['field_rest_of_title']), 0, strlen($ns)) === $ns) {
          $tparts = explode(" ", $fields['field_rest_of_title'], 2);
          $fields['field_nonsort'] = $tparts[0];
          $fields['field_rest_of_title'] = end($tparts);
          break;
        }
      }
      foreach ($fields as $k => $field) {
        if ($field != "" || $field != " " || $field != NULL) {
          $fields[$k] = ["value" => $field];
        }
      }
    }
    $paragraph = parent::createParagraph($this->configuration['paragraph_type'], $fields);
    return $paragraph;
  }
}