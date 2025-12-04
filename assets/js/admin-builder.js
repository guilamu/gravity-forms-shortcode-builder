/**
 * Gravity Forms Shortcode Builder - Admin Builder Scripts
 *
 * @package GF_Shortcode_Builder
 * @since 1.2.0
 */

/* global jQuery, ajaxurl, gfsbBuilder */

(function($) {
	'use strict';

	var gfsbDraggedElement = null;

	/**
	 * Set accordion visibility based on enabled state.
	 *
	 * @param {string} tabId  The tab identifier.
	 * @param {boolean} enabled Whether the tab is enabled.
	 */
	function gfsbSetAccordionVisibility(tabId, enabled) {
		var accordion = document.querySelector('.gfsb-accordion[data-tab="' + tabId + '"]');
		if (accordion) {
			accordion.setAttribute('data-tab-enabled', enabled ? '1' : '0');
			accordion.style.display = enabled ? '' : 'none';
		}
	}

	/**
	 * Initialize tab toggle checkboxes.
	 */
	function gfsbInitTabToggles() {
		var toggles = document.querySelectorAll('.gfsb-tab-toggle-input');
		if (!toggles.length) {
			return;
		}

		toggles.forEach(function(toggle) {
			var tabId = toggle.getAttribute('data-tab');
			gfsbSetAccordionVisibility(tabId, toggle.checked);

			toggle.addEventListener('change', function(event) {
				var target = event.target;
				var id = target.getAttribute('data-tab');
				var isEnabled = target.checked;
				gfsbSetAccordionVisibility(id, isEnabled);

				var data = {
					action: 'gfsb_save_tab_visibility',
					nonce: gfsbBuilder.toggleNonce,
					tab_id: id,
					enabled: isEnabled ? '1' : '0'
				};

				$.post(ajaxurl, data);
			});
		});
	}

	/**
	 * Toggle accordion open/closed state.
	 *
	 * @param {Event} event The click event.
	 * @param {string} tabId The tab identifier.
	 */
	window.gfsbToggleAccordion = function(event, tabId) {
		event.preventDefault();
		event.stopPropagation();

		var accordion = event.currentTarget.closest('.gfsb-accordion');
		accordion.classList.toggle('open');
	};

	/**
	 * Handle drag start event.
	 *
	 * @param {DragEvent} event The drag event.
	 */
	window.gfsbDragStart = function(event) {
		gfsbDraggedElement = event.currentTarget;
		event.currentTarget.classList.add('dragging');
		event.dataTransfer.effectAllowed = 'move';
		event.dataTransfer.setData('text/html', event.currentTarget.innerHTML);
	};

	/**
	 * Handle drag end event.
	 *
	 * @param {DragEvent} event The drag event.
	 */
	window.gfsbDragEnd = function(event) {
		event.currentTarget.classList.remove('dragging');

		// Remove all drag-over classes.
		document.querySelectorAll('.gfsb-accordion').forEach(function(accordion) {
			accordion.classList.remove('drag-over');
		});

		// Save the new order.
		gfsbSaveAccordionOrder();
	};

	/**
	 * Handle drag over event.
	 *
	 * @param {DragEvent} event The drag event.
	 * @return {boolean} False to allow drop.
	 */
	window.gfsbDragOver = function(event) {
		if (event.preventDefault) {
			event.preventDefault();
		}
		event.dataTransfer.dropEffect = 'move';

		var target = event.currentTarget;
		if (target !== gfsbDraggedElement && target.classList.contains('gfsb-accordion')) {
			target.classList.add('drag-over');
		}

		return false;
	};

	/**
	 * Handle drag leave event.
	 *
	 * @param {DragEvent} event The drag event.
	 */
	window.gfsbDragLeave = function(event) {
		event.currentTarget.classList.remove('drag-over');
	};

	/**
	 * Handle drop event.
	 *
	 * @param {DragEvent} event The drag event.
	 * @return {boolean} False to prevent default handling.
	 */
	window.gfsbDrop = function(event) {
		if (event.stopPropagation) {
			event.stopPropagation();
		}

		var target = event.currentTarget;

		if (gfsbDraggedElement !== target && target.classList.contains('gfsb-accordion')) {
			var container = document.getElementById('gfsb-accordions-container');
			var allAccordions = Array.from(container.children);
			var draggedIndex = allAccordions.indexOf(gfsbDraggedElement);
			var targetIndex = allAccordions.indexOf(target);

			if (draggedIndex < targetIndex) {
				target.parentNode.insertBefore(gfsbDraggedElement, target.nextSibling);
			} else {
				target.parentNode.insertBefore(gfsbDraggedElement, target);
			}
		}

		target.classList.remove('drag-over');

		return false;
	};

	/**
	 * Save the accordion order via AJAX.
	 */
	function gfsbSaveAccordionOrder() {
		var accordions = document.querySelectorAll('.gfsb-accordion');
		var accordionOrder = [];

		accordions.forEach(function(accordion) {
			accordionOrder.push(accordion.getAttribute('data-tab'));
		});

		// Save via AJAX.
		var data = {
			action: 'gfsb_save_tab_order',
			nonce: gfsbBuilder.orderNonce,
			tab_order: accordionOrder
		};

		$.post(ajaxurl, data, function(response) {
			if (response.success) {
				// Show success notice.
				var notice = document.getElementById('gfsb-accordion-order-notice');
				notice.classList.add('show');
				setTimeout(function() {
					notice.classList.remove('show');
				}, 3000);
			}
		});
	}

	// Initialize tab toggles on document ready.
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', gfsbInitTabToggles);
	} else {
		gfsbInitTabToggles();
	}

})(jQuery);
