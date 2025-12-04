/**
 * Gravity Forms Shortcode Builder - Notification Modal Scripts
 *
 * @package GF_Shortcode_Builder
 * @since 1.2.0
 */

/* global jQuery, ajaxurl, tinymce, tinyMCE, gfsbModal */

(function($) {
	'use strict';

	// Global variables for editor detection.
	window.gfsbNotificationEditorId = window.gfsbNotificationEditorId || '_gform_setting_message';
	window.gfsbNotificationTextarea = window.gfsbNotificationTextarea || null;

	/**
	 * Get the notification textarea element.
	 *
	 * @return {jQuery|null} The textarea jQuery object or null.
	 */
	window.gfsbGetNotificationTextarea = function() {
		var selectors = [
			'.wp-_gform_setting_message-editor-container .wp-editor-area',
			'.wp-_gform_setting_message-editor-container textarea',
			'textarea[id^="gform_notification_"][id$="_message"]',
			'#gform_notification_message',
			'#_gform_setting_message'
		];

		if (window.gfsbNotificationTextarea) {
			var cached = $(window.gfsbNotificationTextarea);
			if (cached.length) {
				return cached;
			}
			window.gfsbNotificationTextarea = null;
		}

		for (var i = 0; i < selectors.length; i++) {
			var el = $(selectors[i]).first();
			if (el.length) {
				window.gfsbNotificationTextarea = el.get(0);
				if (el.attr('id')) {
					window.gfsbNotificationEditorId = el.attr('id');
					console.log('GFSB: detected editor ID', window.gfsbNotificationEditorId, 'via selector', selectors[i]);
				} else {
					console.log('GFSB: detected editor textarea without ID via selector', selectors[i]);
				}
				return el;
			}
		}
		return null;
	};

	/**
	 * Resolve the notification editor ID.
	 *
	 * @return {string} The editor ID.
	 */
	window.gfsbResolveNotificationEditorId = function() {
		var textarea = window.gfsbGetNotificationTextarea ? window.gfsbGetNotificationTextarea() : null;
		if (textarea && textarea.length && textarea.attr('id')) {
			window.gfsbNotificationEditorId = textarea.attr('id');
			return window.gfsbNotificationEditorId;
		}
		return window.gfsbNotificationEditorId;
	};

	$(document).ready(function() {
		var attempts = 0;
		var interval = setInterval(function() {
			attempts++;
			var resolvedId = window.gfsbResolveNotificationEditorId();
			if (resolvedId || attempts >= 10) {
				clearInterval(interval);
			}
		}, 500);

		window.gfsbResolveNotificationEditorId();

		var dropdownVisible = false;
		var currentTabId = null;

		/**
		 * Ensure the builder button exists.
		 *
		 * @return {jQuery} The button element.
		 */
		function gfsbEnsureBuilderButton() {
			var $button = $('#gfsb-notification-toggle');
			if ($button.length) {
				return $button;
			}
			$button = $('<button/>', {
				type: 'button',
				id: 'gfsb-notification-toggle',
				class: 'button gfsb-notification-toggle',
				css: { display: 'none' }
			}).html('<span style="font-size: 16px; line-height: 1; display: inline-block; margin-right: 4px;">' + gfsbModal.buttonIcon + '</span> ' + gfsbModal.buttonLabel);
			$('body').append($button);
			return $button;
		}

		/**
		 * Move the button next to media buttons.
		 */
		function gfsbMoveButtonNextToMedia() {
			var $button = gfsbEnsureBuilderButton();
			var selectors = [];
			if (window.gfsbNotificationEditorId) {
				selectors.push('#wp-' + window.gfsbNotificationEditorId + '-wrap .wp-media-buttons');
			}
			selectors.push('#wp-_gform_setting_message-wrap .wp-media-buttons');
			selectors.push('.wp-media-buttons');

			var $mediaButtons = null;
			for (var i = 0; i < selectors.length; i++) {
				var candidate = $(selectors[i]).first();
				if (candidate.length) {
					$mediaButtons = candidate;
					break;
				}
			}

			if (!$mediaButtons || !$mediaButtons.length) {
				return;
			}

			if ($mediaButtons.find('#gfsb-notification-toggle').length) {
				$button.show();
				return;
			}

			var $insertMedia = $mediaButtons.find('.insert-media').first();
			$button.css({ marginBottom: '0', marginLeft: '6px', display: '' });
			$button.detach();

			if ($insertMedia.length) {
				$button.insertAfter($insertMedia);
			} else {
				$mediaButtons.append($button);
			}
		}

		gfsbMoveButtonNextToMedia();
		setTimeout(gfsbMoveButtonNextToMedia, 500);
		setTimeout(gfsbMoveButtonNextToMedia, 1500);

		/**
		 * Apply modal layout classes.
		 */
		function gfsbApplyModalLayout() {
			var $content = $('#gfsb-modal-tab-content');
			$content.addClass('gfsb-modal-fullwidth');
			var $previewField = $content.find('#gf_csb_result').closest('.gform-settings-field');
			if ($previewField.length) {
				$previewField.addClass('gfsb-modal-preview-field');
			}
		}

		/**
		 * Setup the insert button in the modal.
		 *
		 * @return {boolean} Whether a button was found and handled.
		 */
		function gfsbSetupInsertButton() {
			var handled = false;
			$('#gfsb-modal-tab-content').find('button').each(function() {
				var $btn = $(this);
				var btnText = $.trim($btn.text());
				var matchesCopy = gfsbModal.copyButtonTexts.some(function(text) {
					return text && btnText.indexOf(text) > -1;
				});
				if (matchesCopy || btnText === gfsbModal.insertButtonLabel) {
					$btn.text(gfsbModal.insertButtonLabel).attr('onclick', '').off('click').on('click', function(e) {
						e.preventDefault();
						insertShortcodeIntoEditor(currentTabId);
					});
					handled = true;
				}
			});
			return handled;
		}

		// Create dropdown.
		$('body').append('<div class="gfsb-notification-dropdown" id="gfsb-dropdown"></div>');

		// Toggle dropdown.
		$(document).on('click', '#gfsb-notification-toggle', function(e) {
			e.preventDefault();
			e.stopPropagation();

			var dropdown = $('#gfsb-dropdown');
			var button = $(this);

			if (dropdownVisible) {
				dropdown.removeClass('show');
				dropdownVisible = false;
			} else {
				// Position dropdown.
				var offset = button.offset();
				dropdown.css({
					top: offset.top + button.outerHeight(),
					left: offset.left
				});

				// Populate dropdown.
				dropdown.empty();

				var tabsList = gfsbModal.tabsList || [];
				window.gfsbTabsListLookup = window.gfsbTabsListLookup || {};
				tabsList.forEach(function(tab) {
					window.gfsbTabsListLookup[tab.id] = tab;
				});

				tabsList.forEach(function(tab) {
					dropdown.append('<div class="gfsb-notification-dropdown-item" data-tab-id="' + tab.id + '">' + tab.title + '</div>');
				});

				dropdown.addClass('show');
				dropdownVisible = true;
			}
		});

		// Close dropdown when clicking outside.
		$(document).on('click', function(e) {
			if (!$(e.target).closest('#gfsb-notification-toggle, #gfsb-dropdown').length) {
				$('#gfsb-dropdown').removeClass('show');
				dropdownVisible = false;
			}
		});

		// Handle dropdown item click.
		$(document).on('click', '.gfsb-notification-dropdown-item', function() {
			var tabId = $(this).data('tab-id');
			currentTabId = tabId;

			// Hide dropdown.
			$('#gfsb-dropdown').removeClass('show');
			dropdownVisible = false;

			// Show modal with tab content.
			showTabModal(tabId);
		});

		/**
		 * Show the tab modal.
		 *
		 * @param {string} tabId The tab identifier.
		 */
		function showTabModal(tabId) {
			// Show modal immediately with loading state.
			$('#gfsb-notification-modal').show();
			$('#gfsb-notification-overlay').show();
			$('#gfsb-notification-modal-content').show();

			var baseTitle = gfsbModal.baseTitle;
			var dropdown = window.gfsbTabsListLookup || {};
			var selected = dropdown[tabId];
			var modalTitle = baseTitle;
			if (selected && selected.title) {
				modalTitle = baseTitle + ' : ' + selected.title;
			}
			$('#gfsb-notification-modal-content h2').text(modalTitle);
			$('#gfsb-modal-tab-content').html('<p style="margin:0;">' + gfsbModal.loadingText + '</p>');

			// Load tab content via AJAX.
			var requestData = {
				action: 'gfsb_get_tab_content',
				tab_id: tabId,
				form_id: gfsbModal.formId,
				nonce: gfsbModal.nonce
			};

			console.log('GFSB: loading tab', tabId, requestData);

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: requestData,
				success: function(response) {
					console.log('GFSB: tab response', response);
					if (response && response.success) {
						$('#gfsb-modal-tab-content').html(response.data.content);
						gfsbApplyModalLayout();
						setTimeout(function() {
							gfsbSetupInsertButton();
						}, 100);
					} else {
						var errorMsg = (response && response.data && response.data.message) ? response.data.message : gfsbModal.errorTabLoad;
						console.error('GFSB: tab response indicated failure', response);
						alert(errorMsg);
					}
				},
				error: function(xhr, status, error) {
					console.error('GFSB: tab request failed', status, error, xhr.responseText);
					alert(gfsbModal.errorRequest);
				}
			});
		}

		/**
		 * Insert shortcode into the notification editor.
		 *
		 * @param {string} tabId The tab identifier (unused but passed for context).
		 */
		function insertShortcodeIntoEditor(tabId) {
			// Get the generated shortcode from the result textarea.
			var shortcode = $('#gfsb-modal-tab-content').find('textarea[readonly]').first().val();

			if (!shortcode) {
				alert(gfsbModal.errorNoShortcode);
				return;
			}

			var $textarea = window.gfsbGetNotificationTextarea ? window.gfsbGetNotificationTextarea() : null;
			if (!$textarea || !$textarea.length) {
				console.error('GFSB: Notification textarea not found.');
				alert(gfsbModal.errorInsert);
				return;
			}

			var editorId = $textarea.attr('id') || '_gform_setting_message';
			console.log('GFSB: inserting shortcode into editor', editorId);
			var inserted = false;

			// Try TinyMCE (Visual tab).
			if (typeof tinymce !== 'undefined' || typeof tinyMCE !== 'undefined') {
				var candidateEditor = null;
				if (editorId) {
					candidateEditor = (typeof tinymce !== 'undefined' ? tinymce.get(editorId) : null) || (typeof tinyMCE !== 'undefined' ? tinyMCE.get(editorId) : null);
				}
				if (!candidateEditor && typeof tinymce !== 'undefined' && tinymce.editors) {
					for (var i = 0; i < tinymce.editors.length; i++) {
						var ed = tinymce.editors[i];
						if ($textarea && ed && ed.getElement && $textarea.length && ed.getElement() === $textarea.get(0)) {
							candidateEditor = ed;
							break;
						}
					}
				}
				if (!candidateEditor && typeof tinymce !== 'undefined' && tinymce.activeEditor) {
					var active = tinymce.activeEditor;
					if ($textarea && active.getElement && active.getElement() === $textarea.get(0)) {
						candidateEditor = active;
					}
				}
				if (!candidateEditor && typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor) {
					var activeAlt = tinyMCE.activeEditor;
					if ($textarea && activeAlt.getElement && activeAlt.getElement() === $textarea.get(0)) {
						candidateEditor = activeAlt;
					}
				}
				if (candidateEditor && !candidateEditor.isHidden()) {
					candidateEditor.focus();
					candidateEditor.execCommand('mceInsertContent', false, shortcode);
					if (candidateEditor.fire) {
						candidateEditor.fire('change');
					}
					inserted = true;
				}
			}

			// Fallback to textarea (Text tab).
			if (!inserted) {
				var textareaEl = $textarea.get(0);
				var value = textareaEl.value;
				if (typeof textareaEl.selectionStart === 'number') {
					var start = textareaEl.selectionStart;
					var end = textareaEl.selectionEnd;
					textareaEl.value = value.substring(0, start) + shortcode + value.substring(end);
					textareaEl.selectionStart = textareaEl.selectionEnd = start + shortcode.length;
				} else {
					textareaEl.value = value + shortcode;
				}
				$(textareaEl).trigger('change');
				inserted = true;
			}

			if (inserted) {
				closeModal();
			} else {
				console.error('GFSB: Unable to locate notification editor. Last known ID', editorId, 'textarea', $textarea);
				alert(gfsbModal.errorInsert);
			}
		}

		/**
		 * Close the modal.
		 */
		function closeModal() {
			$('#gfsb-notification-modal-content').fadeOut(200);
			$('#gfsb-notification-overlay').fadeOut(200);
			currentTabId = null;
		}

		// Close modal on close button click.
		$(document).on('click', '#gfsb-close-modal', function() {
			closeModal();
		});

		// Close modal on overlay click.
		$(document).on('click', '#gfsb-notification-overlay', function() {
			closeModal();
		});

		// Close modal on escape key.
		$(document).on('keydown', function(e) {
			if (e.key === 'Escape' && $('#gfsb-notification-modal-content').is(':visible')) {
				closeModal();
			}
		});
	});

})(jQuery);
