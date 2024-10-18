/**
 * WordPress dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { NoticeList } from '@wordpress/components';

/**
 * Component for displaying notices for a specific feature.
 *
 * @param {Object} props         Component props.
 * @param {string} props.feature Feature name.
 *
 * @return {React.ReactElement} Notices component.
 */
export const Notices = ( { feature } ) => {
	const { removeNotice } = useDispatch( noticesStore );
	const notices = useSelect( ( select ) =>
		select( noticesStore ).getNotices()
	);

	const featureNotices = notices.filter(
		( notice ) =>
			notice.id === `error-${ feature }` ||
			notice.id === `success-${ feature }`
	);

	if ( featureNotices.length === 0 ) {
		return null;
	}

	return <NoticeList notices={ featureNotices } onRemove={ removeNotice } />;
};
