import './frontend.scss';

/* global classifai_personalizer_params */
jQuery(document).ready(function () {
	jQuery(document).on('click', '.classifai-send-reward', function (event) {
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

function classifaiSessionGet(key) {
	const cacheString = window.sessionStorage.getItem(key);
	if (cacheString !== null) {
		const cacheData = JSON.parse(cacheString);
		if (new Date(cacheData.expiresAt) > new Date()) {
			return cacheData.value;
		}
	}
	return null;
}

function classifaiSessionSet(key, value, expirationInMin) {
	window.sessionStorage.setItem(
		key,
		JSON.stringify({
			expiresAt: new Date(new Date().getTime() + 60000 * expirationInMin),
			value,
		}),
	);
}

jQuery(document).ready(function () {
	jQuery('.classifai-recommended-block-wrap').each(function () {
		const blockId = jQuery(this).attr('id');
		const cached = classifaiSessionGet(blockId);
		if (cached !== null) {
			jQuery(`#${blockId}`).html(cached);
			return;
		}

		const attrKey = jQuery(this).data('attr_key');
		const ajaxURL = classifai_personalizer_params?.ajax_url;
		const data = JSON.parse(jQuery(`#attributes-${attrKey}`).html());
		data.action = 'render_recommended_content';
		data.security = classifai_personalizer_params?.ajax_nonce;
		jQuery
			.post(ajaxURL, data)
			.done(function (response) {
				if (response) {
					classifaiSessionSet(blockId, response, 60); // 1 hour expiry time.
				}
				jQuery(`#${blockId}`).html(response);
			})
			.fail(function (error) {
				jQuery(`#${blockId}`).html('');
				// eslint-disable-next-line no-console
				console.error(error);
			});
	});
});
