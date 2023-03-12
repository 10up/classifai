import { createReduxStore, register } from '@wordpress/data';

const DEFAULT_STATE = {
	audioId: 0,
	isProcessing: false,
};

export const store = createReduxStore( 'classifai-post-audio', {
	reducer( state = DEFAULT_STATE, action ) {
		switch( action.type ) {
			case 'SET_AUDIO_ID':
				return {
					...state,
					audioId: action.id,
				}
			case 'SET_PROCESSING_STATUS':
				return {
					...state,
					isProcessing: action.status,
				}
		}

		return state;
	},
	actions: {
		setAudioId( id ) {
			return {
				type: 'SET_AUDIO_ID',
				id,
			}
		},
		setIsProcessing( status ) {
			return {
				type: 'SET_PROCESSING_STATUS',
				status,
			}
		}
	},
	selectors: {
		getAudioId( state ) {
			return state.audioId;
		},
		getIsProcessing( state ) {
			return state.isProcessing;
		}
	}
} );

register( store );
