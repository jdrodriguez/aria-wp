import { useState, useMemo, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Spinner } from '@wordpress/components';
import {
	SectionCard,
	ToggleControl,
	SelectControl,
	TextControl,
} from '../../components';
import { DISPLAY_OPTIONS } from '../../utils/constants';
import SettingsNotice from './SettingsNotice.jsx';
import { fetchGeneralSettings, saveGeneralSettings } from '../../utils/api';

const INTEGER_PATTERN = /^\d+$/;

const validateAutoOpenDelay = (value) => {
	if (value === null || typeof value === 'undefined' || value === '') {
		return __('Specify a delay (seconds) or use 0 to disable auto-open.', 'aria');
	}

	const trimmed = String(value).trim();
	if (!INTEGER_PATTERN.test(trimmed)) {
		return __('Use whole numbers only (seconds).', 'aria');
	}

	const numericValue = parseInt(trimmed, 10);
	if (Number.isNaN(numericValue)) {
		return __('Enter a valid number of seconds.', 'aria');
	}

	if (numericValue < 0) {
		return __('Delay cannot be negative.', 'aria');
	}

	if (numericValue > 120) {
		return __('Keep the delay at 120 seconds or less for a timely prompt.', 'aria');
	}

	return null;
};

const computeValidationState = (settings) => ({
	autoOpenDelay: validateAutoOpenDelay(settings.autoOpenDelay),
});

const normalizeAutoOpenDelay = (value) => {
	const parsed = parseInt(String(value ?? '').trim(), 10);
	if (Number.isNaN(parsed)) {
		return 0;
	}

	if (parsed < 0) {
		return 0;
	}

	if (parsed > 120) {
		return 120;
	}

	return parsed;
};

const GeneralSettingsPanel = () => {
	const [settings, setSettings] = useState({
		enableChat: true,
		displayOn: 'all',
		autoOpenDelay: '0',
		requireEmail: false,
	});
	const [errors, setErrors] = useState({
		autoOpenDelay: null,
	});
	const [saving, setSaving] = useState(false);
	const [notice, setNotice] = useState(null);
	const [loading, setLoading] = useState(true);

	const updateSetting = (key, value) => {
		setSettings((prev) => {
			const nextSettings = { ...prev, [key]: value };
			setErrors(computeValidationState(nextSettings));
			return nextSettings;
		});
	};

	const hasValidationErrors = useMemo(
		() => Object.values(errors).some(Boolean),
		[errors]
	);

	useEffect(() => {
		let mounted = true;

		const loadSettings = async () => {
			setLoading(true);
			try {
				const data = await fetchGeneralSettings();
				if (!mounted) {
					return;
				}

				if (data?.settings) {
					const nextSettings = {
						enableChat: Boolean(data.settings.enableChat),
						displayOn: data.settings.displayOn || 'all',
						autoOpenDelay: String(data.settings.autoOpenDelay ?? '0'),
						requireEmail: Boolean(data.settings.requireEmail),
					};
					setSettings(nextSettings);
					setErrors(computeValidationState(nextSettings));
				}
			} catch (error) {
				if (mounted) {
					setNotice({
						type: 'error',
						message:
							error?.message || __('Failed to load general settings.', 'aria'),
					});
				}
			} finally {
				if (mounted) {
					setLoading(false);
				}
			}
		};

		loadSettings();

		return () => {
			mounted = false;
		};
	}, []);

	const handleSave = async () => {
		setSaving(true);
		try {
			const validationState = computeValidationState(settings);
			const validationFailed = Object.values(validationState).some(Boolean);
			setErrors(validationState);
			if (validationFailed) {
				setNotice({
					type: 'error',
					message: __('Fix the highlighted general settings before saving.', 'aria'),
				});
				setSaving(false);
				return;
			}

			const payload = {
				enableChat: settings.enableChat,
				displayOn: settings.displayOn,
				autoOpenDelay: normalizeAutoOpenDelay(settings.autoOpenDelay),
				requireEmail: settings.requireEmail,
			};
			const data = await saveGeneralSettings(payload);
			if (data?.settings) {
				const nextSettings = {
					enableChat: Boolean(data.settings.enableChat),
					displayOn: data.settings.displayOn || 'all',
					autoOpenDelay: String(data.settings.autoOpenDelay ?? '0'),
					requireEmail: Boolean(data.settings.requireEmail),
				};
				setSettings(nextSettings);
				setErrors(computeValidationState(nextSettings));
			}
			setNotice({
				type: 'success',
				message:
					data?.message || __('General settings saved successfully!', 'aria'),
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

	const panelTitle = __('Chat Widget Settings', 'aria');
	const panelDescription = __('Configure basic settings for your chat widget', 'aria');

	if (loading) {
		return (
			<div className="aria-settings__tab-content">
				<SectionCard title={panelTitle} description={panelDescription}>
					<div className="aria-settings__loading">
						<Spinner />
						<span>{__('Loading general settings…', 'aria')}</span>
					</div>
				</SectionCard>
			</div>
		);
	}

	return (
		<div className="aria-settings__tab-content">
			<SectionCard
				title={panelTitle}
				description={panelDescription}
				footer={
					<div className="aria-settings__actions">
						<Button
							variant="primary"
							onClick={handleSave}
							isBusy={saving}
							disabled={saving || hasValidationErrors}
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
						className={errors.autoOpenDelay ? 'aria-input--error' : ''}
						label={__('Auto-open Delay (seconds)', 'aria')}
						type="number"
						value={settings.autoOpenDelay}
						help={
							errors.autoOpenDelay
								|| __('Automatically open chat after this delay (0 to disable)', 'aria')
						}
						onChange={(value) =>
							updateSetting('autoOpenDelay', value)
						}
						min={0}
						max={120}
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
