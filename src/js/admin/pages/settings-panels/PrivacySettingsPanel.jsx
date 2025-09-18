import { useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Spinner } from '@wordpress/components';
import { SectionCard, ToggleControl, TextControl } from '../../components';
import SettingsNotice from './SettingsNotice.jsx';
import { fetchPrivacySettings, savePrivacySettings } from '../../utils/api';

const INTEGER_PATTERN = /^\d+$/;

const validatePrivacyPolicyUrl = (value, { requireUrl = false } = {}) => {
	const trimmed = (value || '').trim();

	if (requireUrl && trimmed === '') {
		return __('Provide a privacy policy URL when GDPR features are enabled.', 'aria');
	}

	if (trimmed === '') {
		return null;
	}

	try {
		// eslint-disable-next-line no-new
		new URL(trimmed);
	} catch (error) {
		return __('Enter a valid URL (including https://).', 'aria');
	}

	return null;
};

const validateDataRetention = (value) => {
	if (value === null || typeof value === 'undefined' || value === '') {
		return __('Specify how long to retain conversation data.', 'aria');
	}

	const trimmed = String(value).trim();
	if (!INTEGER_PATTERN.test(trimmed)) {
		return __('Use whole numbers only (days).', 'aria');
	}

	const numericValue = parseInt(trimmed, 10);
	if (Number.isNaN(numericValue)) {
		return __('Enter a valid number of days.', 'aria');
	}

	if (numericValue <= 0) {
		return __('Retention period must be greater than zero.', 'aria');
	}

	if (numericValue > 3650) {
		return __('Keep retention to 3650 days (10 years) or less.', 'aria');
	}

	return null;
};

const normalizeNumber = (value, fallback = 0, bounds = {}) => {
	const parsed = parseInt(String(value ?? '').trim(), 10);
	if (Number.isNaN(parsed)) {
		return fallback;
	}
	const { min, max } = bounds;
	if (typeof min === 'number' && parsed < min) {
		return min;
	}
	if (typeof max === 'number' && parsed > max) {
		return max;
	}
	return parsed;
};

const computeValidationState = (settings) => ({
	privacyPolicyUrl: validatePrivacyPolicyUrl(settings.privacyPolicyUrl, {
		requireUrl: settings.enableGDPR,
	}),
	dataRetention: validateDataRetention(settings.dataRetention),
});

const PrivacySettingsPanel = () => {
	const [settings, setSettings] = useState({
		enableGDPR: false,
		privacyPolicyUrl: '',
		dataRetention: '90',
	});
	const [errors, setErrors] = useState({
		privacyPolicyUrl: null,
		dataRetention: null,
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
				const data = await fetchPrivacySettings();
				if (!mounted) {
					return;
				}

				if (data?.settings) {
					const nextSettings = {
						enableGDPR: Boolean(data.settings.enableGDPR),
						privacyPolicyUrl: data.settings.privacyPolicyUrl || '',
						dataRetention: String(data.settings.dataRetention ?? '90'),
					};
					setSettings(nextSettings);
					setErrors(computeValidationState(nextSettings));
				}
			} catch (error) {
				if (mounted) {
					setNotice({
						type: 'error',
						message:
							error?.message || __('Failed to load privacy settings.', 'aria'),
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
					message: __('Resolve the highlighted privacy fields before saving.', 'aria'),
				});
				setSaving(false);
				return;
			}

			const payload = {
				enableGDPR: settings.enableGDPR,
				privacyPolicyUrl: settings.privacyPolicyUrl.trim(),
				dataRetention: normalizeNumber(settings.dataRetention, 90, {
					min: 1,
					max: 3650,
				}),
			};

			const data = await savePrivacySettings(payload);
			if (data?.settings) {
				const nextSettings = {
					enableGDPR: Boolean(data.settings.enableGDPR),
					privacyPolicyUrl: data.settings.privacyPolicyUrl || '',
					dataRetention: String(data.settings.dataRetention),
				};
				setSettings(nextSettings);
				setErrors(computeValidationState(nextSettings));
			}
			setNotice({
				type: 'success',
				message:
					data?.message || __('Privacy settings saved successfully!', 'aria'),
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

	if (loading) {
		return (
			<div className="aria-settings__tab-content aria-settings__loading">
				<Spinner />
				<span>{__('Loading privacy settings…', 'aria')}</span>
			</div>
		);
	}

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
							disabled={saving || hasValidationErrors}
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
						className={errors.privacyPolicyUrl ? 'aria-input--error' : ''}
						label={__('Privacy Policy URL', 'aria')}
						type="url"
						value={settings.privacyPolicyUrl}
						help={
							errors.privacyPolicyUrl
								|| __('Link to your privacy policy page (shown in GDPR consent)', 'aria')
						}
						placeholder="https://yoursite.com/privacy-policy"
						onChange={(value) =>
							updateSetting('privacyPolicyUrl', value)
						}
					/>

					<TextControl
						className={errors.dataRetention ? 'aria-input--error' : ''}
						label={__('Data Retention Period (days)', 'aria')}
						type="number"
						value={settings.dataRetention}
						help={
							errors.dataRetention
								|| __('Automatically delete conversations older than this many days', 'aria')
						}
						onChange={(value) =>
							updateSetting('dataRetention', value)
						}
						min={1}
						max={3650}
					/>
				</div>
			</SectionCard>
		</div>
	);
};

export default PrivacySettingsPanel;
