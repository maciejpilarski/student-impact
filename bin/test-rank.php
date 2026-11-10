<?php
$keep = array();
foreach ( array( SI_OPT_DATA, SI_OPT_TOTALS, SI_OPT_STATE, SI_OPT_LASTSYNC, SI_OPT_LASTERR ) as $o ) { $keep[ $o ] = get_option( $o, null ); }
$m = new ReflectionMethod( 'SI_Sync', 'phase_rank' ); $m->setAccessible( true );
$row = function ( $u, $s, $c ) { return array( 'name' => $u, 'username' => $u, 'team' => 'Test', 'score' => $s, 'contributions' => $c, 'high' => 1, 'recent90' => $c ? 1 : 0, 'avatar' => '' ); };
$ok = 0; $n = 0;
$check = function ( $label, $cond ) use ( &$ok, &$n ) { $n++; if ( $cond ) { $ok++; } echo ( $cond ? 'ok   ' : 'FAIL ' ) . $label . "\n"; };

// 1. Half impact, half contributions; no hours anywhere.
$rows = array( $row( 'a', 100, 10 ), $row( 'b', 50, 100 ), $row( 'c', 100, 100 ), $row( 'd', 0, 0 ) );
$m->invoke( null, array( 'count' => 3 ), array( 'results' => $rows, 'grad_count' => 5, 'failed' => 0 ) );
$d = get_option( SI_OPT_DATA ); $t = get_option( SI_OPT_TOTALS );
$check( 'order c, b, a', array( 'c', 'b', 'a' ) === wp_list_pluck( $d, 'username' ) );
$check( 'composites 100, 75, 55', array( 100.0, 75.0, 55.0 ) === array_map( 'floatval', wp_list_pluck( $d, 'composite' ) ) );
$check( 'no hours key in data or totals', ! isset( $d[0]['hours'] ) && ! isset( $t['hours'] ) );
$check( 'totals: 5 graduates, 4 profiles, 210 contributions, 250 impact, 3 active', 5 === $t['students'] && 4 === $t['profiles'] && 210 === $t['contributions'] && 250 === $t['impact'] && 3 === $t['active'] );

// 2. Too many failed reads keeps the previous figures.
$before = get_option( SI_OPT_DATA );
$m->invoke( null, array( 'count' => 3 ), array( 'results' => array( $row( 'x', 9, 9 ), $row( 'y', 0, 0 ), $row( 'z', 0, 0 ) ), 'failed' => 2 ) );
$st = get_option( SI_OPT_STATE );
$check( 'failed reads: data kept, state error', $before === get_option( SI_OPT_DATA ) && 'error' === $st['phase'] && '' !== (string) get_option( SI_OPT_LASTERR ) );

// 3. No impact panel on any profile keeps the previous figures.
delete_option( SI_OPT_LASTERR );
$m->invoke( null, array( 'count' => 3 ), array( 'results' => array( $row( 'x', 0, 0 ), $row( 'y', 0, 0 ) ), 'failed' => 0 ) );
$check( 'no panel: data kept, error says so', $before === get_option( SI_OPT_DATA ) && false !== strpos( (string) get_option( SI_OPT_LASTERR ), 'Recent impact' ) );

// 4. Token: own first, then the Program Manager's, then none.
$own = get_option( SI_OPT_SETTINGS ); $mgr = get_option( 'wpcpm_settings' );
update_option( SI_OPT_SETTINGS, array( 'airtable_pat' => '' ) ); update_option( 'wpcpm_settings', array_merge( (array) $mgr, array( 'api_token' => 'patMANAGER' ) ) );
$check( 'falls back to the manager token', 'manager' === si_airtable_token_source() && 'patMANAGER' === si_airtable_token() );
update_option( SI_OPT_SETTINGS, array( 'airtable_pat' => 'patOWN' ) );
$check( 'own token wins', 'own' === si_airtable_token_source() && 'patOWN' === si_airtable_token() );
update_option( SI_OPT_SETTINGS, array( 'airtable_pat' => '' ) ); update_option( 'wpcpm_settings', array_merge( (array) $mgr, array( 'api_token' => '' ) ) );
$check( 'no token anywhere', '' === si_airtable_token() );
update_option( SI_OPT_SETTINGS, $own ); update_option( 'wpcpm_settings', $mgr );

// 5. Tracker figures: absent tracker data means no shared tiles.
add_filter( 'pre_option_wpct_data', '__return_empty_array' );
$check( 'no tracker data: no shared figures', array() === si_tracker_figures() );
$html = SI_Render::render_stats( array() );
$check( 'without tracker: 3 tiles, own graduate count', 3 === substr_count( $html, 'class="si-bigstat ' ) );
remove_filter( 'pre_option_wpct_data', '__return_empty_array' );

foreach ( $keep as $o => $v ) { if ( null === $v ) { delete_option( $o ); } else { update_option( $o, $v ); } }
$html = SI_Render::render_stats( array() ) . SI_Render::render( array() );
$check( 'rendered page has no "hour"', false === stripos( $html, 'hour' ) );
$check( 'with tracker: 6 tiles', 6 === substr_count( $html, 'class="si-bigstat ' ) );
echo "$ok of $n passed\n";
