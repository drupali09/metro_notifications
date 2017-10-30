<?php

namespace Drupal\metro_notifications\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\metro_notifications\MetroNotificationsHelper;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Default controller for the metro_notifications module.
 */
class MetroNotificationsController extends ControllerBase {

  /**
   * Implements: getUpdatedNotifications().
   *
   * Call to this function is made from js to asynchronousely update content.
   */
  public function getUpdatedNotifications() {
    $block_content = MetroNotificationsHelper::getNotificationsData();

    $response = new Response();
    $response->setContent(json_encode($block_content));
    $response->headers->set('Content-Type', 'application/json');
    return $response;
  }

  /**
   * Returns a simple page with listing of all notifications.
   *
   * @return array
   *   A simple renderable array.
   */
  public function notificationListing($term) {
    $user = \Drupal::currentUser();
    $content = MetroNotificationsHelper::getNotifications($user->id(), 'page');
    $term = MetroNotificationsHelper::getAllDocumentTypes()[$term];

    $data = '<div>' . t('Following is list of updated documents of type ') . '<b>' . $term . '</b></div><ul>';
    foreach ($content as $nid => $type) {
      if ($type == $term) {
        $node = \Drupal\node\Entity\Node::load($nid);
        $options = ['absolute' => TRUE, 'attributes' => ['class' => 'this-class']];
        $link_object = Link::createFromRoute($node->getTitle(), 'entity.node.canonical', ['node' => $nid], $options)->toString();
        $data .= '<li>' . $link_object . t(' has been updated') . '</li>';
      }
    }
    $data .= '</ul>';
    $element = [
      '#markup'  => $data,
    ];
    return $element;
  }

}
