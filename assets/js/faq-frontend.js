(function () {
	'use strict';

	function toggleItem(button) {
		var expanded = button.getAttribute('aria-expanded') === 'true';
		var targetId = button.getAttribute('aria-controls');
		var panel = targetId ? document.getElementById(targetId) : null;

		button.setAttribute('aria-expanded', expanded ? 'false' : 'true');

		if (panel) {
			panel.hidden = expanded;
		}
	}

	function handleClick(event) {
		if (!event.target.matches('.faq-plugin-toggle')) {
			return;
		}
		toggleItem(event.target);
	}

	document.addEventListener('DOMContentLoaded', function () {
		var accordions = document.querySelectorAll('.faq-plugin-accordion');
		accordions.forEach(function (accordion) {
			accordion.addEventListener('click', handleClick);
		});
	});
})();
