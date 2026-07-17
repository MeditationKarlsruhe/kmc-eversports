import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	CheckboxControl,
	TextControl,
	Notice,
	Spinner,
	Placeholder,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';

export function Edit({ attributes, setAttributes }) {
	const { showImages, groupIds } = attributes;
	const [groups, setGroups] = useState(null);
	const [error, setError] = useState(false);
	const [searchTerm, setSearchTerm] = useState('');

	useEffect(() => {
		apiFetch({ path: '/kmc-eversports/v1/groups' })
			.then(setGroups)
			.catch(() => setError(true));
	}, []);

	function toggleGroup(groupId, checked) {
		setAttributes({
			groupIds: checked
				? [...groupIds, groupId]
				: groupIds.filter((id) => id !== groupId),
		});
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Einstellungen', 'kmc-eversports')}>
					<ToggleControl
						label={__('Bilder anzeigen', 'kmc-eversports')}
						checked={showImages}
						onChange={(value) =>
							setAttributes({ showImages: value })
						}
					/>

					{groupIds.length === 0 && (
						<Notice status="warning" isDismissible={false}>
							{__(
								'Bitte mindestens eine Gruppe auswählen.',
								'kmc-eversports'
							)}
						</Notice>
					)}

					{error && (
						<Notice status="error" isDismissible={false}>
							{__(
								'Gruppen konnten nicht geladen werden.',
								'kmc-eversports'
							)}
						</Notice>
					)}

					{groups === null && !error && <Spinner />}

					{groups && groups.length > 0 && (
						<TextControl
							label={__('Gruppen filtern', 'kmc-eversports')}
							placeholder={__('Name eingeben …', 'kmc-eversports')}
							value={searchTerm}
							onChange={setSearchTerm}
						/>
					)}

					{groups &&
						groups
							.filter((group) =>
								group.name
									.toLowerCase()
									.includes(searchTerm.toLowerCase())
							)
							.map((group) => (
								<CheckboxControl
									key={group.id}
									label={group.name}
									checked={groupIds.includes(group.id)}
									onChange={(checked) =>
										toggleGroup(group.id, checked)
									}
								/>
							))}
				</PanelBody>
			</InspectorControls>
			<div {...useBlockProps()}>

				<Placeholder label={__('Eversports Events', 'kmc-eversports')}>
					{groupIds.length === 0 ? (

						__(
							'Bitte mindestens eine Gruppe in den Blockeinstellungen auswählen.',
							'kmc-eversports'
						)
					) : (
						<ServerSideRender
							block="kmc/eversports-events"
							attributes={attributes}
						/>
					)}

				</Placeholder>
			</div>
		</>
	);
}
