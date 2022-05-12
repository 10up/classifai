/* global classifai_personalizer_params */
const contentLinks = document.querySelectorAll('.classifai-send-reward');
contentLinks.forEach(function (contentLink) {
	contentLink.addEventListener('click', function (event) {
		event.preventDefault();
		const restURL = classifai_personalizer_params?.reward_endpoint;
		const eventId = this.getAttribute('data-eventid');
		/* Send Reward to personalizer */
		fetch(restURL.replace('{eventId}', eventId)).catch((err) => {
			// eslint-disable-next-line no-console
			console.log(err);
		});
		window.location = this.href;
	});
});
