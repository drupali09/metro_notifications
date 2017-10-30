<?php

namespace Drupal\metro_notifications\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\metro_notifications\MetroNotificationsHelper;

/**
 * Provides a metro_notification_block.
 *
 * @Block(
 *   id = "metro_notification_block",
 *   admin_label = @Translation("Metro Notifications"),
 *   category = @Translation("Custom notification block")
 * )
 */
class MetroNotificationsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   *
   * Overrides default function for no caching for block contents.
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $user = \Drupal::currentUser();
    $block_content = MetroNotificationsHelper::getNotifications($user->id());

    if (empty($block_content['count'])) {
      $count = 0;
    }
    else {
      $count = $block_content['count'];
    }

    return [
      '#title'              => $this->t('Metro notifications'),
      '#type'               => 'markup',
      '#markup' => $block_content['output'],
      //'#theme'              => 'notifications',
      /*'#notifications'      => $block_content['output'],
      '#notification_count' => $block_content['count'],
      '#link'               => $block_content['link'],
      '#attached' => [
        'library' => ['metro_notifications/metro_notifications'],
        'drupalSettings' => [
          'metro_notifications' => [
            'refresh_interval'  => $block_content['refresh_interval'],
            'notify_status'     => $block_content['notify_status'],
            'user_access'       => $block_content['user_access'],
          ],
        ],
      ],
      'cache' => [
        'max_age' => 0,
      ],*/
    ];
  }

}
