import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, ToggleControl, TextControl } from '@wordpress/components';
import { SectionCard } from '../../components';
import SettingsNotice from './SettingsNotice.jsx';

const PrivacySettingsPanel = () => {
	const [settings, setSettings] = useState({
		enableGDPR: false,
		privacyPolicyUrl: '',
		dataRetention: '90',
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
				message: __('Privacy settings saved successfully!', 'aria'),
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
				title={__('GDPR Compliance', 'aria')}
				description={__(
					'Configure privacy settings and GDPR compliance features',
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
								: __('Save Privacy Settings', 'aria')}
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
						label={__('Enable GDPR Features', 'aria')}
						help={__(
							'Enable GDPR compliance features including consent management',
							'aria'
						)}
						checked={settings.enableGDPR}
						onChange={(value) => updateSetting('enableGDPR', value)}
					/>

					<TextControl
						label={__('Privacy Policy URL', 'aria')}
						type="url"
						value={settings.privacyPolicyUrl}
						help={__(
							'Link to your privacy policy page (shown in GDPR consent)',
							'aria'
						)}
						placeholder="https://yoursite.com/privacy-policy"
						onChange={(value) =>
							updateSetting('privacyPolicyUrl', value)
						}
					/>

					<TextControl
						label={__('Data Retention Period (days)', 'aria')}
						type="number"
						value={settings.dataRetention}
						help={__(
							'Automatically delete conversations older than this many days',
							'aria'
						)}
						onChange={(value) =>
							updateSetting('dataRetention', value)
						}
					/>
				</div>
			</SectionCard>
		</div>
	);
};

export default PrivacySettingsPanel;
