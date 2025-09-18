import { useEffect, useMemo, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Button, Spinner } from '@wordpress/components';
import { SectionCard, TextControl } from '../../components';
import SettingsNotice from './SettingsNotice.jsx';
import { fetchLicenseSettings, activateLicense } from '../../utils/api';

const LICENSE_PATTERN = /^[A-Za-z0-9-]{12,}$/;

const validateLicenseKey = (value) => {
	const trimmed = (value || '').trim();

	if (!trimmed) {
		return __('Enter your license key to activate premium features.', 'aria');
	}

	if (!LICENSE_PATTERN.test(trimmed)) {
		return __('License keys can include letters, numbers, and dashes (minimum 12 characters).', 'aria');
	}

	return null;
};

const LicenseSettingsPanel = () => {
	const [settings, setSettings] = useState({
		licenseKey: '',
		licenseStatus: 'inactive',
	});
	const [errors, setErrors] = useState({
		licenseKey: null,
	});
	const [saving, setSaving] = useState(false);
	const [notice, setNotice] = useState(null);
	const [loading, setLoading] = useState(true);

	const updateSetting = (key, value) => {
		setSettings((prev) => {
			const nextSettings = { ...prev, [key]: value };
			if ('licenseKey' === key) {
				setErrors((prevErrors) => ({
					...prevErrors,
					licenseKey: validateLicenseKey(value),
				}));
			}
			return nextSettings;
		});
	};

	const hasValidationErrors = useMemo(
		() => Object.values(errors).some(Boolean),
		[errors]
	);

	useEffect(() => {
		let mounted = true;

		const loadLicense = async () => {
			setLoading(true);
			try {
				const data = await fetchLicenseSettings();
				if (!mounted) {
					return;
				}
				if (data?.settings) {
					const nextSettings = {
						licenseKey: data.settings.licenseKey || '',
						licenseStatus: data.settings.licenseStatus || 'inactive',
						activatedAt: data.settings.activatedAt || '',
					};
					setSettings(nextSettings);
					setErrors((prevErrors) => ({
						...prevErrors,
						licenseKey: validateLicenseKey(nextSettings.licenseKey),
					}));
				}
			} catch (error) {
				if (mounted) {
					setNotice({
						type: 'error',
						message:
							error?.message || __('Failed to load license settings.', 'aria'),
					});
				}
			} finally {
				if (mounted) {
					setLoading(false);
				}
			}
		};

		loadLicense();

		return () => {
			mounted = false;
		};
	}, []);

	const handleActivate = async () => {
		setSaving(true);
		try {
			const validationError = validateLicenseKey(settings.licenseKey);
			setErrors((prevErrors) => ({
				...prevErrors,
				licenseKey: validationError,
			}));

			if (validationError) {
				setNotice({
					type: 'error',
					message: validationError,
				});
				setSaving(false);
				return;
			}

			const data = await activateLicense({ licenseKey: settings.licenseKey.trim() });
			if (data?.settings) {
				const nextSettings = {
					licenseKey: data.settings.licenseKey,
					licenseStatus: data.settings.licenseStatus,
					activatedAt: data.settings.activatedAt,
				};
				setSettings(nextSettings);
				setErrors((prevErrors) => ({
					...prevErrors,
					licenseKey: validateLicenseKey(nextSettings.licenseKey),
				}));
			}
			setNotice({
				type: 'success',
				message: data?.message || __('License activated successfully!', 'aria'),
			});
			setTimeout(() => setNotice(null), 5000);
		} catch (error) {
			setNotice({
				type: 'error',
				message:
					error?.message || __('Failed to activate license. Please check your license key.', 'aria'),
			});
		} finally {
			setSaving(false);
		}
	};

	const statusKey = settings.licenseStatus === 'active' ? 'active' : 'inactive';
	const activationTimestamp = settings.activatedAt
		? new Date(settings.activatedAt).toLocaleString()
		: null;

	if (loading) {
		return (
			<div className="aria-settings__tab-content aria-settings__loading">
				<Spinner />
				<span>{__('Loading license information…', 'aria')}</span>
			</div>
		);
	}

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
						disabled={saving || hasValidationErrors || !settings.licenseKey.trim()}
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
					<div className="aria-settings__status">
						<span
							className={`aria-settings__status-indicator aria-settings__status-indicator--${statusKey}`}
							aria-hidden="true"
						/>
						<span className={`aria-settings__status-label aria-settings__status--${statusKey}`}>
							{statusKey === 'active'
								? __('Active', 'aria')
								: __('Inactive', 'aria')}
						</span>
						{activationTimestamp && (
							<span className="aria-settings__status-meta">
								{sprintf(
									/* translators: %s: activation time */
									__('Activated on %s', 'aria'),
									activationTimestamp
								)}
							</span>
						)}
					</div>

					<TextControl
						className={errors.licenseKey ? 'aria-input--error' : ''}
						label={__('License Key', 'aria')}
						value={settings.licenseKey}
						help={
							errors.licenseKey
								|| __('Enter your license key from your purchase confirmation', 'aria')
						}
						placeholder="ARIA-XXXX-XXXX-XXXX-XXXX"
						onChange={(value) => updateSetting('licenseKey', value)}
					/>
				</div>
			</SectionCard>
		</div>
	);
};

export default LicenseSettingsPanel;
