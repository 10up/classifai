import { __ } from '@wordpress/i18n';

export const tones = {
	emotion: {
		label: __( 'Emotion', 'classifai' ),
		values: [
			{
				key: 'happy',
				label: __( 'Happy', 'classifai' ),
			},
			{
				key: 'neutral',
				label: __( 'Neutral', 'classifai' ),
			},
			{
				key: 'sad',
				label: __( 'Sad', 'classifai' ),
			},
		],
	},
	formality: {
		label: __( 'Formality', 'classifai' ),
		values: [
			{
				key: 'formal',
				label: __( 'Formal', 'classifai' ),
				description: __( 'Professional, structured, business-like.', 'classifai' ),
			},
			{
				key: 'informal',
				label: __( 'Informal', 'classifai' ),
				description: __( 'Conversational, relaxed.', 'classifai' ),
			},
			{
				key: 'supportive',
				label: __( 'Supportive', 'classifai' ),
				description: __( 'Reassuring, helpful.', 'classifai' ),
			},
		],
	},
	intent: {
		label: __( 'Intent', 'classifai' ),
		value: [
			{
				key: 'dramatic',
				label: __( 'Dramatic', 'classifai' ),
				description: __( 'Intense, theatrical.', 'classifai' ),
			},
			{
				key: 'persuasive',
				label: __( 'Persuasive', 'classifai' ),
				description: __( 'Convincing, compelling.', 'classifai' ),
			},
			{
				key: 'storytelling',
				label: __( 'Storytelling', 'classifai' ),
				description: __( 'Engaging, immersive.', 'classifai' ),
			},
		],
	},
	audience: {
		label: __( 'Audience', 'classifai' ),
		value: [
			{
				key: 'educational',
				label: __( 'Educational', 'classifai' ),
				description: __( 'Clear, instructive.', 'classifai' ),
			},
			{
				key: 'general',
				label: __( 'General Audience', 'classifai' ),
				description: __( 'Balanced, universally understandable.', 'classifai' ),
			},
			{
				key: 'maketing',
				label: __( 'Marketing & Sales', 'classifai' ),
				description: __( 'Promotional, action-driven.', 'classifai' ),
			},
			{
				key: 'professional',
				label: __( 'Business & Professional', 'classifai' ),
				description: __( 'Corporate, industry-focused.', 'classifai' ),
			},
		],
	}
};