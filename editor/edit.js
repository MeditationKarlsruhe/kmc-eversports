import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export function Edit( { attributes, setAttributes } ) {
	const { showImages } = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Einstellungen', 'kmc-eversports' ) }>
					<ToggleControl
						label={ __( 'Bilder anzeigen', 'kmc-eversports' ) }
						checked={ showImages }
						onChange={ ( value ) =>
							setAttributes( { showImages: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps() }>
				<Placeholder label={ __( 'Eversports Events', 'kmc-eversports' ) }>
					{ __(
						'Die Kursübersicht wird im Frontend gerendert.',
						'kmc-eversports'
					) }
				</Placeholder>
			</div>
		</>
	);
}
