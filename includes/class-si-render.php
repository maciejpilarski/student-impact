<?php
/**
 * Rendering for the Student Stories showcase, shared by the block and the shortcode.
 *
 * @package Student_Impact
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SI_Render {

	/**
	 * Shortcode handler: [student_impact count="15" columns="3" title="..."].
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'count'    => 0, // 0 = show all synced (up to the synced count).
				'columns'  => 3,
				'title'    => __( 'Student impact', 'student-impact' ),
				'subtitle' => __( 'Graduates of the program, ranked by their recent impact and contributions on WordPress.org.', 'student-impact' ),
				'filters'  => 'yes',
				'legend'   => 'yes',
			),
			$atts,
			'student_impact'
		);

		return self::render(
			array(
				'count'    => (int) $atts['count'],
				'columns'  => (int) $atts['columns'],
				'title'    => (string) $atts['title'],
				'subtitle' => (string) $atts['subtitle'],
				'filters'  => self::truthy( $atts['filters'] ),
				'legend'   => self::truthy( $atts['legend'] ),
			)
		);
	}

	/**
	 * Shortcode handler: [student_impact_stats title="..."].
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function stats_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'title'    => __( 'Graduate impact', 'student-impact' ),
				'subtitle' => __( 'What graduates of the program have contributed to WordPress.', 'student-impact' ),
				'note'     => 'yes',
				'layout'   => 'grid',
			),
			$atts,
			'student_impact_stats'
		);

		return self::render_stats(
			array(
				'title'    => (string) $atts['title'],
				'subtitle' => (string) $atts['subtitle'],
				'note'     => self::truthy( $atts['note'] ),
				'layout'   => (string) $atts['layout'],
			)
		);
	}

	/**
	 * Render the class-wide aggregate stats (totals across ALL graduates).
	 *
	 * @param array $args {
	 *     @type string $title    Section heading.
	 *     @type string $subtitle Section subheading.
	 *     @type bool   $note     Whether to show the "across N graduates" footnote.
	 *     @type string $layout   'grid' (cards) or 'inline' (compact horizontal strip).
	 * }
	 * @return string HTML.
	 */
	public static function render_stats( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'title'    => __( 'Graduate impact', 'student-impact' ),
				'subtitle' => '',
				'note'     => true,
				'layout'   => 'grid',
			)
		);
		$layout = ( 'inline' === $args['layout'] ) ? 'inline' : 'grid';

		if ( wp_style_is( 'student-impact', 'registered' ) ) {
			wp_enqueue_style( 'student-impact' );
		}

		$t = get_option( SI_OPT_TOTALS, array() );
		if ( ! is_array( $t ) || empty( $t ) ) {
			return self::empty_notice();
		}
		$t = wp_parse_args(
			$t,
			array(
				'students'      => 0,
				'profiles'      => 0,
				'contributions' => 0,
				'impact'        => 0,
				'high'          => 0,
				'active'        => 0,
			)
		);

		// The program's own figures come from WPCredits-Tracker when it runs here, so a
		// page of both plugins shows one number for one thing.
		$shared    = si_tracker_figures();
		$graduates = isset( $shared['graduates'] ) ? $shared['graduates'] : (int) $t['students'];

		$tiles = array(
			array(
				'key'   => 'students',
				'value' => number_format_i18n( $graduates ),
				'label' => __( 'Graduates to date', 'student-impact' ),
			),
			array(
				'key'   => 'contrib',
				'value' => number_format_i18n( (int) $t['contributions'] ),
				'label' => __( 'Contributions by graduates in the last 12 months', 'student-impact' ),
			),
			array(
				'key'   => 'impact',
				'value' => number_format_i18n( (int) $t['impact'] ),
				'label' => __( 'Recent impact score, as WordPress.org counts it', 'student-impact' ),
			),
		);
		if ( isset( $shared['first_contributions'] ) ) {
			$tiles[] = array(
				'key'   => 'first',
				'value' => number_format_i18n( $shared['first_contributions'] ),
				'label' => __( 'First contributions made', 'student-impact' ),
			);
		}
		if ( isset( $shared['teams'] ) ) {
			$tiles[] = array(
				'key'   => 'teams',
				'value' => number_format_i18n( $shared['teams'] ),
				'label' => __( 'WordPress teams contributed to', 'student-impact' ),
			);
		}
		if ( isset( $shared['keep_pct'] ) ) {
			$tiles[] = array(
				'key'   => 'keep',
				/* translators: %s: a percentage figure. */
				'value' => sprintf( __( '%s%%', 'student-impact' ), number_format_i18n( $shared['keep_pct'] ) ),
				'label' => __( 'Graduates who plan to keep contributing to WordPress', 'student-impact' ),
			);
		}

		$stamp = (int) get_option( SI_OPT_LASTSYNC, 0 );

		ob_start();
		?>
		<section class="si-stats si-stats--<?php echo esc_attr( $layout ); ?>">
			<?php if ( $args['title'] || $args['subtitle'] ) : ?>
				<header class="si-stats__head">
					<?php if ( $args['title'] ) : ?>
						<h2 class="si-stats__title"><?php echo esc_html( $args['title'] ); ?></h2>
					<?php endif; ?>
					<?php if ( $args['subtitle'] ) : ?>
						<p class="si-stats__subtitle"><?php echo esc_html( $args['subtitle'] ); ?></p>
					<?php endif; ?>
				</header>
			<?php endif; ?>

			<dl class="si-stats__grid">
				<?php foreach ( $tiles as $tile ) : ?>
					<div class="si-bigstat si-bigstat--<?php echo esc_attr( $tile['key'] ); ?>">
						<dt class="si-bigstat__label"><?php echo esc_html( $tile['label'] ); ?></dt>
						<dd class="si-bigstat__value"><?php echo esc_html( $tile['value'] ); ?></dd>
					</div>
				<?php endforeach; ?>
			</dl>

			<?php if ( $args['note'] ) : ?>
				<p class="si-stats__note">
					<?php
					printf(
						/* translators: 1: graduates with a WordPress.org profile, 2: graduates who contributed in the last 90 days. */
						esc_html__( 'Contributions and impact are read from the WordPress.org profiles of %1$s graduates. %2$s of them contributed in the last 90 days.', 'student-impact' ),
						esc_html( number_format_i18n( (int) $t['profiles'] ) ),
						esc_html( number_format_i18n( (int) $t['active'] ) )
					);
					if ( $stamp > 0 ) {
						echo ' ';
						printf(
							/* translators: %s: the date of the last successful sync. */
							esc_html__( 'Last updated on %s.', 'student-impact' ),
							'<time datetime="' . esc_attr( wp_date( 'Y-m-d', $stamp ) ) . '">' . esc_html( wp_date( (string) get_option( 'date_format', 'F j, Y' ), $stamp ) ) . '</time>'
						);
					}
					?>
				</p>
			<?php endif; ?>
		</section>
		<?php
		return trim( ob_get_clean() );
	}

	/**
	 * Interpret a shortcode boolean-ish attribute.
	 *
	 * @param mixed $value Value.
	 * @return bool
	 */
	private static function truthy( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}
		return in_array( strtolower( (string) $value ), array( '1', 'yes', 'true', 'on' ), true );
	}

	/**
	 * Render the showcase.
	 *
	 * @param array $args {
	 *     @type int    $count    Max students to show (0 = all synced).
	 *     @type int    $columns  Grid columns (2-4).
	 *     @type string $title    Section heading.
	 *     @type string $subtitle Section subheading.
	 * }
	 * @return string HTML.
	 */
	public static function render( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'count'    => 0,
				'columns'  => 3,
				'title'    => __( 'Student impact', 'student-impact' ),
				'subtitle' => '',
				'filters'  => true,
				'legend'   => true,
			)
		);

		if ( wp_style_is( 'student-impact', 'registered' ) ) {
			wp_enqueue_style( 'student-impact' );
		}

		$students = get_option( SI_OPT_DATA, array() );
		if ( ! is_array( $students ) || empty( $students ) ) {
			return self::empty_notice();
		}

		$count = (int) $args['count'];
		if ( $count > 0 ) {
			$students = array_slice( $students, 0, $count );
		}

		$columns = min( 4, max( 2, (int) $args['columns'] ) );

		// Build the ordered list of contribution areas present (by frequency, then name).
		$teams = self::team_counts( $students );
		$show_filters = ! empty( $args['filters'] ) && count( $teams ) > 1;

		// The first cards a phone shows before "Show all"; a shorter list needs no button.
		$short     = 12;
		$show_more = count( $students ) > $short;

		if ( ( $show_filters || $show_more ) && wp_script_is( 'student-impact', 'registered' ) ) {
			wp_enqueue_script( 'student-impact' );
		}

		ob_start();
		?>
		<section class="si<?php echo $show_filters ? ' si--has-filters' : ''; ?>" style="--si-cols: <?php echo (int) $columns; ?>;">
			<?php if ( $args['title'] || $args['subtitle'] ) : ?>
				<header class="si__head">
					<?php if ( $args['title'] ) : ?>
						<h2 class="si__title"><?php echo esc_html( $args['title'] ); ?></h2>
					<?php endif; ?>
					<?php if ( $args['subtitle'] ) : ?>
						<p class="si__subtitle"><?php echo esc_html( $args['subtitle'] ); ?></p>
					<?php endif; ?>
				</header>
			<?php endif; ?>

			<?php if ( $show_filters ) : ?>
				<div class="si__filters" role="group" aria-label="<?php esc_attr_e( 'Filter students by contribution area', 'student-impact' ); ?>">
					<button type="button" class="si-filter is-active" data-si-filter="*" aria-pressed="true">
						<?php esc_html_e( 'All', 'student-impact' ); ?>
						<span class="si-filter__count"><?php echo esc_html( number_format_i18n( count( $students ) ) ); ?></span>
					</button>
					<?php foreach ( $teams as $team => $n ) : ?>
						<button type="button" class="si-filter" data-si-filter="<?php echo esc_attr( $team ); ?>" aria-pressed="false">
							<?php echo esc_html( $team ); ?>
							<span class="si-filter__count"><?php echo esc_html( number_format_i18n( $n ) ); ?></span>
						</button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<ul class="si__grid" role="list">
				<?php foreach ( $students as $s ) : ?>
					<?php echo self::card( $s ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endforeach; ?>
			</ul>

			<?php if ( $show_filters ) : ?>
				<p class="si__empty-filter" hidden><?php esc_html_e( 'No students in this area.', 'student-impact' ); ?></p>
			<?php endif; ?>

			<?php if ( $show_more ) : ?>
				<p class="si__more">
					<button type="button" class="si-filter si__more-btn">
						<?php
						/* translators: %s: how many graduates the showcase lists. */
						echo esc_html( sprintf( __( 'Show all %s graduates', 'student-impact' ), number_format_i18n( count( $students ) ) ) );
						?>
					</button>
				</p>
			<?php endif; ?>

			<?php
			if ( ! empty( $args['legend'] ) ) {
				echo self::legend(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</section>
		<?php
		return trim( ob_get_clean() );
	}

	/**
	 * The legend under the showcase: what each figure and chip on a card means.
	 *
	 * Each term is drawn as it is on a card, so a reader matches it by sight.
	 *
	 * @return string HTML.
	 */
	private static function legend() {
		$items = array(
			array(
				'term' => '<span class="si-legend__figure">' . esc_html__( 'Impact', 'student-impact' ) . '</span>',
				'text' => __( 'The recent impact score on the graduate\'s WordPress.org profile, for the last 12 months. WordPress.org weighs each contribution: high-impact work counts three times as much as routine activity.', 'student-impact' ),
			),
			array(
				'term' => '<span class="si-legend__figure">' . esc_html__( 'Contributions', 'student-impact' ) . '</span>',
				'text' => __( 'How many contributions WordPress.org recorded for the graduate in the last 12 months, of any kind.', 'student-impact' ),
			),
			array(
				'term' => '<span class="si-legend__rank">#1</span>',
				'text' => __( 'The graduate\'s place in the list. Impact and contributions count half each; the first three sit on a tinted card.', 'student-impact' ),
			),
			array(
				'term' => '<span class="si-badge si-badge--active">' . esc_html__( 'Active now', 'student-impact' ) . '</span>',
				'text' => __( 'The graduate made at least one contribution in the last 90 days.', 'student-impact' ),
			),
			array(
				'term' => '<span class="si-badge si-badge--team">' . esc_html__( 'Polyglots', 'student-impact' ) . '</span>',
				'text' => __( 'The WordPress team the graduate mainly contributes to, Polyglots for example. The buttons above the cards filter by it.', 'student-impact' ),
			),
			array(
				/* translators: %s: number of high-impact contributions. */
				'term' => '<span class="si-badge si-badge--high">' . esc_html( sprintf( _n( '%s high-impact', '%s high-impact', 8, 'student-impact' ), number_format_i18n( 8 ) ) ) . '</span>',
				'text' => __( 'How many of those contributions WordPress.org counts as high-impact, such as commits, releases, approved translations and props.', 'student-impact' ),
			),
		);

		$html = '<div class="si-legend"><h3 class="si-legend__title">' . esc_html__( 'What the figures mean', 'student-impact' ) . '</h3><dl class="si-legend__list">';
		foreach ( $items as $item ) {
			$html .= '<div class="si-legend__item"><dt>' . $item['term'] . '</dt><dd>' . esc_html( $item['text'] ) . '</dd></div>';
		}
		return $html . '</dl></div>';
	}

	/**
	 * Count students per contribution area, ordered by frequency (desc) then name.
	 *
	 * @param array $students Students.
	 * @return array team name => count.
	 */
	private static function team_counts( $students ) {
		$counts = array();
		foreach ( $students as $s ) {
			$team = isset( $s['team'] ) ? trim( (string) $s['team'] ) : '';
			if ( '' === $team ) {
				continue;
			}
			$counts[ $team ] = isset( $counts[ $team ] ) ? $counts[ $team ] + 1 : 1;
		}
		uksort(
			$counts,
			static function ( $a, $b ) use ( $counts ) {
				if ( $counts[ $a ] === $counts[ $b ] ) {
					return strcasecmp( $a, $b );
				}
				return $counts[ $b ] <=> $counts[ $a ];
			}
		);
		return $counts;
	}

	/**
	 * Render a single student card.
	 *
	 * @param array $s Student data.
	 * @return string
	 */
	private static function card( $s ) {
		$s = wp_parse_args(
			$s,
			array(
				'rank'          => 0,
				'name'          => '',
				'username'      => '',
				'url'           => '',
				'avatar'        => '',
				'team'          => '',
				'score'         => 0,
				'contributions' => 0,
				'high'          => 0,
				'recent90'      => 0,
			)
		);

		$rank      = (int) $s['rank'];
		$is_active = (int) $s['recent90'] > 0;
		$top       = ( $rank >= 1 && $rank <= 3 ) ? ' si-card--top' : '';
		$team      = trim( (string) $s['team'] );

		ob_start();
		?>
		<li class="si-card<?php echo esc_attr( $top ); ?>" data-si-team="<?php echo esc_attr( $team ); ?>">
			<div class="si-card__who">
				<?php if ( $s['avatar'] ) : ?>
					<img class="si-card__avatar" src="<?php echo esc_url( $s['avatar'] ); ?>" alt="" loading="lazy" width="64" height="64" />
				<?php else : ?>
					<span class="si-card__avatar si-card__avatar--ph" aria-hidden="true"><?php echo esc_html( self::initials( $s['name'] ) ); ?></span>
				<?php endif; ?>
				<div class="si-card__id">
					<h3 class="si-card__name">
						<?php if ( $s['url'] ) : ?>
							<a href="<?php echo esc_url( $s['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $s['name'] ); ?></a>
						<?php else : ?>
							<?php echo esc_html( $s['name'] ); ?>
						<?php endif; ?>
					</h3>
					<?php if ( $s['username'] ) : ?>
						<p class="si-card__handle">@<?php echo esc_html( $s['username'] ); ?></p>
					<?php endif; ?>
				</div>
				<?php if ( $rank ) : ?>
					<span class="si-card__rank">
						<span class="screen-reader-text"><?php esc_html_e( 'Rank', 'student-impact' ); ?></span>
						#<?php echo (int) $rank; ?>
					</span>
				<?php endif; ?>
			</div>

			<dl class="si-card__stats">
				<div class="si-stat si-stat--impact">
					<dt><?php esc_html_e( 'Impact', 'student-impact' ); ?></dt>
					<dd><?php echo esc_html( number_format_i18n( $s['score'] ) ); ?></dd>
				</div>
				<div class="si-stat si-stat--contrib">
					<dt><?php esc_html_e( 'Contributions', 'student-impact' ); ?></dt>
					<dd><?php echo esc_html( number_format_i18n( $s['contributions'] ) ); ?></dd>
				</div>
			</dl>

			<?php if ( '' !== $team || $is_active || (int) $s['high'] > 0 ) : ?>
				<p class="si-card__badges">
					<?php if ( '' !== $team ) : ?>
						<span class="si-badge si-badge--team"><?php echo esc_html( $team ); ?></span>
					<?php endif; ?>
					<?php if ( $is_active ) : ?>
						<span class="si-badge si-badge--active"><?php esc_html_e( 'Active now', 'student-impact' ); ?></span>
					<?php endif; ?>
					<?php if ( (int) $s['high'] > 0 ) : ?>
						<span class="si-badge si-badge--high">
							<?php
							/* translators: %s: number of high-impact contributions. */
							echo esc_html( sprintf( _n( '%s high-impact', '%s high-impact', (int) $s['high'], 'student-impact' ), number_format_i18n( $s['high'] ) ) );
							?>
						</span>
					<?php endif; ?>
				</p>
			<?php endif; ?>
		</li>
		<?php
		return ob_get_clean();
	}

	/**
	 * Placeholder shown when there's no data yet.
	 *
	 * @return string
	 */
	private static function empty_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return '';
		}
		$url = esc_url( admin_url( 'admin.php?page=student-impact' ) );
		return '<div class="si si--empty"><p>'
			. esc_html__( 'No student stories yet.', 'student-impact' ) . ' '
			. '<a href="' . $url . '">' . esc_html__( 'Run a sync from the Student Impact screen.', 'student-impact' ) . '</a>'
			. '</p></div>';
	}

	/**
	 * Initials fallback for a missing avatar.
	 *
	 * @param string $name Full name.
	 * @return string
	 */
	private static function initials( $name ) {
		$parts = preg_split( '/\s+/u', trim( (string) $name ) );
		$parts = array_values( array_filter( $parts ) );
		if ( empty( $parts ) ) {
			return '?';
		}
		$first = mb_substr( $parts[0], 0, 1 );
		$last  = count( $parts ) > 1 ? mb_substr( end( $parts ), 0, 1 ) : '';
		return mb_strtoupper( $first . $last );
	}
}
