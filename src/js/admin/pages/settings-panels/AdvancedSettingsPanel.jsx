import { useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Spinner } from '@wordpress/components';
import { SectionCard, ToggleControl, TextControl } from '../../components';
import { fetchAdvancedSettings, saveAdvancedSettings } from '../../utils/api';
import SettingsNotice from './SettingsNotice.jsx';

const INTEGER_PATTERN = /^\d+$/;

const validateCacheDuration = (value) => {
	if (value === '' || value === null || typeof value === 'undefined') {
		return __('Enter how long responses should be cached.', 'aria');
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
		return __('Cache duration cannot be negative.', 'aria');
	}

	if (numericValue > 86400) {
		return __('Limit caching to 86400 seconds (24 hours) or less.', 'aria');
	}

	return null;
};

const validateRateLimit = (value) => {
	if (value === '' || value === null || typeof value === 'undefined') {
		return __('Enter an hourly message limit.', 'aria');
	}

	const trimmed = String(value).trim();
	if (!INTEGER_PATTERN.test(trimmed)) {
		return __('Use whole numbers only (messages per hour).', 'aria');
	}

	const numericValue = parseInt(trimmed, 10);
	if (Number.isNaN(numericValue)) {
		return __('Enter a valid hourly limit.', 'aria');
	}

	if (numericValue < 1) {
		return __('Set the limit to at least 1 message per hour.', 'aria');
	}

	if (numericValue > 500) {
		return __('Keep the limit at 500 messages per hour or less.', 'aria');
	}

	return null;
};

const fieldValidators = {
	cacheDuration: validateCacheDuration,
	rateLimit: validateRateLimit,
};

const normalizeNumber = (value, fallback = 0, bounds = {}) => {
	const normalized = parseInt(String(value ?? '').trim(), 10);
	if (Number.isNaN(normalized)) {
		return fallback;
	}

	const { min, max } = bounds;
	if (typeof min === 'number' && normalized < min) {
		return min;
	}
	if (typeof max === 'number' && normalized > max) {
		return max;
	}

	return normalized;
};

const AdvancedSettingsPanel = () => {
	const [settings, setSettings] = useState({
		cacheResponses: true,
		cacheDuration: '3600',
		rateLimit: '60',
		debugLogging: false,
	});
	const [errors, setErrors] = useState({
		cacheDuration: null,
		rateLimit: null,
	});
	const [saving, setSaving] = useState(false);
	const [notice, setNotice] = useState(null);
	const [loading, setLoading] = useState(true);

	const hasValidationErrors = useMemo(
		() => Object.values(errors).some(Boolean),
		[errors]
	);

	const updateSetting = (key, value) => {
		setSettings((prev) => ({ ...prev, [key]: value }));

		if (fieldValidators[key]) {
			setErrors((prev) => ({
				...prev,
				[key]: fieldValidators[key](value),
			}));
		}
	};

	const computeValidationState = (nextSettings) => {
		return Object.entries(fieldValidators).reduce((acc, [key, validator]) => {
			acc[key] = validator(nextSettings[key]);
			return acc;
		}, {});
	};

	useEffect(() => {
		let mounted = true;

		const loadSettings = async () => {
			try {
				const data = await fetchAdvancedSettings();
				if (!mounted) {
					return;
				}

				const nextSettings = {
					cacheResponses: Boolean(data.cacheResponses),
					cacheDuration: data.cacheDuration ? String(data.cacheDuration) : '3600',
					rateLimit: data.rateLimit ? String(data.rateLimit) : '60',
					debugLogging: Boolean(data.debugLogging),
				};

				setSettings(nextSettings);
				setErrors(computeValidationState(nextSettings));
			} catch (error) {
				if (mounted) {
					setNotice({
						type: 'error',
						message: __(
							'Failed to load advanced settings.',
							'aria'
						),
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
		const validationState = computeValidationState(settings);
		const validationFailed = Object.values(validationState).some(Boolean);

		setErrors(validationState);

		if (validationFailed) {
			setNotice({
				type: 'error',
				message: __('Please resolve the highlighted fields before saving.', 'aria'),
			});
			return;
		}

		setSaving(true);
		try {
			await saveAdvancedSettings({
				cache_responses: settings.cacheResponses ? '1' : '0',
				cache_duration: String(
					normalizeNumber(settings.cacheDuration, 0, { min: 0, max: 86400 })
				),
				rate_limit: String(
					normalizeNumber(settings.rateLimit, 1, { min: 1, max: 500 })
				),
				debug_logging: settings.debugLogging ? '1' : '0',
			});
			setNotice({
				type: 'success',
				message: __('Advanced settings saved successfully!', 'aria'),
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

	if (loading) {
		return (
			<div className="aria-settings__tab-content">
				<SectionCard title={__('Performance Settings', 'aria')}>
					<div className="aria-settings__loading">
						<Spinner />
						<span>{__('Loading advanced settings…', 'aria')}</span>
					</div>
				</SectionCard>
			</div>
		);
	}

	return (
		<div className="aria-settings__tab-content">
			<SectionCard
				title={__('Performance Settings', 'aria')}
				description={__(
					'Configure caching and performance optimization settings',
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
								: __('Save Advanced Settings', 'aria')}
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
						className={errors.cacheDuration ? 'aria-input--error' : ''}
						label={__('Cache Duration (seconds)', 'aria')}
						type="number"
						value={settings.cacheDuration}
						help={
							errors.cacheDuration
								|| __('How long to cache responses (3600 = 1 hour)', 'aria')
						}
						onChange={(value) =>
							updateSetting('cacheDuration', value)
						}
						min={0}
						max={86400}
					/>

					<TextControl
						className={errors.rateLimit ? 'aria-input--error' : ''}
						label={__('Rate Limit (messages per hour)', 'aria')}
						type="number"
						value={settings.rateLimit}
						help={
							errors.rateLimit
								|| __('Maximum messages per visitor per hour to prevent abuse', 'aria')
						}
						onChange={(value) => updateSetting('rateLimit', value)}
						min={1}
						max={500}
					/>

					<ToggleControl
						label={__('Enable Debug Logging', 'aria')}
						help={__(
							'Log detailed debug output (requires WP_DEBUG or this toggle). Disable in production environments.',
							'aria'
						)}
						checked={settings.debugLogging}
						onChange={(value) =>
							updateSetting('debugLogging', value)
						}
					/>
				</div>
			</SectionCard>
		</div>
	);
};

export default AdvancedSettingsPanel;
