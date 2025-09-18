import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Button,
	ToggleControl,
	SelectControl,
	TextControl,
} from '@wordpress/components';
import { SectionCard } from '../../components';
import { DISPLAY_OPTIONS } from '../../utils/constants';
import SettingsNotice from './SettingsNotice.jsx';

const GeneralSettingsPanel = () => {
	const [settings, setSettings] = useState({
		enableChat: true,
		displayOn: 'all',
		autoOpenDelay: '0',
		requireEmail: false,
	});
	const [saving, setSaving] = useState(false);
	const [notice, setNotice] = useState(null);

	const updateSetting = (key, value) => {
		setSettings((prev) => ({ ...prev, [key]: value }));
	};

	const handleSave = async () => {
		setSaving(true);
		try {
			await new Promise((resolve) => setTimeout(resolve, 1000));
			setNotice({
				type: 'success',
				message: __('General settings saved successfully!', 'aria'),
			});
			setTimeout(() => setNotice(null), 5000);
		} catch (error) {
			setNotice({
				type: 'error',
				message: __(
					'Failed to save settings. Please try again.',
					'aria'
				),
			});
		} finally {
			setSaving(false);
		}
	};

	return (
		<div className="aria-settings__tab-content">
			<SectionCard
				title={__('Chat Widget Settings', 'aria')}
				description={__(
					'Configure basic settings for your chat widget',
					'aria'
				)}
				footer={
					<div className="aria-settings__actions">
						<Button
							variant="primary"
							onClick={handleSave}
							isBusy={saving}
							disabled={saving}
						>
							{saving
								? __('Saving…', 'aria')
								: __('Save General Settings', 'aria')}
						</Button>
					</div>
				}
			>
				<SettingsNotice
					notice={notice}
					onRemove={() => setNotice(null)}
				/>
				<div className="aria-settings__grid">
					<ToggleControl
						label={__('Enable Chat Widget', 'aria')}
						help={__(
							'Enable Aria chat widget on your website',
							'aria'
						)}
						checked={settings.enableChat}
						onChange={(value) => updateSetting('enableChat', value)}
					/>

					<SelectControl
						label={__('Display On', 'aria')}
						value={settings.displayOn}
						options={DISPLAY_OPTIONS}
						help={__(
							'Choose where the chat widget should appear',
							'aria'
						)}
						onChange={(value) => updateSetting('displayOn', value)}
					/>

					<TextControl
						label={__('Auto-open Delay (seconds)', 'aria')}
						type="number"
						value={settings.autoOpenDelay}
						help={__(
							'Automatically open chat after this delay (0 to disable)',
							'aria'
						)}
						onChange={(value) =>
							updateSetting('autoOpenDelay', value)
						}
					/>

					<ToggleControl
						label={__('Require Email Before Chat', 'aria')}
						help={__(
							'Require visitors to provide email before chatting',
							'aria'
						)}
						checked={settings.requireEmail}
						onChange={(value) =>
							updateSetting('requireEmail', value)
						}
					/>
				</div>
			</SectionCard>
		</div>
	);
};

export default GeneralSettingsPanel;
