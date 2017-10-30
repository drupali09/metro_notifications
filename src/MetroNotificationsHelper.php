<?php

namespace Drupal\metro_notifications;

use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Link;
use Drupal\Core\Url;
/**
 * Class MetroNotificationsHelper.
 */
class MetroNotificationsHelper {

  public static function getNotifications($uid, $display_type = 'block'){
    $block_content['output'] = "";
    $block_content['count']  = 0;

    //Get last login time for current user
    $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    //$user = \Drupal::entityTypeManager()->getStorage('user')->load(\Drupal::currentUser()->id());
    $lastLoginTime = $user->getLastLoginTime();
    $lastAccessedTime = $user->getLastAccessedTime();

    //Get all nodes created/updated after last login
    $q = \Drupal::database()->select('history', 'h');
    $q->leftJoin('node_field_data', 'n', 'n.nid = h.nid');
    $q->fields('h', array('nid'));
    $q->condition('h.uid', $uid, '!=');
    $q->condition('n.type', 'document', '=');
    $res_nids = $q->execute()->fetchCol();

    $results_updated = [];
    //check if current user has access to these nodes
    foreach($res_nids as $nid) {
      if (self::userHasAccessToNode($uid, $nid)) {
        $node = \Drupal\node\Entity\Node::load($nid);
        $document_type = self::getTaxonomyTerm($node->field_taxo_types->getValue())[0];
        $results_updated[$nid] = $document_type;
      }
    }

    //Get all update nodes NOT seen by current user
    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->leftJoin('history', 'h', 'n.nid = h.nid');
    $query->fields('n', array('nid'));
    $query->fields('h', array('timestamp'));
    $query->condition('h.uid', $uid, '=');
    //$query->condition('n.changed', , '>');
    $query->condition('n.type', 'document', '=');
    $query->condition('h.timestamp', $lastAccessedTime, '<');
    //$query->condition('h.nid', $accessible_nids, 'IN');
    $res = $query->execute()->fetchAll();

    foreach ($res as $res) {
      $node = \Drupal\node\Entity\Node::load($res->nid);
      if (self::userHasAccessToNode($uid, $res->nid) && $res->timestamp < $node->getChangedTime()) {
        $document_type = self::getTaxonomyTerm($node->field_taxo_types->getValue())[0];
        $results_updated[$res->nid] = $document_type;
      }
    }

    $results_page = $results_updated;
    $results_block = array_count_values($results_updated);

    foreach (array_values(self::getAllDocumentTypes()) as $type) {
      if(array_key_exists($type, $results_block)) {
        $result[$type] = $results_block[$type];
      } else {
        $result[$type] = 0;
      }
    }
    //kint($result);
    $output = '';
    foreach($result as $key => $value) {
      $term_id = array_search ($key, self::getAllDocumentTypes());
      $url = Url::fromRoute('metro_notifications.content', ['term' => $term_id]);
      $link = Link::fromTextAndUrl(t($key), $url )->toString();
      $output .= '<div><b>' . $link . '</b><br/><span>' . t('%d unread document(s)', array('%d' => $value)) . '</span></div>';
    }

    $block_content['output'] = $output;
    if ($display_type == 'page') {
      return $results_page;
    } else {
      return $block_content;
    }

  }

  public static function userHasAccessToNode($uid, $nid) {
    return TRUE;
  }

  public static function getEmailNotifications($uid, $start, $end) {
    //Get all nodes created/updated after last login
    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->fields('n', array('nid'));
    $query->condition('n.changed', $start, '>=');
    $query->condition('n.changed', $end, '<=');
    $res_nids = $query->execute()->fetchCol();

    //check if current user has access to these nodes
    foreach($res_nids as $nid) {
      if (self::userHasAccessToNode($uid, $nid)) {
        $accessible_nids[] = $nid;
      }
    }

    $output = t('Following is the list of new/updated content last week - ') . '<br/>';
    foreach ($accessible_nids as $nid) {
      $node = \Drupal\node\Entity\Node::load($nid);
      $output .= $node->getTitle() . '<br/>';
    }
    return $output;
  }

  public static function getAllMarchantsOfBanner($nid) {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'merchant')
          ->condition('field_ref_banner', $nid, '=');
    /*$query = \Drupal::database()->select('node_field_data', 'n');
    $query->leftJoin('node__field_ref_banner', 'nb', 'n.nid = nb.entity_id');
    $query->fields('n', array('nid'));
    $query->condition('n.type', 'merchant', '=');
    $query->condition('nb.field_ref_banner_target_id', $nid, '=');*/

    $res = $query->execute();
    return array_values($res);

  }

  public static function getAllUsersOfMarchant($nid) {
    $query = \Drupal::entityQuery('user');
    $query->condition('field_marchant', $nid, '=');

    $res = $query->execute();
    return array_values($res);
  }

  public static function getCurrentLanguage() {
    // Get language context
    $language = \Drupal::languageManager()->getCurrentLanguage();
    $langcode = $language->getId();

    return $langcode;
  }

  /**
   *  Provides the required material to create link, internal or external
   *
   * @param object $fieldValues
   *  The data on a multiple values sfield manage with a linkit widget
   *
   * @return array()
   *   Array that contains values from a multiple values textfield.
   */
  public static function getTaxonomyTerm($fieldValues) {

    // Get language context
    $language = \Drupal::languageManager()->getCurrentLanguage();
    $langcode = $language->getId();

    $datas = array();

    $counter = 0;
    foreach ($fieldValues as $key => $link) {
      if (isset($link['target_id']) && $link['target_id'] != null) {
        $term = Term::load($link['target_id']);
        if($term != null){
          $localized = \Drupal::service('entity.repository')->getTranslationFromContext($term, $langcode);
          $term_name = $localized->name->value;
          $datas[] = $term_name;
        }
      }
      $counter++;
    }

    return $datas;
  }

  public static function getAllDocumentTypes(){
    $vid = 'document_types';
    $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
    foreach ($terms as $term) {
      $term_data[$term->tid] = $term->name;
    }
    return $term_data;
  }

}
