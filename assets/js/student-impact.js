/**
 * Student Impact - contribution-area filtering, and the short list on a phone.
 * Vanilla JS, no dependencies. Scoped per showcase instance so multiple
 * showcases on one page work independently.
 *
 * The short list: this script marks a long showcase `si--collapsed`, and the stylesheet then shows
 * the first 12 cards on a narrow screen with a button for the rest. Without this script every card
 * shows, so nothing is ever out of reach.
 */
( function () {
	'use strict';

	function initShowcase( section ) {
		var filterBar = section.querySelector( '.si__filters' );
		var more = section.querySelector( '.si__more-btn' );

		function expand() {
			section.classList.remove( 'si--collapsed' );
		}

		if ( more ) {
			section.classList.add( 'si--collapsed' );
			more.addEventListener( 'click', function () {
				var next = section.querySelector( '.si-card:nth-child(13) a' );
				expand();
				if ( next ) {
					next.focus();
				}
			} );
		}

		if ( ! filterBar ) {
			return;
		}

		var buttons = Array.prototype.slice.call( filterBar.querySelectorAll( '.si-filter' ) );
		var cards = Array.prototype.slice.call( section.querySelectorAll( '.si-card' ) );
		var emptyMsg = section.querySelector( '.si__empty-filter' );

		function apply( filter ) {
			var visible = 0;

			// A team's graduates may all sit past the short list, so a filter shows the whole list.
			if ( '*' !== filter ) {
				expand();
			}

			cards.forEach( function ( card ) {
				var team = card.getAttribute( 'data-si-team' ) || '';
				var show = '*' === filter || team === filter;
				card.hidden = ! show;
				if ( show ) {
					visible++;
				}
			} );

			buttons.forEach( function ( btn ) {
				var active = btn.getAttribute( 'data-si-filter' ) === filter;
				btn.classList.toggle( 'is-active', active );
				btn.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
			} );

			if ( emptyMsg ) {
				emptyMsg.hidden = visible !== 0;
			}
		}

		filterBar.addEventListener( 'click', function ( event ) {
			var btn = event.target.closest( '.si-filter' );
			if ( ! btn || ! filterBar.contains( btn ) ) {
				return;
			}
			apply( btn.getAttribute( 'data-si-filter' ) );
		} );
	}

	function init() {
		var showcases = document.querySelectorAll( '.si__grid' );
		Array.prototype.forEach.call( showcases, function ( grid ) {
			initShowcase( grid.closest( '.si' ) );
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
