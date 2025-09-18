import { useState, useEffect, useMemo } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Button, Spinner } from '@wordpress/components';
import { SectionCard, ToggleControl, TextControl } from '../../components';
import SettingsNotice from './SettingsNotice.jsx';
import {
	fetchNotificationSettings,
	saveNotificationSettings,
	makeAjaxRequest,
} from '../../utils/api';

const normalizeRecipients = (input = '') =>
	input
		.split(',')
		.map((value) => value.trim())
		.filter(Boolean);

const isValidEmail = (value) => {
	// Lightweight validation; server still sanitizes
	return /.+@.+\..+/.test(value);
};

const validateRecipients = (value, { requireRecipient = false } = {}) => {
	const recipients = normalizeRecipients(value);

	if (requireRecipient && recipients.length === 0) {
		return __('Add at least one email recipient to receive notifications.', 'aria');
	}

	const invalidRecipients = recipients.filter((email) => !isValidEmail(email));
	if (invalidRecipients.length > 0) {
		return sprintf(
			/* translators: %s: invalid email addresses */
			__('Invalid email address(es): %s', 'aria'),
			invalidRecipients.join(', ')
		);
	}

	return null;
};

const computeValidationState = (settings) => ({
	additionalRecipients: validateRecipients(settings.additionalRecipients, {
		requireRecipient: settings.enableNotifications,
	}),
});

const NotificationSettingsPanel = () => {
	const [settings, setSettings] = useState({
		enableNotifications: false,
		additionalRecipients: '',
		newConversation: true,
	});
	const [errors, setErrors] = useState({
		additionalRecipients: null,
	});
	const [saving, setSaving] = useState(false);
	const [notice, setNotice] = useState(null);
	const [loading, setLoading] = useState(true);

	const updateSetting = (key, value) => {
		setSettings((prev) => {
			const nextSettings = { ...prev, [key]: value };
			setErrors((prevErrors) => ({
				...prevErrors,
				additionalRecipients: validateRecipients(nextSettings.additionalRecipients, {
					requireRecipient: nextSettings.enableNotifications,
				}),
			}));
			return nextSettings;
		});
	};

	const hasValidationErrors = useMemo(
		() => Object.values(errors).some(Boolean),
		[errors]
	);

	const handleSave = async () => {
		setSaving(true);
		try {
			const validationState = computeValidationState(settings);
			const validationFailed = Object.values(validationState).some(Boolean);
			setErrors(validationState);
			if (validationFailed) {
				setNotice({
					type: 'error',
					message: __('Please fix the highlighted notification fields.', 'aria'),
				});
				setSaving(false);
				return;
			}

			const recipients = normalizeRecipients(settings.additionalRecipients);
			const payload = {
				enableNotifications: settings.enableNotifications,
				additionalRecipients: recipients.join(', '),
				newConversation: settings.newConversation,
			};
			const data = await saveNotificationSettings(payload);
			if (data?.settings) {
				const nextSettings = {
					...settings,
					...data.settings,
				};
				setSettings(nextSettings);
				setErrors(computeValidationState(nextSettings));
			}
			setNotice({
				type: 'success',
				message:
					data?.message || __('Notification settings saved successfully!', 'aria'),
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

	const sendTestEmail = async () => {
		const validationState = computeValidationState(settings);
		setErrors(validationState);

		if (!settings.enableNotifications) {
			setNotice({
				type: 'warning',
				message: __('Enable notifications before sending a test email.', 'aria'),
			});
			return;
		}

		if (validationState.additionalRecipients) {
			setNotice({
				type: 'error',
				message: validationState.additionalRecipients,
			});
			return;
		}

		const recipients = normalizeRecipients(settings.additionalRecipients);
		try {
			await makeAjaxRequest('aria_test_notification');
			setNotice({
				type: 'success',
				message: __('Test email sent successfully!', 'aria'),
			});
			setTimeout(() => setNotice(null), 5000);
		} catch (error) {
			setNotice({
				type: 'error',
				message:
					error?.message || __('Failed to send test email.', 'aria'),
			});
		}
	};

	useEffect(() => {
		const loadNotificationSettings = async () => {
			setLoading(true);
			try {
				const data = await fetchNotificationSettings();
				if (data?.settings) {
					const nextSettings = {
						enableNotifications: Boolean(data.settings.enableNotifications),
						additionalRecipients: data.settings.additionalRecipients || '',
						newConversation: Boolean(data.settings.newConversation),
					};
					setSettings(nextSettings);
					setErrors(computeValidationState(nextSettings));
				}
			} catch (error) {
				setNotice({
					type: 'error',
					message:
						error?.message || __('Failed to load notification settings.', 'aria'),
				});
			} finally {
				setLoading(false);
			}
		};

		loadNotificationSettings();
	}, []);

	if (loading) {
		return (
			<div className="aria-settings__tab-content aria-settings__loading">
				<Spinner />
				<span>{__('Loading notification settings…', 'aria')}</span>
			</div>
		);
	}

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
				<Button
					variant="secondary"
					onClick={sendTestEmail}
					disabled={
						saving ||
						hasValidationErrors ||
						!settings.enableNotifications ||
						!normalizeRecipients(settings.additionalRecipients).length
					}
				>
					{__('Send Test Email', 'aria')}
				</Button>
						<Button
							variant="primary"
							onClick={handleSave}
							isBusy={saving}
							disabled={saving || hasValidationErrors}
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
						className={errors.additionalRecipients ? 'aria-input--error' : ''}
						label={__('Additional Recipients', 'aria')}
						value={settings.additionalRecipients}
						help={
							errors.additionalRecipients
								|| __('Enter additional email addresses separated by commas', 'aria')
						}
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
