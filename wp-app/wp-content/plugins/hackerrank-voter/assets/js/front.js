jQuery(function ($) {
	$(document).on(
		'click',
		'.hrv-container:not(.hrv-container-working) .hrv-btn:not(.hrv-btn-disabled)',
		function (e) {
			e.preventDefault();

			const $this = $(this);
			const $container = $(this).closest('.hrv-container');

			$container.addClass('hrv-container-working');

			const data = {
				id: $container.data('id'),
				nonce: $container.data('nonce'),
				type: $this.data('type'),
				action: 'hacker_rank_voter_vote',
			};

			$.ajax({
				url: HackerRankVoter.ajaxurl,
				method: 'POST',
				data: data,
				dataType: 'json',
				success: function handleSuccessResponse(response) {
					if (response.status === 'OK') {
						$container.replaceWith(response.html);
					} else {
						$('.hrv-error', $container).html(response.message).show();
						$container.removeClass('hrv-container-working');
					}
				},
				error: function handleErrorResponse() {
					$('.hrv-error', $container).html(HackerRankVoter.errors.generic).show();
					$container.removeClass('hrv-container-working');
				},
			});
		}
	);
});
