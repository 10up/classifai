import { useDispatch, useSelect } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { NoticeList } from '@wordpress/components';

export const Notices = ( { feature } ) => {
	const { removeNotice } = useDispatch( noticesStore );
	const notices = useSelect( ( select ) =>
		select( noticesStore ).getNotices()
	);

	const featureNotices = notices.filter(
		( notice ) => notice.id === `error-${ feature }`
	);

	if ( featureNotices.length === 0 ) {
		return null;
	}

	return <NoticeList notices={ featureNotices } onRemove={ removeNotice } />;
};
