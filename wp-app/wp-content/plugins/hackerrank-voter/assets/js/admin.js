jQuery(function ($) {
	/* When success notice is dismissed, update url to remove ?status=success from it. */
	$(document).on('click', '.notice-hacker-rank-voter-success .notice-dismiss', function () {
		history.replaceState(null, '', window.location.href.replace(/(\?|\&)status=success/, ''));
	});
	/* Trigger visibility of embed type description on radio button selection */
	$(document).on('input', "#hacker-rank-settings-form input[name='embed_type']", function () {
		$(this).closest('li').addClass('active').siblings('.active').removeClass('active');
	});
});
