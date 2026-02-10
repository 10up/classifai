import { __ } from '@wordpress/i18n';

export const tones = {
	emotion: {
		label: __( 'Emotion', 'classifai' ),
		value: [
			{
				value: 'happy',
				label: __( 'Happy', 'classifai' ),
			},
			{
				value: 'neutral',
				label: __( 'Neutral', 'classifai' ),
			},
			{
				value: 'sad',
				label: __( 'Sad', 'classifai' ),
			},
		],
	},
	formality: {
		label: __( 'Formality', 'classifai' ),
		value: [
			{
				value: 'formal',
				label: __( 'Formal', 'classifai' ),
				description: __(
					'Professional, structured, business-like.',
					'classifai'
				),
			},
			{
				value: 'informal',
				label: __( 'Informal', 'classifai' ),
				description: __( 'Conversational, relaxed.', 'classifai' ),
			},
			{
				value: 'supportive',
				label: __( 'Supportive', 'classifai' ),
				description: __( 'Reassuring, helpful.', 'classifai' ),
			},
		],
	},
	intent: {
		label: __( 'Intent', 'classifai' ),
		value: [
			{
				value: 'dramatic',
				label: __( 'Dramatic', 'classifai' ),
				description: __( 'Intense, theatrical.', 'classifai' ),
			},
			{
				value: 'persuasive',
				label: __( 'Persuasive', 'classifai' ),
				description: __( 'Convincing, compelling.', 'classifai' ),
			},
			{
				value: 'storytelling',
				label: __( 'Storytelling', 'classifai' ),
				description: __( 'Engaging, immersive.', 'classifai' ),
			},
		],
	},
	audience: {
		label: __( 'Audience', 'classifai' ),
		value: [
			{
				value: 'educational',
				label: __( 'Educational', 'classifai' ),
				description: __( 'Clear, instructive.', 'classifai' ),
			},
			{
				value: 'general',
				label: __( 'General', 'classifai' ),
				description: __(
					'Balanced, universally understandable.',
					'classifai'
				),
			},
			{
				value: 'maketing',
				label: __( 'Marketing', 'classifai' ),
				description: __( 'Promotional, action-driven.', 'classifai' ),
			},
			{
				value: 'professional',
				label: __( 'Professional', 'classifai' ),
				description: __( 'Corporate, industry-focused.', 'classifai' ),
			},
		],
	},
};
