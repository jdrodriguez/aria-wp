import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, TextControl } from '@wordpress/components';
import { SectionCard } from '../../components';
import SettingsNotice from './SettingsNotice.jsx';

const LicenseSettingsPanel = () => {
	const [settings, setSettings] = useState({
		licenseKey: '',
		licenseStatus: 'inactive',
	});
	const [saving, setSaving] = useState(false);
	const [notice, setNotice] = useState(null);

	const updateSetting = (key, value) => {
		setSettings((prev) => ({ ...prev, [key]: value }));
	};

	const handleActivate = async () => {
		setSaving(true);
		try {
			await new Promise((resolve) => setTimeout(resolve, 1500));
			setSettings((prev) => ({ ...prev, licenseStatus: 'active' }));
			setNotice({
				type: 'success',
				message: __('License activated successfully!', 'aria'),
			});
			setTimeout(() => setNotice(null), 5000);
		} catch (error) {
			setNotice({
				type: 'error',
				message: __(
					'Failed to activate license. Please check your license key.',
					'aria'
				),
			});
		} finally {
			setSaving(false);
		}
	};

	const statusKey =
		settings.licenseStatus === 'active' ? 'active' : 'inactive';

	return (
		<div className="aria-settings__tab-content">
			<SectionCard
				title={__('License Information', 'aria')}
				description={__(
					'Enter your license key to unlock all premium features',
					'aria'
				)}
				footer={
					<div className="aria-settings__actions">
						<Button
							variant="primary"
							onClick={handleActivate}
							isBusy={saving}
							disabled={saving || !settings.licenseKey.trim()}
						>
							{saving
								? __('Activating…', 'aria')
								: __('Activate License', 'aria')}
						</Button>
					</div>
				}
			>
				<SettingsNotice
					notice={notice}
					onRemove={() => setNotice(null)}
				/>
				<div className="aria-settings__grid">
					<div>
						<div className="aria-settings__status">
							<span
								className={`aria-settings__status-indicator aria-settings__status-indicator--${statusKey}`}
								aria-hidden="true"
							/>
							<span
								className={`aria-settings__status-label aria-settings__status--${statusKey}`}
							>
								{statusKey === 'active'
									? __('Active', 'aria')
									: __('Inactive', 'aria')}
							</span>
						</div>
						<TextControl
							label={__('License Key', 'aria')}
							value={settings.licenseKey}
							help={__(
								'Enter your license key from your purchase confirmation',
								'aria'
							)}
							placeholder="ARIA-XXXX-XXXX-XXXX-XXXX"
							onChange={(value) =>
								updateSetting('licenseKey', value)
							}
						/>
					</div>
				</div>
			</SectionCard>
		</div>
	);
};

export default LicenseSettingsPanel;
