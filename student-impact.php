<?php
/**
 * Plugin Name:       Student Impact
 * Plugin URI:        https://wordpresseducation.org/
 * Description:       Showcases the graduates of the WordPress Credits program, ranked by their recent impact and contributions on WordPress.org. Data is synced from Airtable and profiles.wordpress.org; program figures come from WPCredits-Tracker when it runs on the same site. Provides "Student Stories" (showcase) and "Graduate Stats" (totals) blocks with matching shortcodes.
 * Version:           1.7.4
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Maciej (Matt) Pilarski
 * Author URI:        https://profiles.wordpress.org/gomp/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       student-impact
 *
 * @package Student_Impact
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SI_VERSION', '1.7.4' );
define( 'SI_FILE', __FILE__ );
define( 'SI_DIR', plugin_dir_path( __FILE__ ) );
define( 'SI_URL', plugin_dir_url( __FILE__ ) );

// Option / hook names.
define( 'SI_OPT_SETTINGS', 'si_settings' );  // Airtable config + display defaults.
define( 'SI_OPT_DATA', 'si_data' );          // Ranked showcase data (array of students).
define( 'SI_OPT_TOTALS', 'si_totals' );      // Aggregate stats across ALL graduates.
define( 'SI_OPT_STATE', 'si_state' );        // Resumable sync state.
define( 'SI_OPT_LASTSYNC', 'si_last_sync' ); // Timestamp of last successful sync.
define( 'SI_OPT_LASTERR', 'si_last_error' ); // Last sync error message.
define( 'SI_CRON_SYNC', 'si_cron_daily' );   // Recurring scheduled sync, weekly since 1.7.4 (the hook keeps its first name).
define( 'SI_CRON_RUN', 'si_run_sync' );      // Single-event sync step (resumable).

require_once SI_DIR . 'includes/shared.php';
require_once SI_DIR . 'includes/class-si-sync.php';
require_once SI_DIR . 'includes/class-si-render.php';
require_once SI_DIR . 'includes/class-si-settings.php';
require_once SI_DIR . 'includes/class-si-block.php';

/**
 * Default settings.
 *
 * @return array
 */
function si_default_settings() {
	return array(
		'airtable_pat'   => '',
		'base_id'        => 'appIzQKfwTn5dyPVp',
		'students_table' => 'tbla8GZg5x6NY7aWt',
		'reports_table'  => 'tbljYkkVGbeoaWEtY',
		'teams_table'    => 'tblUBEXiS3QKUCXHf',
		'status_value'   => 'Graduate',
		'count'          => 50,
	);
}

/**
 * Get merged settings.
 *
 * @return array
 */
function si_get_settings() {
	$saved = get_option( SI_OPT_SETTINGS, array() );
	return wp_parse_args( is_array( $saved ) ? $saved : array(), si_default_settings() );
}

/**
 * Boot the plugin.
 */
function si_init() {
	SI_Settings::instance();
	SI_Block::instance();

	// Cron handlers.
	add_action( SI_CRON_SYNC, array( 'SI_Sync', 'run_step' ) );

	// A site that scheduled the sync daily (before 1.7.4) moves to the weekly slot.
	if ( 'weekly' !== wp_get_schedule( SI_CRON_SYNC ) ) {
		si_schedule_sync();
	}
	add_action( SI_CRON_RUN, array( 'SI_Sync', 'run_step' ) );

	// Shortcodes.
	add_shortcode( 'student_impact', array( 'SI_Render', 'shortcode' ) );
	add_shortcode( 'student_impact_stats', array( 'SI_Render', 'stats_shortcode' ) );

	// Backward-compatible aliases for shortcodes shipped under earlier plugin names,
	// so pages built with a previous version keep rendering after the rename.
	$si_legacy_showcase = array( 'education_student_stories', 'ei_student_stories' );
	$si_legacy_stats    = array( 'education_graduate_stats' );
	foreach ( $si_legacy_showcase as $si_tag ) {
		if ( ! shortcode_exists( $si_tag ) ) {
			add_shortcode( $si_tag, array( 'SI_Render', 'shortcode' ) );
		}
	}
	foreach ( $si_legacy_stats as $si_tag ) {
		if ( ! shortcode_exists( $si_tag ) ) {
			add_shortcode( $si_tag, array( 'SI_Render', 'stats_shortcode' ) );
		}
	}

	// Register the shared assets so the block.json handle + shortcode can enqueue them.
	si_register_styles();
}
add_action( 'init', 'si_init' );

/**
 * Register (but do not force-enqueue) the shared stylesheet + filter script.
 * The block (via block.json) and the shortcode enqueue them on demand when they render.
 */
function si_register_styles() {
	if ( ! wp_style_is( 'student-impact', 'registered' ) ) {
		wp_register_style(
			'student-impact',
			SI_URL . 'assets/css/student-impact.css',
			array(),
			SI_VERSION
		);
	}
	if ( ! wp_script_is( 'student-impact', 'registered' ) ) {
		wp_register_script(
			'student-impact',
			SI_URL . 'assets/js/student-impact.js',
			array(),
			SI_VERSION,
			true
		);
	}
}

/**
 * The next Monday 06:30 UTC as a Unix timestamp: half an hour after WPCredits-Tracker's weekly
 * sync, so a page of both plugins takes its new figures the same morning.
 *
 * @param int|null $now Current time (for tests); defaults to time().
 * @return int
 */
function si_next_monday_0630_utc( $now = null ) {
	$now  = null === $now ? time() : (int) $now;
	$next = strtotime( 'monday 06:30 UTC', $now );
	if ( false === $next || $next <= $now ) {
		$next = strtotime( 'next monday 06:30 UTC', $now );
	}
	return (int) $next;
}

/**
 * Schedule the weekly sync, replacing any other schedule of the same hook.
 */
function si_schedule_sync() {
	wp_clear_scheduled_hook( SI_CRON_SYNC );
	wp_schedule_event( si_next_monday_0630_utc(), 'weekly', SI_CRON_SYNC );
}

/**
 * Activation: seed the anonymous totals and schedule the weekly sync.
 */
function si_activate() {
	// Ensure default settings exist.
	if ( false === get_option( SI_OPT_SETTINGS, false ) ) {
		update_option( SI_OPT_SETTINGS, si_default_settings() );
	}

	// The showcase ships no snapshot: it names people, so it starts empty and the first sync
	// fills it. Only the anonymous totals below are seeded.

	// Seed the aggregate totals from the bundled snapshot if we have none yet.
	$existing_totals = get_option( SI_OPT_TOTALS, false );
	if ( empty( $existing_totals ) || ! is_array( $existing_totals ) ) {
		$totals_file = SI_DIR . 'data/seed-totals.json';
		if ( is_readable( $totals_file ) ) {
			$totals = json_decode( file_get_contents( $totals_file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( is_array( $totals ) ) {
				update_option( SI_OPT_TOTALS, $totals );
			}
		}
	}

	si_schedule_sync();
}
register_activation_hook( __FILE__, 'si_activate' );

/**
 * Deactivation: clear scheduled events.
 */
function si_deactivate() {
	wp_clear_scheduled_hook( SI_CRON_SYNC );
	wp_clear_scheduled_hook( SI_CRON_RUN );
}
register_deactivation_hook( __FILE__, 'si_deactivate' );
