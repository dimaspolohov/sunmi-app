<?php
/**
 * Main WordPress core class
 *
 * @package Onepix\PluginTemplate
 */

namespace Onepix\PluginTemplate\REST;

defined( 'ABSPATH' ) || exit();

/**
 * Main WordPress core class
 *
 * @package Onepix\PluginTemplate
 */
class Main {
    /**
     * Main constructor.
     */
    public function __construct() {
        add_action(
            'plugins_loaded',
            function () {
                new Auth\Main();
                new Order\Main();
                new Shop\Main();
                new Product\Main();
            }
        );
    }
}
