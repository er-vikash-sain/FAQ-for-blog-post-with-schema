(function () {
	'use strict';

	function isExpanded(button) {
		return button.getAttribute('aria-expanded') === 'true';
	}

	function setExpanded(button, expanded) {
		var targetId = button.getAttribute('aria-controls');
		var panel = targetId ? document.getElementById(targetId) : null;

		button.setAttribute('aria-expanded', expanded ? 'true' : 'false');

		if (panel) {
			panel.hidden = !expanded;
		}
	}

	function handleToggle(button) {
		var accordion = button.closest('.faq-plugin-accordion');
		if (!accordion) {
			return;
		}

		// If already open, keep it open to maintain single-open behavior.
		if (isExpanded(button)) {
			return;
		}

		var toggles = accordion.querySelectorAll('.faq-plugin-toggle');

		toggles.forEach(function (toggle) {
			setExpanded(toggle, toggle === button);
		});
	}

	function handleClick(event) {
		var button = event.target.closest('.faq-plugin-toggle');
		if (!button) {
			return;
		}

		handleToggle(button);
	}

	document.addEventListener('DOMContentLoaded', function () {
		var accordions = document.querySelectorAll('.faq-plugin-accordion');

		accordions.forEach(function (accordion) {
			var toggles = accordion.querySelectorAll('.faq-plugin-toggle');

			toggles.forEach(function (toggle, index) {
				setExpanded(toggle, index === 0);
			});

			accordion.addEventListener('click', handleClick);
		});
	});
})();
