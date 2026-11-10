<?php
/**
 * What this plugin shares with its neighbors on a site: the Airtable token of the WPCredits
 * Program Manager and the public figures of WPCredits-Tracker.
 *
 * Both are read when needed and never copied into this plugin's own options, so the site keeps
 * one token to look after and one set of program figures.
 *
 * @package Student_Impact
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A token out of a settings option, or '' when the option or the key is not a usable string.
 *
 * @param string $option The option's name.
 * @param string $key    The key that holds the token.
 * @return string
 */
function si_token_from_option( $option, $key ) {
	$settings = get_option( $option, array() );
	if ( ! is_array( $settings ) || ! isset( $settings[ $key ] ) || ! is_string( $settings[ $key ] ) ) {
		return '';
	}
	return trim( $settings[ $key ] );
}

/**
 * Where the token comes from: 'own', 'manager' (the WPCredits Program Manager's), or '' for none.
 *
 * @return string
 */
function si_airtable_token_source() {
	if ( '' !== si_token_from_option( SI_OPT_SETTINGS, 'airtable_pat' ) ) {
		return 'own';
	}
	if ( '' !== si_token_from_option( 'wpcpm_settings', 'api_token' ) ) {
		return 'manager';
	}
	return '';
}

/**
 * The token a sync sends to Airtable, or '' when there is none.
 *
 * @return string
 */
function si_airtable_token() {
	$source = si_airtable_token_source();
	if ( 'own' === $source ) {
		$token = si_token_from_option( SI_OPT_SETTINGS, 'airtable_pat' );
	} elseif ( 'manager' === $source ) {
		$token = si_token_from_option( 'wpcpm_settings', 'api_token' );
	} else {
		$token = '';
	}
	$token = apply_filters( 'si_airtable_token', $token, $source );
	return is_string( $token ) ? $token : '';
}

/**
 * The program figures WPCredits-Tracker publishes, when that plugin has data on this site.
 *
 * Only the figures this plugin prints, each present only when the tracker holds a usable value,
 * so a page of both plugins shows one number for one thing.
 *
 * @return array {
 *     @type int $graduates           Graduates to date.
 *     @type int $first_contributions First contributions made.
 *     @type int $teams               WordPress teams contributed to.
 *     @type int $keep_pct            Percent of graduates who plan to keep contributing.
 * }
 */
function si_tracker_figures() {
	$data = get_option( 'wpct_data', array() );
	if ( ! is_array( $data ) || empty( $data['global'] ) || ! is_array( $data['global'] ) ) {
		return array();
	}
	$global = $data['global'];
	$out    = array();

	if ( isset( $global['graduates'] ) && is_numeric( $global['graduates'] ) && $global['graduates'] > 0 ) {
		$out['graduates'] = (int) $global['graduates'];
	}
	if ( isset( $global['firstContributions'] ) && is_numeric( $global['firstContributions'] ) && $global['firstContributions'] > 0 ) {
		$out['first_contributions'] = (int) $global['firstContributions'];
	}
	if ( ! empty( $global['teamDistribution'] ) && is_array( $global['teamDistribution'] ) ) {
		$out['teams'] = count( $global['teamDistribution'] );
	}
	if ( isset( $data['feedback']['keep']['pct'] ) && is_numeric( $data['feedback']['keep']['pct'] ) ) {
		$out['keep_pct'] = (int) $data['feedback']['keep']['pct'];
	}

	return apply_filters( 'si_tracker_figures', $out );
}
