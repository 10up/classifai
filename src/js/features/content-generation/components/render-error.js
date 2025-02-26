/**
 * Render out an error message.
 *
 * @param {Object} props       The component props.
 * @param {string} props.error The error message to show.
 * @return {React.ReactNode} The rendered component.
 */
export const RenderError = ( { error } ) => {
	if ( ! error ) {
		return null;
	}

	return <div className="error">{ error }</div>;
};
