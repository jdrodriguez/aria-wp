import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Button,
	ToggleControl,
	TextControl,
	Spinner,
} from '@wordpress/components';
import { SectionCard } from '../../components';
import { fetchAdvancedSettings, saveAdvancedSettings } from '../../utils/api';
import SettingsNotice from './SettingsNotice.jsx';

const AdvancedSettingsPanel = () => {
	const [settings, setSettings] = useState({
		cacheResponses: true,
		cacheDuration: '3600',
		rateLimit: '60',
		debugLogging: false,
	});
	const [saving, setSaving] = useState(false);
	const [notice, setNotice] = useState(null);
	const [loading, setLoading] = useState(true);

	const updateSetting = (key, value) => {
		setSettings((prev) => ({ ...prev, [key]: value }));
	};

	useEffect(() => {
		let mounted = true;

		const loadSettings = async () => {
			try {
				const data = await fetchAdvancedSettings();
				if (!mounted) {
					return;
				}

				setSettings({
					cacheResponses: Boolean(data.cacheResponses),
					cacheDuration: data.cacheDuration || '3600',
					rateLimit: data.rateLimit || '60',
					debugLogging: Boolean(data.debugLogging),
				});
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
		setSaving(true);
		try {
			await saveAdvancedSettings({
				cache_responses: settings.cacheResponses ? '1' : '0',
				cache_duration: settings.cacheDuration,
				rate_limit: settings.rateLimit,
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
							disabled={saving}
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
