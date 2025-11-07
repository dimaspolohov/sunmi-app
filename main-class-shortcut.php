<?php
/**
 * Main class shortcut
 *
 * @package Onepix\PluginTemplate
 */

use Onepix\PluginTemplate\SunMi;

/**
 * Shortcut for getting Main class instance
 *
 * @return \Onepix\PluginTemplate\PluginBase\Templates\Singleton
 */
function plugin_template(): \Onepix\PluginTemplate\PluginBase\Templates\Singleton
{
	return SunMi::get_instance();
}
