import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { PageHeader, PageShell } from '../components';
import {
	DesignNotice,
	DesignWidgetSection,
	DesignColorsSection,
	DesignBrandingSection,
	DesignPreviewSection,
	DesignActions,
} from './design-sections';
import {
	WIDGET_POSITIONS,
	WIDGET_SIZES,
	WIDGET_THEMES,
} from '../utils/constants';
import { fetchDesignSettings, saveDesignSettings } from '../utils/api';
import { Spinner } from '@wordpress/components';

const defaultDesignSettings = {
	position: 'bottom-right',
	size: 'medium',
	theme: 'light',
	primaryColor: '#2271b1',
	backgroundColor: '#ffffff',
	textColor: '#1e1e1e',
	title: 'Chat with us',
	welcomeMessage: 'Hi! How can I help you today?',
};

const mergeDesignSettings = (incoming = {}) => ({
	...defaultDesignSettings,
	...incoming,
});

const Design = () => {
	const [settings, setSettings] = useState(defaultDesignSettings);
	const [saving, setSaving] = useState(false);
	const [notice, setNotice] = useState(null);
	const [initialLoading, setInitialLoading] = useState(true);

	const updateSetting = (key, value) => {
		setSettings((prev) => ({ ...prev, [key]: value }));
	};

	const handleSave = async () => {
		setSaving(true);
		setNotice({ type: 'info', message: __('Saving design settings…', 'aria') });

		try {
			const data = await saveDesignSettings(settings);
			if (data?.settings) {
				setSettings(mergeDesignSettings(data.settings));
			}
			setNotice({
				type: 'success',
				message:
					data?.message || __('Design settings saved successfully!', 'aria'),
			});
			setTimeout(() => setNotice(null), 5000);
		} catch (error) {
			setNotice({
				type: 'error',
				message:
					error?.message || __('Failed to save settings. Please try again.', 'aria'),
			});
		} finally {
			setSaving(false);
		}
	};

	const uploadIcon = () => {
		// TODO: Implement file upload
		// eslint-disable-next-line no-console
		console.log('Upload icon functionality to be implemented');
	};

	const uploadAvatar = () => {
		// TODO: Implement file upload
		// eslint-disable-next-line no-console
		console.log('Upload avatar functionality to be implemented');
	};

	useEffect(() => {
		const loadDesignSettings = async () => {
			setInitialLoading(true);
			try {
				const data = await fetchDesignSettings();
				if (data?.settings) {
					setSettings(mergeDesignSettings(data.settings));
				}
			} catch (error) {
				setNotice({
					type: 'error',
					message:
						error?.message || __('Failed to load design settings.', 'aria'),
				});
				setTimeout(() => setNotice(null), 7000);
			} finally {
				setInitialLoading(false);
			}
		};

		loadDesignSettings();
	}, []);

	if (initialLoading) {
		return (
			<PageShell className="aria-design aria-design-react" width="wide">
				<PageHeader
					title={__('Design', 'aria')}
					description={__(
						'Customize the appearance and behavior of your chat widget.',
						'aria'
					)}
				/>
				<div className="aria-design__loading">
					<Spinner />
					<span>{__('Loading design settings…', 'aria')}</span>
				</div>
			</PageShell>
		);
	}

	return (
		<PageShell className="aria-design aria-design-react" width="wide">
			<PageHeader
				title={__('Design', 'aria')}
				description={__(
					'Customize the appearance and behavior of your chat widget.',
					'aria'
				)}
			/>

			<DesignNotice notice={notice} onRemove={() => setNotice(null)} />

			<div className="aria-stack-lg">
				<DesignWidgetSection
					position={settings.position}
					size={settings.size}
					theme={settings.theme}
					onChange={updateSetting}
					positionOptions={WIDGET_POSITIONS}
					sizeOptions={WIDGET_SIZES}
					themeOptions={WIDGET_THEMES}
				/>

				<DesignColorsSection settings={settings} onChange={updateSetting} />

				<DesignBrandingSection
					settings={settings}
					onChange={updateSetting}
					onUploadIcon={uploadIcon}
					onUploadAvatar={uploadAvatar}
				/>

				<DesignPreviewSection
					title={settings.title}
					welcomeMessage={settings.welcomeMessage}
					colors={{
						primaryColor: settings.primaryColor,
						backgroundColor: settings.backgroundColor,
						textColor: settings.textColor,
					}}
				/>

				<DesignActions onSave={handleSave} isSaving={saving} />
			</div>
		</PageShell>
	);
};

export default Design;
