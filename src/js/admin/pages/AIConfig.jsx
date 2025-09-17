import React, { useState, useEffect } from 'react';
import { 
    Panel, 
    PanelBody, 
    Button, 
    SelectControl,
    TextControl,
    RangeControl,
    Notice,
    Card,
    CardBody,
    CardHeader,
    Flex,
    FlexItem,
    __experimentalSpacer as Spacer 
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { PageHeader, MetricCard } from '../components';

export const AIConfig = () => {
    // State management
    const [provider, setProvider] = useState('openai');
    const [apiKey, setApiKey] = useState('');
    const [showApiKey, setShowApiKey] = useState(false);
    const [isTestingApi, setIsTestingApi] = useState(false);
    const [notice, setNotice] = useState(null);
    const [modelSettings, setModelSettings] = useState({
        openai_model: 'gpt-3.5-turbo',
        openai_max_tokens: 500,
        openai_temperature: 0.7,
        gemini_model: 'gemini-pro',
        gemini_max_tokens: 500
    });
    const [currentApiKey, setCurrentApiKey] = useState('');
    const [usageStats, setUsageStats] = useState({
        monthly_usage: 0,
        estimated_cost: 0,
        recent_activity: []
    });

    // Load initial data
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
                    nonce: ariaAdmin.nonce
                })
            });
            
            const data = await response.json();
            if (data.success) {
                setProvider(data.data.provider || 'openai');
                setCurrentApiKey(data.data.masked_key || '');
                setModelSettings(prev => ({
                    ...prev,
                    ...data.data.model_settings
                }));
            }
        } catch (error) {
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
                    nonce: ariaAdmin.nonce
                })
            });
            
            const data = await response.json();
            if (data.success) {
                setUsageStats(data.data);
            }
        } catch (error) {
            console.error('Failed to load usage stats:', error);
        }
    };

    const handleSaveConfiguration = async () => {
        try {
            setNotice({ type: 'info', message: __('Saving configuration...', 'aria') });

            const response = await fetch(ariaAdmin.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'aria_save_ai_config',
                    nonce: ariaAdmin.nonce,
                    provider: provider,
                    api_key: apiKey,
                    model_settings: JSON.stringify(modelSettings)
                })
            });
            
            const data = await response.json();
            if (data.success) {
                setNotice({ type: 'success', message: data.data.message });
                setApiKey(''); // Clear input after successful save
                loadConfiguration(); // Reload to get updated masked key
            } else {
                setNotice({ type: 'error', message: data.data.message || __('Failed to save configuration', 'aria') });
            }
        } catch (error) {
            setNotice({ type: 'error', message: __('Network error occurred', 'aria') });
        }
    };

    const handleTestApi = async (useCurrentKey = false) => {
        const testKey = useCurrentKey ? 'current' : apiKey;
        
        if (!useCurrentKey && (!apiKey || apiKey.includes('*'))) {
            setNotice({ type: 'error', message: __('Please enter a valid API key', 'aria') });
            return;
        }

        setIsTestingApi(true);
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
                    provider: provider,
                    api_key: testKey
                })
            });
            
            const data = await response.json();
            setNotice({ 
                type: data.success ? 'success' : 'error', 
                message: data.data.message 
            });
        } catch (error) {
            setNotice({ type: 'error', message: __('Connection test failed', 'aria') });
        } finally {
            setIsTestingApi(false);
        }
    };

    const getProviderModels = () => {
        const models = {
            openai: [
                { value: 'gpt-4', label: 'GPT-4', description: __('Most capable model, higher cost', 'aria'), cost_level: 'high' },
                { value: 'gpt-4-turbo-preview', label: 'GPT-4 Turbo', description: __('Latest GPT-4 with improved performance', 'aria'), cost_level: 'high' },
                { value: 'gpt-3.5-turbo', label: 'GPT-3.5 Turbo', description: __('Fast and efficient, good balance', 'aria'), cost_level: 'medium' },
                { value: 'gpt-3.5-turbo-16k', label: 'GPT-3.5 Turbo 16K', description: __('Larger context window', 'aria'), cost_level: 'medium' }
            ],
            gemini: [
                { value: 'gemini-pro', label: 'Gemini Pro', description: __('Google\'s most capable model', 'aria'), cost_level: 'medium' },
                { value: 'gemini-pro-vision', label: 'Gemini Pro Vision', description: __('Multimodal with image support', 'aria'), cost_level: 'high' }
            ]
        };
        return models[provider] || [];
    };

    const getCurrentModel = () => {
        return provider === 'openai' ? modelSettings.openai_model : modelSettings.gemini_model;
    };

    const updateModelSettings = (key, value) => {
        setModelSettings(prev => ({
            ...prev,
            [key]: value
        }));
    };

    const getApiKeyHelp = () => {
        if (provider === 'openai') {
            return (
                <p>
                    {__('Get your API key from', 'aria')} <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener">OpenAI Platform</a>. 
                    {__(' Your key should start with "sk-".', 'aria')}
                </p>
            );
        } else {
            return (
                <p>
                    {__('Get your API key from', 'aria')} <a href="https://makersuite.google.com/app/apikey" target="_blank" rel="noopener">Google AI Studio</a>.
                </p>
            );
        }
    };

    return (
        <div className="aria-ai-config-react" style={{ paddingRight: '32px' }}>
            {/* Header Section matching Dashboard */}
            <div className="aria-ai-config-header">
                <h1 style={{ fontSize: '28px', fontWeight: '700', color: '#1e1e1e', marginBottom: '8px' }}>
                    {__('AI Configuration', 'aria')}
                </h1>
                <p style={{ fontSize: '16px', color: '#6c757d', lineHeight: '1.5', marginBottom: 0 }}>
                    {__('Configure your AI provider settings and customize how Aria responds', 'aria')}
                </p>
            </div>
            
            <Spacer marginTop={6} />

            {notice && (
                <>
                    <Notice 
                        status={notice.type} 
                        onRemove={() => setNotice(null)}
                        isDismissible={true}
                    >
                        {notice.message}
                    </Notice>
                    <Spacer marginTop={4} />
                </>
            )}

            {/* Main Metrics Grid */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: '24px', marginBottom: '32px' }}>
                {/* Provider Selection Card */}
                <Card size="large" style={{ padding: '24px' }}>
                    <CardHeader style={{ paddingBottom: '16px' }}>
                        <Flex align="center" gap={3}>
                            <div style={{ fontSize: '24px' }}>🔌</div>
                            <h2 style={{ fontSize: '18px', fontWeight: '600', color: '#1e1e1e', margin: 0 }}>
                                {__('AI Provider', 'aria')}
                            </h2>
                        </Flex>
                    </CardHeader>
                    <CardBody>
                        <SelectControl
                            value={provider}
                            onChange={(value) => setProvider(value)}
                            options={[
                                { value: 'openai', label: __('OpenAI (ChatGPT)', 'aria') },
                                { value: 'gemini', label: __('Google Gemini', 'aria') }
                            ]}
                            help={__('Select your preferred AI service provider.', 'aria')}
                        />
                    </CardBody>
                </Card>

                {/* API Key Configuration Card */}
                <Card size="large" style={{ padding: '24px' }}>
                    <CardHeader style={{ paddingBottom: '16px' }}>
                        <Flex align="center" gap={3}>
                            <div style={{ fontSize: '24px' }}>🔐</div>
                            <h2 style={{ fontSize: '18px', fontWeight: '600', color: '#1e1e1e', margin: 0 }}>
                                {__('API Key', 'aria')}
                            </h2>
                        </Flex>
                    </CardHeader>
                    <CardBody>
                        <TextControl
                            type={showApiKey ? 'text' : 'password'}
                            value={apiKey}
                            onChange={(value) => setApiKey(value)}
                            placeholder={currentApiKey 
                                ? __('Enter new API key (leave blank to keep current)', 'aria')
                                : __('Enter your API key', 'aria')
                            }
                        />
                        
                        <Spacer marginTop={3}>
                            <Flex gap={2}>
                                <FlexItem>
                                    <Button
                                        variant="secondary"
                                        onClick={() => setShowApiKey(!showApiKey)}
                                        icon={showApiKey ? 'hidden' : 'visibility'}
                                    >
                                        {showApiKey ? __('Hide', 'aria') : __('Show', 'aria')}
                                    </Button>
                                </FlexItem>
                                <FlexItem>
                                    <Button
                                        variant="secondary"
                                        onClick={() => handleTestApi(false)}
                                        isBusy={isTestingApi}
                                        disabled={!apiKey || apiKey.includes('*')}
                                        icon="update"
                                    >
                                        {__('Test', 'aria')}
                                    </Button>
                                </FlexItem>
                                {currentApiKey && (
                                    <FlexItem>
                                        <Button
                                            variant="secondary"
                                            onClick={() => handleTestApi(true)}
                                            isBusy={isTestingApi}
                                            icon="saved"
                                        >
                                            {__('Test Saved', 'aria')}
                                        </Button>
                                    </FlexItem>
                                )}
                            </Flex>
                        </Spacer>

                        {currentApiKey && (
                            <Spacer marginTop={3}>
                                <Card size="small">
                                    <CardBody>
                                        <strong>{__('Current API Key:', 'aria')}</strong> 
                                        <code style={{ marginLeft: '8px' }}>{currentApiKey}</code>
                                    </CardBody>
                                </Card>
                            </Spacer>
                        )}

                        <Spacer marginTop={3}>
                            {getApiKeyHelp()}
                        </Spacer>
                    </CardBody>
                </Card>
            </div>

            {/* Provider-specific Settings */}
            <Card size="large" style={{ marginBottom: '32px' }}>
                <CardHeader>
                    <h2 style={{ fontSize: '20px', fontWeight: '600', color: '#1e1e1e' }}>
                        {provider === 'openai' ? __('OpenAI Settings', 'aria') : __('Google Gemini Settings', 'aria')}
                    </h2>
                </CardHeader>
                <CardBody style={{ padding: '24px' }}>
                            <div className="aria-model-config-grid">
                                <div>
                                    {/* Model Selection */}
                                    <SelectControl
                                        label={__('Model', 'aria')}
                                        value={getCurrentModel()}
                                        onChange={(value) => updateModelSettings(
                                            provider === 'openai' ? 'openai_model' : 'gemini_model', 
                                            value
                                        )}
                                        options={getProviderModels()}
                                    />

                                    {/* Model Details */}
                                    {getProviderModels().map(model => (
                                        getCurrentModel() === model.value && (
                                            <Card key={model.value} size="small">
                                                <CardBody>
                                                    <p>{model.description}</p>
                                                    {model.cost_level === 'high' && (
                                                        <Notice status="warning" isDismissible={false}>
                                                            <strong>{__('Cost Warning:', 'aria')}</strong> This model has higher usage costs.
                                                        </Notice>
                                                    )}
                                                    {model.cost_level === 'medium' && (
                                                        <Notice status="info" isDismissible={false}>
                                                            Moderate usage costs apply.
                                                        </Notice>
                                                    )}
                                                    {model.cost_level === 'low' && (
                                                        <Notice status="success" isDismissible={false}>
                                                            Cost-effective option.
                                                        </Notice>
                                                    )}
                                                </CardBody>
                                            </Card>
                                        )
                                    ))}
                                </div>

                                <div>
                                    {/* Response Settings */}
                                    <TextControl
                                        label={__('Max Response Length', 'aria')}
                                        type="number"
                                        value={modelSettings[provider === 'openai' ? 'openai_max_tokens' : 'gemini_max_tokens']}
                                        onChange={(value) => updateModelSettings(
                                            provider === 'openai' ? 'openai_max_tokens' : 'gemini_max_tokens',
                                            parseInt(value)
                                        )}
                                        min={50}
                                        max={2000}
                                        step={50}
                                        help={__('Maximum tokens (1 token ≈ 4 characters)', 'aria')}
                                    />

                                    {provider === 'openai' && (
                                        <Spacer marginTop={4}>
                                            <RangeControl
                                                label={__('Response Creativity', 'aria')}
                                                value={modelSettings.openai_temperature}
                                                onChange={(value) => updateModelSettings('openai_temperature', value)}
                                                min={0}
                                                max={1}
                                                step={0.1}
                                                help={__('Lower = focused, higher = creative', 'aria')}
                                            />
                                        </Spacer>
                                    )}
                                </div>
                    </div>
                </CardBody>
            </Card>

            {/* Usage Statistics */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: '24px', marginBottom: '32px' }}>
                <Card size="large" style={{ padding: '24px' }}>
                    <CardHeader style={{ paddingBottom: '16px' }}>
                        <Flex align="center" gap={3}>
                            <div style={{ fontSize: '24px' }}>📊</div>
                            <h2 style={{ fontSize: '18px', fontWeight: '600', color: '#1e1e1e', margin: 0 }}>
                                {__('Usage Statistics', 'aria')}
                            </h2>
                        </Flex>
                    </CardHeader>
                    <CardBody>
                            <div className="metric-item large">
                                <span className="item-value primary">{usageStats.monthly_usage?.toLocaleString()}</span>
                                <span className="item-label">{__('Tokens This Month', 'aria')}</span>
                            </div>
                            {provider === 'openai' && usageStats.estimated_cost > 0 && (
                                <div className="metric-item">
                                    <span className="item-value secondary">${usageStats.estimated_cost?.toFixed(2)}</span>
                                    <span className="item-label">{__('Estimated Cost', 'aria')}</span>
                                </div>
                            )}
                    </CardBody>
                </Card>

                {usageStats.recent_activity?.length > 0 && (
                    <Card size="large" style={{ padding: '24px' }}>
                        <CardHeader style={{ paddingBottom: '16px' }}>
                            <Flex align="center" gap={3}>
                                <div style={{ fontSize: '24px' }}>🕐</div>
                                <h2 style={{ fontSize: '18px', fontWeight: '600', color: '#1e1e1e', margin: 0 }}>
                                    {__('Recent Activity', 'aria')}
                                </h2>
                            </Flex>
                        </CardHeader>
                        <CardBody>
                                <div className="aria-recent-activity">
                                    {usageStats.recent_activity.slice(0, 3).map((activity, index) => (
                                        <div key={index} className="activity-item">
                                            <span className="activity-time">
                                                {new Date(activity.timestamp).toLocaleDateString()}
                                            </span>
                                            <span className="activity-tokens">
                                                {activity.tokens_used} tokens
                                            </span>
                                        </div>
                                    ))}
                            </div>
                        </CardBody>
                    </Card>
                )}
            </div>

            {/* Save Button */}
            <Card>
                <CardBody>
                    <Button
                        variant="primary"
                        onClick={handleSaveConfiguration}
                        style={{
                            background: 'linear-gradient(135deg, #2271b1 0%, #1a5d8a 100%)',
                            border: 'none',
                            borderRadius: '10px',
                            padding: '12px 24px',
                            fontSize: '15px',
                            fontWeight: '600',
                            boxShadow: '0 4px 12px rgba(34, 113, 177, 0.25)'
                        }}
                    >
                        {__('Save Configuration', 'aria')}
                    </Button>
                </CardBody>
            </Card>
        </div>
    );
};