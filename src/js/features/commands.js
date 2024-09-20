import { useCommandLoader } from '@wordpress/commands';
import { edit, image, settings } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';

const Commands = () => {
	const getCommandLoader = () => {
		const commands = [];
		const excerptButton = document.querySelector(
			'.editor-post-excerpt button.classifai-post-excerpt'
		);
		const titleButton = document.querySelector(
			'.classifai-post-status button.title'
		);

		// Command to open the ClassifAI settings page.
		commands.push( {
			name: 'classifai/settings',
			label: __( 'ClassifAI settings', 'classifai' ),
			icon: settings,
			callback: () => {
				document.location.href = 'tools.php?page=classifai';
			},
		} );

		// Command to generate an excerpt.
		if ( excerptButton ) {
			commands.push( {
				name: 'classifai/generate-excerpt',
				label: __( 'ClassifAI: Generate excerpt', 'classifai' ),
				icon: edit,
				callback: ( { close } ) => {
					close();

					excerptButton.scrollIntoView( {
						block: 'center',
					} );
					excerptButton.click();
				},
			} );
		}

		// Command to generate titles.
		if ( titleButton ) {
			commands.push( {
				name: 'classifai/generate-titles',
				label: __( 'ClassifAI: Generate titles', 'classifai' ),
				icon: edit,
				callback: ( { close } ) => {
					close();

					titleButton.scrollIntoView( {
						block: 'center',
					} );
					titleButton.click();
				},
			} );
		}

		// Command to go to the image generation page.
		if ( typeof classifaiDalleData !== 'undefined' ) {
			commands.push( {
				name: 'classifai/generate-image',
				label: __( 'ClassifAI: Generate image', 'classifai' ),
				icon: image,
				callback: () => {
					document.location.href =
						'upload.php?action=classifai-generate-image';
				},
			} );
		}

		return { commands };
	};

	useCommandLoader( {
		name: 'classifai',
		hook: getCommandLoader,
	} );

	return null;
};

if ( 'function' === typeof useCommandLoader ) {
	registerPlugin( 'classifai-commands', { render: Commands } );
}
