/**
 * External dependencies
 */
import { NavLink, Navigate, Outlet, useParams } from 'react-router-dom';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { STORE_NAME } from '../../data/store';
const { features, services } = window.classifAISettings;

/**
 * ServiceSettings component to render the feature navigation tabs and the feature settings.
 *
 * @return {Object} The ServiceSettings component.
 */
export const ServiceSettings = () => {
	const { setCurrentService } = useDispatch( STORE_NAME );
	const { service } = useParams();
	useEffect( () => {
		if ( service && services[ service ] ) {
			setCurrentService( service );
		}
	}, [ service, setCurrentService ] );

	// If the service is not available, redirect to the language processing page.
	if ( ! services[ service ] ) {
		return <Navigate to="/language_processing" replace />;
	}

	const serviceFeatures = features[ service ] || {};
	return (
		<div className="service-settings-wrapper">
			<div className="classifai-tabs" aria-orientation="vertical">
				{ Object.keys( serviceFeatures ).map( ( feature ) => (
					<NavLink
						to={ feature }
						key={ feature }
						className={ ( { isActive } ) =>
							isActive
								? 'active-tab classifai-tabs-item'
								: 'classifai-tabs-item'
						}
					>
						{ serviceFeatures[ feature ]?.label ||
							__( 'Feature', 'classifai' ) }
					</NavLink>
				) ) }
			</div>
			<div className="feature-settings-wrapper">
				<Outlet />
			</div>
		</div>
	);
};
