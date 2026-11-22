/**
 * Bulk Add Categories for WooCommerce - Admin JavaScript.
 *
 * All user-facing strings come from window.bulkAddCat.strings (localized in PHP).
 */
( function( $ ) {
	'use strict';

	var config = window.bulkAddCat || {};
	var strings = config.strings || {};

	$( document ).ready( function () {
		initEventHandlers();
	} );

	/**
	 * Initialize event handlers.
	 */
	function initEventHandlers() {
		$( '.bac-form' ).on( 'submit', function ( e ) {
			e.preventDefault();
			processBatch( $( this ) );
		} );

		$( '.bac-clear-btn' ).on( 'click', function () {
			if ( window.confirm( strings.confirmClear ) ) {
				$( this ).closest( 'form' ).find( 'textarea' ).val( '' );
				hideResults();
				hideProgress();
			}
		} );

		$( '.bac-load-example' ).on( 'click', function () {
			loadExampleData( $( this ).data( 'type' ) );
		} );

		// Show only the parent-brand selector that matches the selected taxonomy.
		var $taxSelect = $( '#brand_taxonomy' );
		if ( $taxSelect.length ) {
			function syncBrandParents() {
				var tax = $taxSelect.val();
				$( '.bac-brand-parent-group' ).hide();
				$( '.bac-brand-parent-group[data-taxonomy="' + tax + '"]' ).show();
			}
			$taxSelect.on( 'change', syncBrandParents ).trigger( 'change' );
		}
	}

	/**
	 * Process a bulk form (single request or chunked batches).
	 *
	 * @param jQuery $form Form element with a data-type attribute.
	 */
	function processBatch( $form ) {
		var type = $form.data( 'type' );
		var $textarea = $form.find( 'textarea[name="bulk_data"]' );
		var rawData = ( $textarea.val() || '' ).trim();

		if ( ! rawData ) {
			window.alert( strings.enterData );
			return;
		}

		var data = parseData( rawData, type );
		if ( 0 === data.length ) {
			window.alert( strings.noValidData );
			return;
		}

		var batchSize = config.batchSize || 50;
		var batchDelay = config.batchDelay || 1000;

		if ( data.length <= batchSize ) {
			processBatchData( type, data, function ( result ) {
				showResults( result, resultSuccessClass( result ) );
			} );
			return;
		}

		processBatches( type, data, batchSize, batchDelay );
	}

	/**
	 * Map a result to a CSS status class.
	 *
	 * @param object result Batch result.
	 * @return string
	 */
	function resultSuccessClass( result ) {
		if ( result.success ) {
			return 'success';
		}
		if ( ( result.created_count || 0 ) > 0 || ( result.skipped_count || 0 ) > 0 ) {
			return 'warning';
		}
		return 'error';
	}

	/**
	 * Chunked processing for large lists.
	 *
	 * @param string type     Operation type.
	 * @param array  data     Parsed items.
	 * @param number batchSize Items per request.
	 * @param number delay     Milliseconds between batches.
	 */
	function processBatches( type, data, batchSize, delay ) {
		var totalBatches = Math.ceil( data.length / batchSize );
		var currentBatch = 0;
		var totals = { created_count: 0, skipped_count: 0, failed_count: 0 };
		var allSkipped = [];
		var allErrors = [];

		showLoading( true );
		hideResults();
		showProgress( 0, totalBatches );

		function processNextBatch() {
			if ( currentBatch >= totalBatches ) {
				showLoading( false );
				hideProgress();
				showResults(
					{
						success: 0 === totals.failed_count,
						message: strings.done,
						created_count: totals.created_count,
						skipped_count: totals.skipped_count,
						failed_count: totals.failed_count,
						skipped: allSkipped,
						errors: allErrors
					},
					0 === totals.failed_count ? 'success' : 'warning'
				);
				return;
			}

			var start = currentBatch * batchSize;
			var batch = data.slice( start, start + batchSize );

			processBatchData( type, batch, function ( result ) {
				totals.created_count += result.created_count || 0;
				totals.skipped_count += result.skipped_count || 0;
				totals.failed_count += result.failed_count || 0;

				( result.skipped || [] ).forEach( function ( item ) {
					allSkipped.push( item );
				} );
				( result.errors || [] ).forEach( function ( item ) {
					allErrors.push( item );
				} );

				currentBatch += 1;
				showProgress( currentBatch, totalBatches );
				window.setTimeout( processNextBatch, delay );
			} );
		}

		processNextBatch();
	}

	/**
	 * Send one batch to the server.
	 *
	 * @param string type     Operation type.
	 * @param array  data     Items for this batch.
	 * @param Function callback Called with a normalized result object.
	 */
	function processBatchData( type, data, callback ) {
		$.ajax( {
			url: config.ajaxUrl,
			type: 'POST',
			data: {
				action: 'bulk_add_cat_process',
				nonce: config.nonce,
				type: type,
				data: JSON.stringify( data )
			},
			success: function ( response ) {
				var result = ( response && response.data ) ? response.data : {};
				if ( ! response.success ) {
					if ( ! result || ! result.message ) {
						result = {
							success: false,
							message: strings.requestFailed + ( response.message || response.data || 'unknown error' ),
							created_count: 0,
							skipped_count: 0,
							failed_count: data.length,
							skipped: [],
							errors: []
						};
					}
				}
				callback( result );
			},
			error: function ( xhr, status, error ) {
				callback( {
					success: false,
					message: strings.requestFailed + error,
					created_count: 0,
					skipped_count: 0,
					failed_count: data.length,
					skipped: [],
					errors: [ { name: strings.requestError, error: error } ]
				} );
			}
		} );
	}

	/**
	 * Show the progress indicator.
	 *
	 * @param number current Batches finished.
	 * @param number total   Total batches.
	 */
	function showProgress( current, total ) {
		var percentage = total > 0 ? Math.round( ( current / total ) * 100 ) : 0;
		var $progress = $( '.bac-progress' );

		$progress.addClass( 'show' );
		$progress.find( '.bac-progress-fill' ).css( 'width', percentage + '%' );
		$progress.find( '.bac-progress-text' ).text(
			strings.progressLabel + ' ' + current + ' / ' + total + ' (' + percentage + '%)'
		);
	}

	/**
	 * Hide the progress indicator.
	 */
	function hideProgress() {
		$( '.bac-progress' ).removeClass( 'show' );
	}

	/**
	 * Parse the raw textarea content.
	 *
	 * @param string rawData Raw text.
	 * @param string type    Operation type.
	 * @return array
	 */
	function parseData( rawData, type ) {
		var lines = rawData.split( '\n' );
		var data = [];

		$.each( lines, function ( index, line ) {
			line = line.trim();

			// Skip blank lines and comments.
			if ( ! line || 0 === line.indexOf( '#' ) || 0 === line.indexOf( '//' ) ) {
				return;
			}

			var item = parseLine( line, type );
			if ( item ) {
				data.push( item );
			}
		} );

		return data;
	}

	/**
	 * Parse a single line for the given operation type.
	 *
	 * @param string line Line content.
	 * @param string type Operation type.
	 * @return object|null
	 */
	function parseLine( line, type ) {
		var parts = line.split( '|' ).map( function ( p ) {
			return p.trim();
		} );

		switch ( type ) {
			case 'categories':
				return parseCategoryLine( parts );
			case 'brands':
				return parseBrandLine( parts );
			case 'attributes':
				return parseAttributeLine( parts );
			default:
				return null;
		}
	}

	/**
	 * Get the selected parent ID for categories.
	 *
	 * @return number
	 */
	function getCategoriesParentId() {
		var $parentSelect = $( '#parent_category' );
		if ( $parentSelect.length && $parentSelect.val() ) {
			return parseInt( $parentSelect.val(), 10 ) || 0;
		}
		return 0;
	}

	/**
	 * Get the selected parent ID for the active brand taxonomy.
	 *
	 * @return number
	 */
	function getBrandsParentId() {
		var tax = $( '#brand_taxonomy' ).val() || '';
		var $select = $( '.bac-brand-parent-group[data-taxonomy="' + tax + '"] select' );
		if ( $select.length && $select.val() ) {
			return parseInt( $select.val(), 10 ) || 0;
		}
		return 0;
	}

	/**
	 * Parse a category line.
	 * Format: Name | Parent (optional) | Description (optional) | Slug (optional)
	 *
	 * @param array parts Pipe-split fields.
	 * @return object|null
	 */
	function parseCategoryLine( parts ) {
		if ( ! parts[0] ) {
			return null;
		}

		var parentId = getCategoriesParentId();
		// A parent written on the line wins; the dropdown is only the fallback for
		// lines that do not name their own parent.
		var inlineParent = parts[1] || '';

		return {
			name: parts[0],
			parent_id: inlineParent ? 0 : parentId,
			parent_name: inlineParent,
			description: parts[2] || '',
			slug: parts[3] || ''
		};
	}

	/**
	 * Parse a brand line.
	 * Format: Name | Description (optional) | Slug (optional)
	 *
	 * @param array parts Pipe-split fields.
	 * @return object|null
	 */
	function parseBrandLine( parts ) {
		if ( ! parts[0] ) {
			return null;
		}

		return {
			taxonomy: $( '#brand_taxonomy' ).val() || '',
			name: parts[0],
			parent_id: getBrandsParentId(),
			description: parts[1] || '',
			slug: parts[2] || ''
		};
	}

	/**
	 * Parse an attribute line.
	 * Format: Name | Values (comma separated) | Type (optional) | Slug (optional)
	 *
	 * @param array parts Pipe-split fields.
	 * @return object|null
	 */
	function parseAttributeLine( parts ) {
		if ( ! parts[0] ) {
			return null;
		}

		var values = [];
		if ( parts[1] ) {
			values = parts[1].split( ',' ).map( function ( v ) {
				return v.trim();
			} ).filter( function ( v ) {
				return v;
			} );
		}

		return {
			name: parts[0],
			values: values,
			type: parts[2] || 'select',
			slug: parts[3] || ''
		};
	}

	/**
	 * Load example data into the textarea.
	 *
	 * @param string type Operation type.
	 */
	function loadExampleData( type ) {
		var examples = {
			categories: [
				'# Product category examples (one category per line)',
				'# Format: Name | Parent | Description | Slug',
				'#',
				'Electronics',
				'Mobile Phones | Electronics | Smartphones and tablets',
				'Laptops | Electronics | Notebook computers | laptops',
				'Smart Watches',
				'Headphones',
				'Chargers'
			].join( '\n' ),

			brands: [
				'# Brand examples (one brand per line)',
				'# Format: Name | Description | Slug',
				'#',
				'Apple | Apple Inc. | apple',
				'Samsung | Samsung Electronics | samsung',
				'Nike | Nike, Inc. | nike'
			].join( '\n' ),

			attributes: [
				'# Attribute examples (one attribute per line)',
				'# Format: Name | Values (comma separated) | Type | Slug',
				'#',
				'Color | Red,Blue,Black,White,Green | select | color',
				'Size | S,M,L,XL,XXL | select | size',
				'Material | Cotton,Polyester,Wool,Silk | select | material',
				'Storage | 64GB,128GB,256GB,512GB | select | storage'
			].join( '\n' )
		};

		$( 'textarea[name="bulk_data"]' ).val( examples[ type ] || examples.categories );
	}

	/**
	 * Show / hide the processing state.
	 *
	 * @param boolean show Whether to show the spinner.
	 */
	function showLoading( show ) {
		var $loading = $( '.bac-loading' );
		var $submitBtn = $( '.bac-form button[type="submit"]' );

		if ( show ) {
			if ( ! $submitBtn.data( 'originalLabel' ) ) {
				$submitBtn.data( 'originalLabel', $submitBtn.text() );
			}
			$loading.addClass( 'show' );
			$submitBtn.prop( 'disabled', true ).text( strings.processing );
		} else {
			$loading.removeClass( 'show' );
			$submitBtn
				.prop( 'disabled', false )
				.text( $submitBtn.data( 'originalLabel' ) || strings.startLabel );
		}
	}

	/**
	 * Render batch results with created / skipped / failed stats.
	 *
	 * @param object  data   Normalized result.
	 * @param string  status One of: success, warning, error.
	 */
	function showResults( data, status ) {
		var $results = $( '.bac-results' );
		var html = '';

		if ( data.message ) {
			var messageClass = 'success-message';
			if ( 'error' === status ) {
				messageClass = 'error-message';
			} else if ( 'warning' === status ) {
				messageClass = 'warning-message';
			}
			html += '<div class="' + messageClass + '">' + escapeHtml( data.message ) + '</div>';
		}

		if ( typeof data.created_count !== 'undefined' || typeof data.skipped_count !== 'undefined' || typeof data.failed_count !== 'undefined' ) {
			html += '<div class="bac-stats">';
			html += '<div class="bac-stat created"><div class="number">' + ( data.created_count || 0 ) + '</div><div class="label">' + escapeHtml( strings.created ) + '</div></div>';
			html += '<div class="bac-stat skipped"><div class="number">' + ( data.skipped_count || 0 ) + '</div><div class="label">' + escapeHtml( strings.skipped ) + '</div></div>';
			html += '<div class="bac-stat failed"><div class="number">' + ( data.failed_count || 0 ) + '</div><div class="label">' + escapeHtml( strings.failed ) + '</div></div>';
			html += '</div>';
		}

		if ( data.skipped && data.skipped.length > 0 ) {
			html += '<details class="bac-skipped-list"><summary>' + escapeHtml( strings.skippedItems ) + '</summary><ul>';
			$.each( data.skipped, function ( i, item ) {
				html += '<li>' + escapeHtml( item.name || '' );
				if ( item.reason ) {
					html += ' — ' + escapeHtml( item.reason );
				}
				html += '</li>';
			} );
			html += '</ul></details>';
		}

		if ( data.errors && data.errors.length > 0 ) {
			html += '<div class="bac-error-list"><strong>' + escapeHtml( strings.errorDetails ) + '</strong><ul>';
			$.each( data.errors, function ( i, item ) {
				html += '<li>' + escapeHtml( item.name || '' ) + ': ' + escapeHtml( item.error || '' ) + '</li>';
			} );
			html += '</ul></div>';
		}

		/* Auto-reload after items were created, so the sidebar lists stay in sync. */
		if ( ( data.created_count || 0 ) > 0 ) {
			html += '<div class="bac-refresh-hint" style="margin-top:10px;padding:8px 12px;background:#f0f6fc;border-radius:4px;">'
				+ '<span>' + escapeHtml( strings.reloadHint ) + '</span> '
				+ '<button class="button button-small" onclick="window.location.reload()" style="margin-left:8px;">' + escapeHtml( strings.reloadNow ) + '</button>'
				+ '</div>';
			window.setTimeout( function () { window.location.reload(); }, 3000 );
		}

		$results.html( html ).addClass( 'show' );
	}

	/**
	 * Hide the results panel.
	 */
	function hideResults() {
		$( '.bac-results' ).removeClass( 'show' ).empty();
	}

	/**
	 * Escape a string for HTML insertion.
	 *
	 * @param string text Raw text.
	 * @return string
	 */
	function escapeHtml( text ) {
		if ( ! text ) {
			return '';
		}
		var div = document.createElement( 'div' );
		div.appendChild( document.createTextNode( text ) );
		return div.innerHTML;
	}
} )( jQuery );
