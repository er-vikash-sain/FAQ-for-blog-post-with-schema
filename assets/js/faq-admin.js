/* global wp */
(function ($) {
	'use strict';

	$(function () {
		var $wrap = $('#faq-plugin-meta-wrap');

		if (!$wrap.length) {
			return;
		}

		var $rows = $wrap.find('.faq-plugin-rows');
		var template = wp.template('faq-plugin-row');

		function reindexRows() {
			$rows.children('.faq-plugin-row').each(function (index) {
				var $row = $(this);
				$row.attr('data-index', index);
				$row.find('input, textarea').each(function () {
					var $field = $(this);
					var name = $field.attr('name');
					if (!name) {
						return;
					}
					// Replace the index inside faq_plugin_faqs[<index>].
					var newName = name.replace(/faq_plugin_faqs\[[^\]]+\]/, 'faq_plugin_faqs[' + index + ']');
					$field.attr('name', newName);
				});
			});
		}

		function ensureRowExists() {
			if (0 === $rows.children('.faq-plugin-row').length) {
				addRow();
			}
		}

		function addRow() {
			var index = $rows.children('.faq-plugin-row').length;
			$rows.append(template({ index: index }));
			reindexRows();
		}

		$wrap.on('click', '.faq-plugin-add-row', function () {
			addRow();
		});

		$wrap.on('click', '.faq-plugin-remove-row', function () {
			$(this).closest('.faq-plugin-row').remove();
			ensureRowExists();
			reindexRows();
		});

		$wrap.on('click', '.faq-plugin-move-up', function () {
			var $row = $(this).closest('.faq-plugin-row');
			var $prev = $row.prev('.faq-plugin-row');
			if ($prev.length) {
				$row.insertBefore($prev);
				reindexRows();
			}
		});

		$wrap.on('click', '.faq-plugin-move-down', function () {
			var $row = $(this).closest('.faq-plugin-row');
			var $next = $row.next('.faq-plugin-row');
			if ($next.length) {
				$row.insertAfter($next);
				reindexRows();
			}
		});
	});
})(jQuery);
