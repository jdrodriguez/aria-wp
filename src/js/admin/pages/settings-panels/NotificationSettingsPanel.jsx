import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, ToggleControl, TextControl } from '@wordpress/components';
import { SectionCard } from '../../components';
import SettingsNotice from './SettingsNotice.jsx';

const NotificationSettingsPanel = () => {
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
				message: __(
					'Notification settings saved successfully!',
					'aria'
				),
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
		<div className="aria-settings__tab-content">
			<SectionCard
				title={__('Email Notifications', 'aria')}
				description={__(
					'Configure email notifications for conversations and events',
					'aria'
				)}
				footer={
					<div className="aria-settings__actions aria-settings__test-actions">
						<Button variant="secondary" onClick={sendTestEmail}>
							{__('Send Test Email', 'aria')}
						</Button>
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
					</div>
				}
			>
				<SettingsNotice
					notice={notice}
					onRemove={() => setNotice(null)}
				/>
				<div className="aria-settings__grid">
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
			</SectionCard>
		</div>
	);
};

export default NotificationSettingsPanel;
