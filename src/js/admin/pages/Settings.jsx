import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Card,
	CardHeader,
	CardBody,
	Button,
	TextControl,
	SelectControl,
	ToggleControl,
	TabPanel,
	Notice,
} from '@wordpress/components';
import PropTypes from 'prop-types';
import { PageHeader } from '../components';
import { DISPLAY_OPTIONS } from '../utils/constants';

const SettingsTabContent = ({ tabName }) => {
	switch (tabName) {
		case 'general':
			return <GeneralSettings />;
		case 'notifications':
			return <NotificationSettings />;
		case 'advanced':
			return <AdvancedSettings />;
		case 'privacy':
			return <PrivacySettings />;
		case 'license':
			return <LicenseSettings />;
		default:
			return <GeneralSettings />;
	}
};

SettingsTabContent.propTypes = {
	tabName: PropTypes.string.isRequired,
};

const GeneralSettings = () => {
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
			// Simulate API call
			await new Promise((resolve) => setTimeout(resolve, 1000));
			setNotice({
				type: 'success',
				message: __('General settings saved successfully!', 'aria'),
			});
			setTimeout(() => setNotice(null), 5000);
		} catch (error) {
			setNotice({
				type: 'error',
				message: __('Failed to save settings. Please try again.', 'aria'),
			});
		} finally {
			setSaving(false);
		}
	};

	return (
		<div style={{ paddingRight: '32px' }}>
			{notice && (
				<div style={{ marginBottom: '24px' }}>
					<Notice
						status={notice.type}
						isDismissible={true}
						onRemove={() => setNotice(null)}
					>
						{notice.message}
					</Notice>
				</div>
			)}

			<Card size="large" style={{ padding: '24px', marginBottom: '24px' }}>
				<CardHeader style={{ paddingBottom: '16px' }}>
					<h3
						style={{
							fontSize: '18px',
							fontWeight: '600',
							marginBottom: '8px',
							margin: 0,
						}}
					>
						{__('Chat Widget Settings', 'aria')}
					</h3>
					<p style={{ fontSize: '14px', color: '#757575', margin: 0 }}>
						{__(
							'Configure basic settings for your chat widget',
							'aria'
						)}
					</p>
				</CardHeader>
				<CardBody style={{ paddingTop: '24px' }}>
					<div
						style={{
							display: 'grid',
							gap: '20px',
						}}
					>
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
				</CardBody>
			</Card>

			<Card>
				<CardBody>
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
				</CardBody>
			</Card>
		</div>
	);
};

const NotificationSettings = () => {
	const [settings, setSettings] = useState({
		enableNotifications: false,
		additionalRecipients: '',
		newConversation: true,
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
				message: __('Notification settings saved successfully!', 'aria'),
			});
			setTimeout(() => setNotice(null), 5000);
		} catch (error) {
			setNotice({
				type: 'error',
				message: __('Failed to save settings. Please try again.', 'aria'),
			});
		} finally {
			setSaving(false);
		}
	};

	const sendTestEmail = async () => {
		try {
			await new Promise((resolve) => setTimeout(resolve, 1000));
			setNotice({
				type: 'success',
				message: __('Test email sent successfully!', 'aria'),
			});
			setTimeout(() => setNotice(null), 5000);
		} catch (error) {
			setNotice({
				type: 'error',
				message: __('Failed to send test email.', 'aria'),
			});
		}
	};

	return (
		<div style={{ paddingRight: '32px' }}>
			{notice && (
				<div style={{ marginBottom: '24px' }}>
					<Notice
						status={notice.type}
						isDismissible={true}
						onRemove={() => setNotice(null)}
					>
						{notice.message}
					</Notice>
				</div>
			)}

			<Card size="large" style={{ padding: '24px', marginBottom: '24px' }}>
				<CardHeader style={{ paddingBottom: '16px' }}>
					<h3
						style={{
							fontSize: '18px',
							fontWeight: '600',
							marginBottom: '8px',
							margin: 0,
						}}
					>
						{__('Email Notifications', 'aria')}
					</h3>
					<p style={{ fontSize: '14px', color: '#757575', margin: 0 }}>
						{__(
							'Configure email notifications for conversations and events',
							'aria'
						)}
					</p>
				</CardHeader>
				<CardBody style={{ paddingTop: '24px' }}>
					<div
						style={{
							display: 'grid',
							gap: '20px',
						}}
					>
						<ToggleControl
							label={__('Enable Email Notifications', 'aria')}
							help={__(
								'Enable email notifications for conversations',
								'aria'
							)}
							checked={settings.enableNotifications}
							onChange={(value) =>
								updateSetting('enableNotifications', value)
							}
						/>

						<TextControl
							label={__('Additional Recipients', 'aria')}
							value={settings.additionalRecipients}
							help={__(
								'Enter additional email addresses separated by commas',
								'aria'
							)}
							placeholder="email1@example.com, email2@example.com"
							onChange={(value) =>
								updateSetting('additionalRecipients', value)
							}
						/>

						<ToggleControl
							label={__('New Conversation Alerts', 'aria')}
							help={__(
								'Send email when a visitor starts a new conversation',
								'aria'
							)}
							checked={settings.newConversation}
							onChange={(value) =>
								updateSetting('newConversation', value)
							}
						/>
					</div>
				</CardBody>
			</Card>

			<Card size="large" style={{ padding: '24px', marginBottom: '24px' }}>
				<CardHeader style={{ paddingBottom: '16px' }}>
					<h3
						style={{
							fontSize: '18px',
							fontWeight: '600',
							marginBottom: '8px',
							margin: 0,
						}}
					>
						{__('Test Notifications', 'aria')}
					</h3>
					<p style={{ fontSize: '14px', color: '#757575', margin: 0 }}>
						{__(
							'Send a test email to verify your notification settings',
							'aria'
						)}
					</p>
				</CardHeader>
				<CardBody style={{ paddingTop: '24px' }}>
					<Button variant="secondary" onClick={sendTestEmail}>
						{__('Send Test Email', 'aria')}
					</Button>
				</CardBody>
			</Card>

			<Card>
				<CardBody>
					<Button
						variant="primary"
						onClick={handleSave}
						isBusy={saving}
						disabled={saving}
					>
						{saving
							? __('Saving…', 'aria')
							: __('Save Notification Settings', 'aria')}
					</Button>
				</CardBody>
			</Card>
		</div>
	);
};

const AdvancedSettings = () => {
	const [settings, setSettings] = useState({
		cacheResponses: true,
		cacheDuration: '3600',
		rateLimit: '60',
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
				message: __('Advanced settings saved successfully!', 'aria'),
			});
			setTimeout(() => setNotice(null), 5000);
		} catch (error) {
			setNotice({
				type: 'error',
				message: __('Failed to save settings. Please try again.', 'aria'),
			});
		} finally {
			setSaving(false);
		}
	};

	return (
		<div style={{ paddingRight: '32px' }}>
			{notice && (
				<div style={{ marginBottom: '24px' }}>
					<Notice
						status={notice.type}
						isDismissible={true}
						onRemove={() => setNotice(null)}
					>
						{notice.message}
					</Notice>
				</div>
			)}

			<Card size="large" style={{ padding: '24px', marginBottom: '24px' }}>
				<CardHeader style={{ paddingBottom: '16px' }}>
					<h3
						style={{
							fontSize: '18px',
							fontWeight: '600',
							marginBottom: '8px',
							margin: 0,
						}}
					>
						{__('Performance Settings', 'aria')}
					</h3>
					<p style={{ fontSize: '14px', color: '#757575', margin: 0 }}>
						{__(
							'Configure caching and performance optimization settings',
							'aria'
						)}
					</p>
				</CardHeader>
				<CardBody style={{ paddingTop: '24px' }}>
					<div
						style={{
							display: 'grid',
							gap: '20px',
						}}
					>
						<ToggleControl
							label={__('Cache AI Responses', 'aria')}
							help={__(
								'Cache similar questions to improve performance and reduce API costs',
								'aria'
							)}
							checked={settings.cacheResponses}
							onChange={(value) =>
								updateSetting('cacheResponses', value)
							}
						/>

						<TextControl
							label={__('Cache Duration (seconds)', 'aria')}
							type="number"
							value={settings.cacheDuration}
							help={__(
								'How long to cache responses (3600 = 1 hour)',
								'aria'
							)}
							onChange={(value) =>
								updateSetting('cacheDuration', value)
							}
						/>

						<TextControl
							label={__('Rate Limit (messages per hour)', 'aria')}
							type="number"
							value={settings.rateLimit}
							help={__(
								'Maximum messages per visitor per hour to prevent abuse',
								'aria'
							)}
							onChange={(value) => updateSetting('rateLimit', value)}
						/>
					</div>
				</CardBody>
			</Card>

			<Card>
				<CardBody>
					<Button
						variant="primary"
						onClick={handleSave}
						isBusy={saving}
						disabled={saving}
					>
						{saving
							? __('Saving…', 'aria')
							: __('Save Advanced Settings', 'aria')}
					</Button>
				</CardBody>
			</Card>
		</div>
	);
};

const PrivacySettings = () => {
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
				message: __('Failed to save settings. Please try again.', 'aria'),
			});
		} finally {
			setSaving(false);
		}
	};

	return (
		<div style={{ paddingRight: '32px' }}>
			{notice && (
				<div style={{ marginBottom: '24px' }}>
					<Notice
						status={notice.type}
						isDismissible={true}
						onRemove={() => setNotice(null)}
					>
						{notice.message}
					</Notice>
				</div>
			)}

			<Card size="large" style={{ padding: '24px', marginBottom: '24px' }}>
				<CardHeader style={{ paddingBottom: '16px' }}>
					<h3
						style={{
							fontSize: '18px',
							fontWeight: '600',
							marginBottom: '8px',
							margin: 0,
						}}
					>
						{__('GDPR Compliance', 'aria')}
					</h3>
					<p style={{ fontSize: '14px', color: '#757575', margin: 0 }}>
						{__(
							'Configure privacy settings and GDPR compliance features',
							'aria'
						)}
					</p>
				</CardHeader>
				<CardBody style={{ paddingTop: '24px' }}>
					<div
						style={{
							display: 'grid',
							gap: '20px',
						}}
					>
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
				</CardBody>
			</Card>

			<Card>
				<CardBody>
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
				</CardBody>
			</Card>
		</div>
	);
};

const LicenseSettings = () => {
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
				message: __('Failed to activate license. Please check your license key.', 'aria'),
			});
		} finally {
			setSaving(false);
		}
	};

	return (
		<div style={{ paddingRight: '32px' }}>
			{notice && (
				<div style={{ marginBottom: '24px' }}>
					<Notice
						status={notice.type}
						isDismissible={true}
						onRemove={() => setNotice(null)}
					>
						{notice.message}
					</Notice>
				</div>
			)}

			<Card size="large" style={{ padding: '24px', marginBottom: '24px' }}>
				<CardHeader style={{ paddingBottom: '16px' }}>
					<h3
						style={{
							fontSize: '18px',
							fontWeight: '600',
							marginBottom: '8px',
							margin: 0,
						}}
					>
						{__('License Information', 'aria')}
					</h3>
					<p style={{ fontSize: '14px', color: '#757575', margin: 0 }}>
						{__(
							'Enter your license key to unlock all premium features',
							'aria'
						)}
					</p>
				</CardHeader>
				<CardBody style={{ paddingTop: '24px' }}>
					<div
						style={{
							display: 'grid',
							gap: '20px',
						}}
					>
						<div>
							<div
								style={{
									display: 'flex',
									alignItems: 'center',
									gap: '12px',
									marginBottom: '16px',
								}}
							>
								<span style={{ fontSize: '14px', fontWeight: '600' }}>
									{__('Status:', 'aria')}
								</span>
								<div
									style={{
										display: 'flex',
										alignItems: 'center',
										gap: '8px',
									}}
								>
									<div
										style={{
											width: '12px',
											height: '12px',
											borderRadius: '50%',
											backgroundColor:
												settings.licenseStatus === 'active'
													? '#28a745'
													: '#dc3545',
										}}
									/>
									<span
										style={{
											fontSize: '14px',
											color:
												settings.licenseStatus === 'active'
													? '#28a745'
													: '#dc3545',
											fontWeight: '600',
											textTransform: 'capitalize',
										}}
									>
										{settings.licenseStatus === 'active'
											? __('Active', 'aria')
											: __('Inactive', 'aria')}
									</span>
								</div>
							</div>
						</div>

						<TextControl
							label={__('License Key', 'aria')}
							value={settings.licenseKey}
							help={__(
								'Enter your license key from your purchase confirmation',
								'aria'
							)}
							placeholder="ARIA-XXXX-XXXX-XXXX-XXXX"
							onChange={(value) => updateSetting('licenseKey', value)}
						/>
					</div>
				</CardBody>
			</Card>

			<Card>
				<CardBody>
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
				</CardBody>
			</Card>
		</div>
	);
};

const Settings = () => {
	const tabs = [
		{ name: 'general', title: __('General', 'aria') },
		{ name: 'notifications', title: __('Notifications', 'aria') },
		{ name: 'advanced', title: __('Advanced', 'aria') },
		{ name: 'privacy', title: __('Privacy & GDPR', 'aria') },
		{ name: 'license', title: __('License', 'aria') },
	];

	return (
		<div className="aria-settings-react">
			<PageHeader
				title={__('Settings', 'aria')}
				description={__(
					'Configure how Aria behaves and interacts with your visitors',
					'aria'
				)}
			/>
			<TabPanel
				className="aria-settings-tabs"
				activeClass="active-tab"
				tabs={tabs}
				initialTabName="general"
			>
				{(tab) => <SettingsTabContent tabName={tab.name} />}
			</TabPanel>
		</div>
	);
};

export default Settings;