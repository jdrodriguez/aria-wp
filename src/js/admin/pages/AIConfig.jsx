import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { PageHeader, PageShell } from '../components';
import {
	AIConfigNotice,
	AIConfigProviderSection,
	AIConfigModelSection,
	AIConfigUsageSection,
	AIConfigActions,
} from './ai-config-sections';

/* global ariaAdmin */

export const AIConfig = () => {
	const [provider, setProvider] = useState('openai');
	const [apiKey, setApiKey] = useState('');
	const [showApiKey, setShowApiKey] = useState(false);
	const [isTestingApi, setIsTestingApi] = useState(false);
	const [testingKeyType, setTestingKeyType] = useState(null);
	const [saving, setSaving] = useState(false);
	const [notice, setNotice] = useState(null);
	const [modelSettings, setModelSettings] = useState({
		openai_model: 'gpt-3.5-turbo',
		openai_max_tokens: 500,
		openai_temperature: 0.7,
		gemini_model: 'gemini-pro',
		gemini_max_tokens: 500,
	});
	const [currentApiKey, setCurrentApiKey] = useState('');
	const [usageStats, setUsageStats] = useState({
		monthly_usage: 0,
		estimated_cost: 0,
		recent_activity: [],
	});

	useEffect(() => {
		loadConfiguration();
		loadUsageStats();
	}, []);

	const loadConfiguration = async () => {
		try {
			const response = await fetch(ariaAdmin.ajaxUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams({
					action: 'aria_get_ai_config',
					nonce: ariaAdmin.nonce,
				}),
			});

			const data = await response.json();
			if (data.success) {
				setProvider(data.data.provider || 'openai');
				setCurrentApiKey(data.data.masked_key || '');
				setModelSettings((prev) => ({
					...prev,
					...data.data.model_settings,
				}));
			}
		} catch (error) {
			// eslint-disable-next-line no-console
			console.error('Failed to load AI configuration:', error);
		}
	};

	const loadUsageStats = async () => {
		try {
			const response = await fetch(ariaAdmin.ajaxUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams({
					action: 'aria_get_usage_stats',
					nonce: ariaAdmin.nonce,
				}),
			});

			const data = await response.json();
			if (data.success) {
				setUsageStats({
					monthly_usage: data.data.monthly_usage || 0,
					estimated_cost: data.data.estimated_cost || 0,
					recent_activity: data.data.recent_activity || [],
				});
			}
		} catch (error) {
			// eslint-disable-next-line no-console
			console.error('Failed to load usage stats:', error);
		}
	};

	const handleSaveConfiguration = async () => {
		try {
			setSaving(true);
			setNotice({ type: 'info', message: __('Saving configuration...', 'aria') });

			const response = await fetch(ariaAdmin.ajaxUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams({
					action: 'aria_save_ai_config',
					nonce: ariaAdmin.nonce,
					provider,
					api_key: apiKey,
					model_settings: JSON.stringify(modelSettings),
				}),
			});

			const data = await response.json();
			if (data.success) {
				setNotice({ type: 'success', message: data.data.message });
				setApiKey('');
				loadConfiguration();
			} else {
				setNotice({
					type: 'error',
					message:
						data?.data?.message || __('Failed to save configuration', 'aria'),
				});
			}
		} catch (error) {
			setNotice({
				type: 'error',
				message: __('Network error occurred', 'aria'),
			});
		} finally {
			setSaving(false);
		}
	};

	const handleTestApi = async ({ useCurrentKey = false } = {}) => {
		if (!useCurrentKey && (!apiKey || apiKey.includes('*'))) {
			setNotice({ type: 'error', message: __('Please enter a valid API key', 'aria') });
			return;
		}

		const keyType = useCurrentKey ? 'saved' : 'new';
		setIsTestingApi(true);
		setTestingKeyType(keyType);
		setNotice({ type: 'info', message: __('Testing API connection...', 'aria') });

		try {
			const response = await fetch(ariaAdmin.ajaxUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams({
					action: 'aria_test_api',
					nonce: ariaAdmin.nonce,
					provider,
					api_key: useCurrentKey ? 'current' : apiKey,
				}),
			});

			const data = await response.json();
			setNotice({
				type: data.success ? 'success' : 'error',
				message: data.data.message,
			});
		} catch (error) {
			setNotice({ type: 'error', message: __('Connection test failed', 'aria') });
		} finally {
			setIsTestingApi(false);
			setTestingKeyType(null);
		}
	};

	const getProviderModels = () => {
		const models = {
			openai: [
				{
					value: 'gpt-4',
					label: 'GPT-4',
					description: __('Most capable model, higher cost', 'aria'),
					cost_level: 'high',
				},
				{
					value: 'gpt-4-turbo-preview',
					label: 'GPT-4 Turbo',
					description: __('Latest GPT-4 with improved performance', 'aria'),
					cost_level: 'high',
				},
				{
					value: 'gpt-3.5-turbo',
					label: 'GPT-3.5 Turbo',
					description: __('Fast and efficient, good balance', 'aria'),
					cost_level: 'medium',
				},
				{
					value: 'gpt-3.5-turbo-16k',
					label: 'GPT-3.5 Turbo 16K',
					description: __('Larger context window', 'aria'),
					cost_level: 'medium',
				},
			],
			gemini: [
				{
					value: 'gemini-pro',
					label: 'Gemini Pro',
					description: __("Google's most capable model", 'aria'),
					cost_level: 'medium',
				},
				{
					value: 'gemini-pro-vision',
					label: 'Gemini Pro Vision',
					description: __('Multimodal with image support', 'aria'),
					cost_level: 'high',
				},
			],
		};

		return models[provider] || [];
	};

	const getCurrentModel = () =>
		provider === 'openai' ? modelSettings.openai_model : modelSettings.gemini_model;

	const updateModelSettings = (key, value) => {
		setModelSettings((prev) => ({
			...prev,
			[key]: value,
		}));
	};

	const getApiKeyHelp = () => {
		if (provider === 'openai') {
			return (
				<p>
					{__('Get your API key from', 'aria')}{' '}
					<a
						href="https://platform.openai.com/api-keys"
						target="_blank"
						rel="noopener noreferrer"
					>
						OpenAI Platform
					</a>
					.
				</p>
			);
		}

		return (
			<p>
				{__('Get your API key from', 'aria')}{' '}
				<a
					href="https://makersuite.google.com/app/apikey"
					target="_blank"
					rel="noopener noreferrer"
				>
					Google AI Studio
				</a>
				.
			</p>
		);
	};

	return (
		<PageShell className="aria-ai-config aria-ai-config-react" width="wide">
			<PageHeader
				title={__('AI Setup', 'aria')}
				description={__(
					'Connect your AI provider and tune responses so Aria reflects your brand.',
					'aria'
				)}
			/>

			<AIConfigNotice notice={notice} onRemove={() => setNotice(null)} />

			<div className="aria-stack-lg">
				<AIConfigProviderSection
					provider={provider}
					onChangeProvider={setProvider}
					apiKey={apiKey}
					onChangeApiKey={setApiKey}
					showApiKey={showApiKey}
					onToggleShowApiKey={() => setShowApiKey((prev) => !prev)}
					onTestApiKey={() => handleTestApi({ useCurrentKey: false })}
					onTestSavedKey={
						currentApiKey
							? () => handleTestApi({ useCurrentKey: true })
							: undefined
					}
					isTesting={isTestingApi}
					testingKeyType={testingKeyType}
					currentApiKey={currentApiKey}
					apiKeyHelp={getApiKeyHelp()}
				/>

				<AIConfigModelSection
					provider={provider}
					currentModel={getCurrentModel()}
					providerModels={getProviderModels()}
					onChangeModel={(value) =>
						updateModelSettings(
							provider === 'openai' ? 'openai_model' : 'gemini_model',
							value
						)
					}
					modelSettings={modelSettings}
					onUpdateModelSettings={updateModelSettings}
				/>

				<AIConfigUsageSection usageStats={usageStats} provider={provider} />

				<AIConfigActions
					onSave={handleSaveConfiguration}
					isSaving={saving}
				/>
			</div>
		</PageShell>
	);
};
