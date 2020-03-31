<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @since             1.0.0
 * @package           movabletype-importer-fixers
 *
 * @wordpress-plugin
 * Plugin Name:       Movable Type Importer fixes
 * Description:       This plugin is being used for Movable Type import fixes.
 * Version:           1.0.0
 * Author:            Automattic
 * Author URI:        https://automattic.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       movabletype-importer-fixers
 * Domain Path:       /languages
 */

/**
 * Currently plugin version.
 */
define( 'MT_MIGRATION_TOOLS_VERSION', '1.0.0' );

define( 'MT_MIGRATION_TOOLS_DIR', plugin_dir_path( __FILE__ ) );

// add WP-CLI command support
if ( defined( 'WP_CLI' ) && WP_CLI ) {

	require_once MT_MIGRATION_TOOLS_DIR . '/cli/init.php';
}
