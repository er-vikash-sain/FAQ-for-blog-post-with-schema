jQuery(function ($) {
	const $items = $('.faq-accordion details.faq-item, .faq-plugin-accordion details.faq-item');

	if (!$items.length) {
		return;
	}

	$items.on('toggle', function () {
		if (!this.open) {
			return;
		}

		$items.not(this).removeAttr('open');
	});
});
