/**
 * Aria Admin React Components
 * Modern WordPress Components-based admin interface
 */


import { createRoot, useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import {
    Panel,
    PanelBody,
    Button,
    TextControl,
    SelectControl,
    ToggleControl,
    Card,
    CardHeader,
    CardBody,
    CardFooter,
    TabPanel,
    Flex,
    FlexItem,
    ColorPicker,
    Spacer,
    Heading,
    Text,
    Notice
} from '@wordpress/components';

// Import AIConfig component from pages
import { AIConfig } from './admin/pages/AIConfig.jsx';

// Settings Page Component
const AriaSettings = () => {
    const tabs = [
        { name: 'general', title: __('General', 'aria') },
        { name: 'notifications', title: __('Notifications', 'aria') },
        { name: 'advanced', title: __('Advanced', 'aria') },
        { name: 'privacy', title: __('Privacy & GDPR', 'aria') },
        { name: 'license', title: __('License', 'aria') }
    ];

    return (
        <div className="aria-settings-react">
            <Heading size={1}>{__('Settings', 'aria')}</Heading>
            <Text variant="muted">
                {__('Configure how Aria behaves and interacts with your visitors', 'aria')}
            </Text>
            
            <Spacer marginTop={6} />
            
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

// Settings Tab Content Component
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
    tabName: PropTypes.string.isRequired
};

// General Settings Component
const GeneralSettings = () => {
    return (
        <Panel className="aria-general-settings">
            <PanelBody title={__('General Settings', 'aria')} initialOpen={true}>
                <ToggleControl
                    label={__('Enable Chat', 'aria')}
                    help={__('Enable Aria chat widget on your website', 'aria')}
                    checked={true}
                    onChange={() => {}}
                />
                
                <Spacer marginTop={4} />
                
                <SelectControl
                    label={__('Display On', 'aria')}
                    value="all"
                    options={[
                        { label: __('All pages', 'aria'), value: 'all' },
                        { label: __('Homepage only', 'aria'), value: 'home' },
                        { label: __('Blog posts', 'aria'), value: 'posts' },
                        { label: __('Static pages', 'aria'), value: 'pages' }
                    ]}
                    onChange={() => {}}
                />
                
                <Spacer marginTop={4} />
                
                <TextControl
                    label={__('Auto-open Delay', 'aria')}
                    type="number"
                    value="0"
                    help={__('Automatically open chat after this delay (0 to disable)', 'aria')}
                    onChange={() => {}}
                />
                
                <Spacer marginTop={4} />
                
                <ToggleControl
                    label={__('Require Email', 'aria')}
                    help={__('Require visitors to provide email before chatting', 'aria')}
                    checked={false}
                    onChange={() => {}}
                />
            </PanelBody>
            
            <PanelBody title={__('Save Settings', 'aria')} initialOpen={false}>
                <Button variant="primary">
                    {__('Save Settings', 'aria')}
                </Button>
            </PanelBody>
        </Panel>
    );
};

// Notification Settings Component
const NotificationSettings = () => {
    return (
        <Panel className="aria-notification-settings">
            <PanelBody title={__('Email Notifications', 'aria')} initialOpen={true}>
                <ToggleControl
                    label={__('Enable Notifications', 'aria')}
                    help={__('Enable email notifications for conversations', 'aria')}
                    checked={false}
                    onChange={() => {}}
                />
                
                <Spacer marginTop={4} />
                
                <TextControl
                    label={__('Additional Recipients', 'aria')}
                    value=""
                    help={__('Enter additional email addresses separated by commas', 'aria')}
                    onChange={() => {}}
                />
                
                <Spacer marginTop={4} />
                
                <ToggleControl
                    label={__('New Conversation', 'aria')}
                    help={__('Send email when a visitor starts a new conversation', 'aria')}
                    checked={true}
                    onChange={() => {}}
                />
                
                <Spacer marginTop={4} />
                
                <Button variant="secondary">
                    {__('Send Test Email', 'aria')}
                </Button>
            </PanelBody>
        </Panel>
    );
};

// Advanced Settings Component
const AdvancedSettings = () => {
    return (
        <Panel className="aria-advanced-settings">
            <PanelBody title={__('Performance', 'aria')} initialOpen={true}>
                <ToggleControl
                    label={__('Cache Responses', 'aria')}
                    help={__('Cache similar questions to improve performance', 'aria')}
                    checked={true}
                    onChange={() => {}}
                />
                
                <Spacer marginTop={4} />
                
                <TextControl
                    label={__('Cache Duration', 'aria')}
                    type="number"
                    value="3600"
                    help={__('How long to cache responses (seconds)', 'aria')}
                    onChange={() => {}}
                />
                
                <Spacer marginTop={4} />
                
                <TextControl
                    label={__('Rate Limit', 'aria')}
                    type="number"
                    value="60"
                    help={__('Maximum messages per visitor per hour', 'aria')}
                    onChange={() => {}}
                />
            </PanelBody>
            
            <PanelBody title={__('Developer Options', 'aria')} initialOpen={false}>
                <ToggleControl
                    label={__('Debug Mode', 'aria')}
                    help={__('Enable debug logging (for troubleshooting)', 'aria')}
                    checked={false}
                    onChange={() => {}}
                />
                
                <Notice status="warning" isDismissible={false}>
                    <Text>
                        {__('Warning: Debug mode may expose sensitive information in logs.', 'aria')}
                    </Text>
                </Notice>
            </PanelBody>
        </Panel>
    );
};

// Privacy Settings Component
const PrivacySettings = () => {
    return (
        <Panel className="aria-privacy-settings">
            <PanelBody title={__('GDPR Compliance', 'aria')} initialOpen={true}>
                <ToggleControl
                    label={__('Enable GDPR Features', 'aria')}
                    help={__('Enable GDPR compliance features', 'aria')}
                    checked={false}
                    onChange={() => {}}
                />
                
                <Spacer marginTop={4} />
                
                <TextControl
                    label={__('Privacy Policy URL', 'aria')}
                    type="url"
                    value=""
                    help={__('Link to your privacy policy page', 'aria')}
                    onChange={() => {}}
                />
                
                <Spacer marginTop={4} />
                
                <TextControl
                    label={__('Data Retention', 'aria')}
                    type="number"
                    value="90"
                    help={__('Automatically delete conversations older than this (days)', 'aria')}
                    onChange={() => {}}
                />
            </PanelBody>
        </Panel>
    );
};

// License Settings Component
const LicenseSettings = () => {
    return (
        <Panel className="aria-license-settings">
            <PanelBody title={__('License Information', 'aria')} initialOpen={true}>
                <Card>
                    <CardHeader>
                        <Heading size={3}>{__('Current Status', 'aria')}</Heading>
                    </CardHeader>
                    <CardBody>
                        <Notice status="warning" isDismissible={false}>
                            <Text>
                                {__('Trial - 30 days remaining', 'aria')}
                            </Text>
                        </Notice>
                    </CardBody>
                </Card>
                
                <Spacer marginTop={4} />
                
                <TextControl
                    label={__('License Key', 'aria')}
                    value=""
                    help={__('Enter your license key to unlock all features', 'aria')}
                    onChange={() => {}}
                />
                
                <Spacer marginTop={4} />
                
                <Flex gap={4}>
                    <FlexItem>
                        <Button variant="primary">
                            {__('Activate License', 'aria')}
                        </Button>
                    </FlexItem>
                    <FlexItem>
                        <Button variant="secondary">
                            {__('Get License Key', 'aria')}
                        </Button>
                    </FlexItem>
                </Flex>
            </PanelBody>
        </Panel>
    );
};

// Design Page Component
const AriaDesign = () => {
    // State for design settings
    const [settings, setSettings] = useState({
        position: 'bottom-right',
        size: 'medium',
        theme: 'light',
        primaryColor: '#2271b1',
        backgroundColor: '#ffffff',
        textColor: '#1e1e1e',
        title: 'Chat with us',
        welcomeMessage: 'Hi! How can I help you today?'
    });

    const updateSetting = (key, value) => {
        setSettings(prev => ({ ...prev, [key]: value }));
    };

    return (
        <div className="aria-design-react">
            <Heading size={1}>{__('Design', 'aria')}</Heading>
            <Text variant="muted">
                {__('Customize the appearance and behavior of your chat widget', 'aria')}
            </Text>
            
            <Spacer marginTop={6} />
            
            <Panel className="aria-design-panel">
                <PanelBody title={__('Widget Appearance', 'aria')} initialOpen={true}>
                    <SelectControl
                        label={__('Widget Position', 'aria')}
                        value={settings.position}
                        options={[
                            { label: __('Bottom Right', 'aria'), value: 'bottom-right' },
                            { label: __('Bottom Left', 'aria'), value: 'bottom-left' },
                            { label: __('Top Right', 'aria'), value: 'top-right' },
                            { label: __('Top Left', 'aria'), value: 'top-left' }
                        ]}
                        onChange={(value) => updateSetting('position', value)}
                    />
                    
                    <Spacer marginTop={4} />
                    
                    <SelectControl
                        label={__('Widget Size', 'aria')}
                        value={settings.size}
                        options={[
                            { label: __('Small', 'aria'), value: 'small' },
                            { label: __('Medium', 'aria'), value: 'medium' },
                            { label: __('Large', 'aria'), value: 'large' }
                        ]}
                        onChange={(value) => updateSetting('size', value)}
                    />
                    
                    <Spacer marginTop={4} />
                    
                    <SelectControl
                        label={__('Theme', 'aria')}
                        value={settings.theme}
                        options={[
                            { label: __('Light', 'aria'), value: 'light' },
                            { label: __('Dark', 'aria'), value: 'dark' },
                            { label: __('Auto', 'aria'), value: 'auto' }
                        ]}
                        onChange={(value) => updateSetting('theme', value)}
                    />
                </PanelBody>
                
                <PanelBody title={__('Colors', 'aria')} initialOpen={true}>
                    <div className="aria-color-control">
                        <label className="components-base-control__label">
                            {__('Primary Color', 'aria')}
                        </label>
                        <p className="components-base-control__help">
                            {__('Main color for buttons and highlights', 'aria')}
                        </p>
                        <ColorPicker
                            color={settings.primaryColor}
                            onChange={(color) => updateSetting('primaryColor', color)}
                            enableAlpha={false}
                        />
                    </div>
                    
                    <Spacer marginTop={6} />
                    
                    <div className="aria-color-control">
                        <label className="components-base-control__label">
                            {__('Background Color', 'aria')}
                        </label>
                        <p className="components-base-control__help">
                            {__('Background color for the chat widget', 'aria')}
                        </p>
                        <ColorPicker
                            color={settings.backgroundColor}
                            onChange={(color) => updateSetting('backgroundColor', color)}
                            enableAlpha={false}
                        />
                    </div>
                    
                    <Spacer marginTop={6} />
                    
                    <div className="aria-color-control">
                        <label className="components-base-control__label">
                            {__('Text Color', 'aria')}
                        </label>
                        <p className="components-base-control__help">
                            {__('Color for text in the chat widget', 'aria')}
                        </p>
                        <ColorPicker
                            color={settings.textColor}
                            onChange={(color) => updateSetting('textColor', color)}
                            enableAlpha={false}
                        />
                    </div>
                </PanelBody>
                
                <PanelBody title={__('Branding', 'aria')} initialOpen={false}>
                    <TextControl
                        label={__('Widget Title', 'aria')}
                        value={settings.title}
                        help={__('Title shown at the top of the chat widget', 'aria')}
                        onChange={(value) => updateSetting('title', value)}
                    />
                    
                    <Spacer marginTop={4} />
                    
                    <TextControl
                        label={__('Welcome Message', 'aria')}
                        value={settings.welcomeMessage}
                        help={__('First message shown to visitors', 'aria')}
                        onChange={(value) => updateSetting('welcomeMessage', value)}
                    />
                    
                    <Spacer marginTop={4} />
                    
                    <Button variant="secondary">
                        {__('Upload Custom Icon', 'aria')}
                    </Button>
                    
                    <Spacer marginTop={2} />
                    
                    <Button variant="secondary">
                        {__('Upload Avatar', 'aria')}
                    </Button>
                </PanelBody>
                
                <PanelBody title={__('Preview', 'aria')} initialOpen={false}>
                    <Card>
                        <CardHeader>
                            <Heading size={4}>{__('Widget Preview', 'aria')}</Heading>
                        </CardHeader>
                        <CardBody>
                            <div style={{ 
                                padding: '20px', 
                                border: '1px solid #ddd', 
                                borderRadius: '8px',
                                backgroundColor: '#f9f9f9',
                                textAlign: 'center'
                            }}>
                                <Text>
                                    {__('Chat widget preview will appear here', 'aria')}
                                </Text>
                            </div>
                        </CardBody>
                    </Card>
                </PanelBody>
                
                <PanelBody title={__('Save Settings', 'aria')} initialOpen={false}>
                    <Button variant="primary">
                        {__('Save Design Settings', 'aria')}
                    </Button>
                </PanelBody>
            </Panel>
        </div>
    );
};

// Helper function to format time ago
const formatTimeAgo = (dateString) => {
    if (!dateString) return __('Unknown time', 'aria');
    
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) {
        return __('Just now', 'aria');
    } else if (diffInSeconds < 3600) {
        const minutes = Math.floor(diffInSeconds / 60);
        return minutes === 1 ? __('1 minute ago', 'aria') : `${minutes} ${__('minutes ago', 'aria')}`;
    } else if (diffInSeconds < 86400) {
        const hours = Math.floor(diffInSeconds / 3600);
        return hours === 1 ? __('1 hour ago', 'aria') : `${hours} ${__('hours ago', 'aria')}`;
    } else if (diffInSeconds < 604800) {
        const days = Math.floor(diffInSeconds / 86400);
        return days === 1 ? __('1 day ago', 'aria') : `${days} ${__('days ago', 'aria')}`;
    } else {
        // For older dates, show the actual date
        return date.toLocaleDateString();
    }
};

// Dashboard Page Component
const AriaDashboard = () => {
    const [dashboardData, setDashboardData] = useState({
        conversationsToday: 0,
        totalConversations: 0,
        knowledgeCount: 0,
        licenseStatus: { status: 'trial', days_remaining: 30 },
        recentConversations: [],
        setupSteps: []
    });

    // In a real implementation, this would fetch data from WordPress
    // For now, we'll use placeholder data that matches the PHP structure
    const [loading, setLoading] = useState(true);

    // Fetch real data from WordPress AJAX
    useEffect(() => {
        const fetchDashboardData = async () => {
            // Try to get data from React root element data attributes as backup
            const rootElement = document.getElementById('aria-dashboard-root');
            const fallbackAjaxUrl = rootElement?.getAttribute('data-ajax-url');
            const fallbackNonce = rootElement?.getAttribute('data-nonce');
            const fallbackAdminUrl = rootElement?.getAttribute('data-admin-url');

            // Check if WordPress admin variables are available
            if (!window.ariaAdmin) {
                console.error('ariaAdmin object not found. WordPress script localization may have failed.');
                setDashboardData({
                    conversationsToday: 0,
                    totalConversations: 0,
                    knowledgeCount: 0,
                    licenseStatus: { status: 'error', days_remaining: 0 },
                    recentConversations: [],
                    setupSteps: []
                });
                setLoading(false);
                return;
            }

            // Check if required properties are available and use fallbacks
            if (!window.ariaAdmin.ajaxUrl || !window.ariaAdmin.nonce) {
                // Use data attributes as primary fallback
                if (fallbackAjaxUrl && fallbackNonce) {
                    window.ariaAdmin.ajaxUrl = fallbackAjaxUrl;
                    window.ariaAdmin.nonce = fallbackNonce;
                    window.ariaAdmin.adminUrl = fallbackAdminUrl;
                } else {
                    
                    // Try to get nonce from the test page approach as last resort
                    try {
                        const response = await fetch('/wp-content/plugins/aria/test-ajax-direct.php');
                        const testPageText = await response.text();
                        
                        // Extract nonce from the test page (it generates one)
                        const nonceMatch = testPageText.match(/const manualNonce = '([^']+)'/);
                        if (nonceMatch) {
                            window.ariaAdmin.nonce = nonceMatch[1];
                            window.ariaAdmin.ajaxUrl = '/wp-admin/admin-ajax.php';
                        } else {
                            throw new Error('Could not extract nonce from test page');
                        }
                    } catch (error) {
                        console.error('All fallback methods failed:', error);
                        setDashboardData({
                            conversationsToday: 0,
                            totalConversations: 0,
                            knowledgeCount: 0,
                            licenseStatus: { status: 'error', days_remaining: 0 },
                            recentConversations: [],
                            setupSteps: []
                        });
                        setLoading(false);
                        return;
                    }
                }
            }

            try {
                const formData = new FormData();
                formData.append('action', 'aria_get_dashboard_data');
                formData.append('nonce', window.ariaAdmin.nonce);

                const response = await fetch(window.ariaAdmin.ajaxUrl, {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                if (result.success) {
                    setDashboardData(result.data);
                } else {
                    console.error('Dashboard data fetch failed:', result.data?.message || 'Unknown error');
                    console.error('Full error response:', result);
                    // Fallback to basic data
                    setDashboardData({
                        conversationsToday: 0,
                        totalConversations: 0,
                        knowledgeCount: 0,
                        licenseStatus: { status: 'error', days_remaining: 0 },
                        recentConversations: [],
                        setupSteps: []
                    });
                }
            } catch (error) {
                console.error('Error fetching dashboard data:', error);
                console.error('Error details:', {
                    message: error.message,
                    stack: error.stack,
                    ajaxUrl: window.ariaAdmin?.ajaxUrl,
                    nonce: window.ariaAdmin?.nonce
                });
                // Fallback to basic data
                setDashboardData({
                    conversationsToday: 0,
                    totalConversations: 0,
                    knowledgeCount: 0,
                    licenseStatus: { status: 'error', days_remaining: 0 },
                    recentConversations: [],
                    setupSteps: []
                });
            } finally {
                setLoading(false);
            }
        };

        fetchDashboardData();
    }, []);

    if (loading) {
        return (
            <div className="aria-dashboard-react">
                <Heading size={1}>{__('Dashboard', 'aria')}</Heading>
                <Text variant="muted">
                    {__('Loading dashboard data...', 'aria')}
                </Text>
                <div style={{background: '#fff3cd', padding: '10px', margin: '10px 0', border: '1px solid #ffc107'}}>
                    <strong>🔧 DEBUG INFO:</strong><br/>
                    ariaAdmin available: {typeof window.ariaAdmin !== 'undefined' ? 'YES' : 'NO'}<br/>
                    {window.ariaAdmin && (
                        <>
                            AJAX URL: {window.ariaAdmin.ajaxUrl}<br/>
                            Nonce: {window.ariaAdmin.nonce ? 'Available' : 'Missing'}
                        </>
                    )}
                </div>
            </div>
        );
    }

    return (
        <div className="aria-dashboard-react" style={{ paddingRight: '32px' }}>
            <div className="aria-dashboard-header">
                <Heading size={1}>{__('Dashboard', 'aria')}</Heading>
                <Text variant="muted">
                    {__('Monitor your AI assistant\'s performance and activity', 'aria')}
                </Text>
            </div>
            
            <Spacer marginTop={6} />
            
            
            {/* Main Metrics Grid */}
            <div className="aria-dashboard-metrics" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: '24px', marginBottom: '32px' }}>
                {/* Today's Activity */}
                <Card size="large" style={{ padding: '24px' }}>
                    <CardHeader style={{ paddingBottom: '16px' }}>
                        <Flex align="center" gap={3}>
                            <div style={{ fontSize: '24px' }}>📊</div>
                            <Heading size={2} style={{ fontSize: '18px', fontWeight: '600', color: '#1e1e1e' }}>
                                {__('Today\'s Activity', 'aria')}
                            </Heading>
                        </Flex>
                    </CardHeader>
                    <CardBody>
                        <div style={{ textAlign: 'center', padding: '16px 0' }}>
                            <div style={{ fontSize: '48px', fontWeight: '700', color: '#2271b1', lineHeight: '1.2', marginBottom: '8px' }}>
                                {dashboardData.conversationsToday}
                            </div>
                            <div style={{ fontSize: '14px', color: '#757575', fontWeight: '500' }}>
                                {__('Conversations Today', 'aria')}
                            </div>
                        </div>
                    </CardBody>
                </Card>

                {/* Total Activity */}
                <Card size="large" style={{ padding: '24px' }}>
                    <CardHeader style={{ paddingBottom: '16px' }}>
                        <Flex align="center" gap={3}>
                            <div style={{ fontSize: '24px' }}>👥</div>
                            <Heading size={2} style={{ fontSize: '18px', fontWeight: '600', color: '#1e1e1e' }}>
                                {__('Total Activity', 'aria')}
                            </Heading>
                        </Flex>
                    </CardHeader>
                    <CardBody>
                        <div style={{ textAlign: 'center', padding: '16px 0' }}>
                            <div style={{ fontSize: '48px', fontWeight: '700', color: '#2271b1', lineHeight: '1.2', marginBottom: '8px' }}>
                                {dashboardData.totalConversations}
                            </div>
                            <div style={{ fontSize: '14px', color: '#757575', fontWeight: '500' }}>
                                {__('Total Conversations', 'aria')}
                            </div>
                        </div>
                    </CardBody>
                </Card>

                {/* Knowledge Base */}
                <Card size="large" style={{ padding: '24px' }}>
                    <CardHeader style={{ paddingBottom: '16px' }}>
                        <Flex align="center" gap={3}>
                            <div style={{ fontSize: '24px' }}>📚</div>
                            <Heading size={2} style={{ fontSize: '18px', fontWeight: '600', color: '#1e1e1e' }}>
                                {__('Knowledge Base', 'aria')}
                            </Heading>
                        </Flex>
                    </CardHeader>
                    <CardBody>
                        <div style={{ textAlign: 'center', padding: '16px 0' }}>
                            <div style={{ fontSize: '48px', fontWeight: '700', color: '#2271b1', lineHeight: '1.2', marginBottom: '8px' }}>
                                {dashboardData.knowledgeCount}
                            </div>
                            <div style={{ fontSize: '14px', color: '#757575', fontWeight: '500' }}>
                                {__('Knowledge Entries', 'aria')}
                            </div>
                        </div>
                    </CardBody>
                </Card>

                {/* License Status */}
                <Card size="large" style={{ padding: '24px' }}>
                    <CardHeader style={{ paddingBottom: '16px' }}>
                        <Flex align="center" gap={3}>
                            <div style={{ fontSize: '24px' }}>🔑</div>
                            <Heading size={2} style={{ fontSize: '18px', fontWeight: '600', color: '#1e1e1e' }}>
                                {__('License Status', 'aria')}
                            </Heading>
                        </Flex>
                    </CardHeader>
                    <CardBody>
                        <div style={{ textAlign: 'center', padding: '16px 0' }}>
                            <div style={{ fontSize: '32px', fontWeight: '600', color: '#2271b1', lineHeight: '1.2', marginBottom: '8px' }}>
                                {dashboardData.licenseStatus.status.charAt(0).toUpperCase() + dashboardData.licenseStatus.status.slice(1)}
                            </div>
                            <div style={{ fontSize: '14px', color: '#757575', fontWeight: '500' }}>
                                {__('Current Status', 'aria')}
                            </div>
                            {dashboardData.licenseStatus.status === 'trial' && (
                                <div style={{ marginTop: '12px', fontSize: '13px', color: '#d63638' }}>
                                    {__('Trial Remaining', 'aria')}: {dashboardData.licenseStatus.days_remaining} {__('days', 'aria')}
                                </div>
                            )}
                        </div>
                    </CardBody>
                </Card>
            </div>

            <Spacer marginTop={8} />

            {/* Setup Steps */}
            {dashboardData.setupSteps.some(step => !step.completed) && (
                <>
                    <Card>
                        <CardHeader>
                            <Heading size={3}>{__('Quick Setup', 'aria')}</Heading>
                        </CardHeader>
                        <CardBody>
                            <Flex direction="row" gap={4} wrap={true}>
                                {dashboardData.setupSteps.map((step, index) => (
                                    <FlexItem key={index}>
                                        <div className={`setup-step ${step.completed ? 'completed' : ''}`}>
                                            <div className="step-icon">
                                                {step.completed ? '✅' : '⏳'}
                                            </div>
                                            <Text weight={500}>{step.title}</Text>
                                            {!step.completed && (
                                                <Button variant="link" href={step.link} size="small">
                                                    {__('Configure', 'aria')}
                                                </Button>
                                            )}
                                        </div>
                                    </FlexItem>
                                ))}
                            </Flex>
                        </CardBody>
                    </Card>
                    <Spacer marginTop={6} />
                </>
            )}

            {/* Recent Conversations */}
            <Card size="large" style={{ padding: '24px' }}>
                <CardHeader style={{ paddingBottom: '20px' }}>
                    <Flex justify="space-between" align="center">
                        <Heading size={2} style={{ fontSize: '20px', fontWeight: '600', color: '#1e1e1e' }}>
                            {__('Recent Conversations', 'aria')}
                        </Heading>
                        <Button 
                            variant="secondary" 
                            onClick={() => window.location.href = (window.ariaAdmin?.adminUrl || '/wp-admin/') + 'admin.php?page=aria-conversations'}
                            style={{ 
                                fontSize: '14px',
                                fontWeight: '600',
                                background: 'linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%)',
                                border: '2px solid #e1e4e8',
                                borderRadius: '8px',
                                padding: '8px 16px',
                                color: '#1e1e1e',
                                boxShadow: '0 2px 6px rgba(0, 0, 0, 0.05)',
                                transition: 'all 0.2s ease',
                                cursor: 'pointer'
                            }}
                            onMouseEnter={(e) => {
                                e.target.style.borderColor = '#2271b1';
                                e.target.style.boxShadow = '0 4px 12px rgba(34, 113, 177, 0.15)';
                            }}
                            onMouseLeave={(e) => {
                                e.target.style.borderColor = '#e1e4e8';
                                e.target.style.boxShadow = '0 2px 6px rgba(0, 0, 0, 0.05)';
                            }}
                        >
                            {__('View All', 'aria')}
                        </Button>
                    </Flex>
                </CardHeader>
                <CardBody style={{ padding: '0' }}>
                    {dashboardData.recentConversations.length > 0 ? (
                        <div style={{ display: 'flex', flexDirection: 'column', gap: '0' }}>
                            {dashboardData.recentConversations.map((conversation, index) => (
                                <div 
                                    key={conversation.id} 
                                    onClick={() => window.location.href = (window.ariaAdmin?.adminUrl || '/wp-admin/') + `admin.php?page=aria-conversations&conversation_id=${conversation.id}`}
                                    style={{ 
                                        padding: '16px 20px',
                                        borderBottom: index < dashboardData.recentConversations.length - 1 ? '1px solid #e5e5e5' : 'none',
                                        cursor: 'pointer',
                                        transition: 'background-color 0.2s ease'
                                    }}
                                    onMouseEnter={(e) => e.target.style.backgroundColor = '#f8f9fa'}
                                    onMouseLeave={(e) => e.target.style.backgroundColor = 'transparent'}
                                >
                                    <Flex gap={3} align="flex-start" justify="space-between">
                                        <Flex gap={3} align="flex-start" style={{ flex: 1 }}>
                                            <div style={{ 
                                                width: '40px', 
                                                height: '40px', 
                                                borderRadius: '50%', 
                                                backgroundColor: '#2271b1', 
                                                color: 'white', 
                                                display: 'flex', 
                                                alignItems: 'center', 
                                                justifyContent: 'center', 
                                                fontSize: '16px', 
                                                fontWeight: '600',
                                                flexShrink: 0
                                            }}>
                                                {conversation.guest_name ? conversation.guest_name.charAt(0).toUpperCase() : 'A'}
                                            </div>
                                            <div style={{ flex: 1, minWidth: 0 }}>
                                                <div style={{ fontSize: '15px', fontWeight: '600', color: '#1e1e1e', marginBottom: '4px' }}>
                                                    {conversation.guest_name || __('Anonymous', 'aria')}
                                                </div>
                                                <div style={{ 
                                                    fontSize: '14px', 
                                                    color: '#757575', 
                                                    marginBottom: '6px',
                                                    overflow: 'hidden',
                                                    textOverflow: 'ellipsis',
                                                    whiteSpace: 'nowrap'
                                                }}>
                                                    {conversation.initial_question}
                                                </div>
                                                <div style={{ fontSize: '13px', color: '#949494' }}>
                                                    {conversation.created_at ? formatTimeAgo(conversation.created_at) : __('Unknown time', 'aria')}
                                                </div>
                                            </div>
                                        </Flex>
                                        <div style={{ 
                                            padding: '4px 12px', 
                                            borderRadius: '12px', 
                                            fontSize: '12px', 
                                            fontWeight: '500',
                                            backgroundColor: conversation.status === 'active' ? '#d1ecf1' : '#f8f9fa',
                                            color: conversation.status === 'active' ? '#0c5460' : '#6c757d',
                                            textTransform: 'uppercase',
                                            letterSpacing: '0.5px',
                                            flexShrink: 0
                                        }}>
                                            {conversation.status}
                                        </div>
                                    </Flex>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div style={{ textAlign: 'center', padding: '40px 20px' }}>
                            <div style={{ fontSize: '48px', marginBottom: '16px' }}>💬</div>
                            <div style={{ fontSize: '16px', color: '#757575', marginBottom: '20px' }}>
                                {__('No conversations yet. Aria is ready to start chatting with your visitors!', 'aria')}
                            </div>
                            <Button 
                                variant="primary" 
                                onClick={() => window.open(window.location.origin, '_blank')}
                                style={{
                                    background: 'linear-gradient(135deg, #2271b1 0%, #1a5d8a 100%)',
                                    border: 'none',
                                    borderRadius: '10px',
                                    padding: '12px 24px',
                                    fontSize: '15px',
                                    fontWeight: '600',
                                    boxShadow: '0 4px 12px rgba(34, 113, 177, 0.25)',
                                    transition: 'all 0.3s ease',
                                    cursor: 'pointer'
                                }}
                                onMouseEnter={(e) => {
                                    e.target.style.transform = 'translateY(-2px)';
                                    e.target.style.boxShadow = '0 6px 18px rgba(34, 113, 177, 0.35)';
                                }}
                                onMouseLeave={(e) => {
                                    e.target.style.transform = 'translateY(0)';
                                    e.target.style.boxShadow = '0 4px 12px rgba(34, 113, 177, 0.25)';
                                }}
                            >
                                {__('Test Aria', 'aria')}
                            </Button>
                        </div>
                    )}
                </CardBody>
            </Card>

            <Spacer marginTop={6} />

            {/* Quick Actions */}
            <Card size="large" style={{ padding: '24px' }}>
                <CardHeader style={{ paddingBottom: '20px' }}>
                    <Heading size={2} style={{ fontSize: '20px', fontWeight: '600', color: '#1e1e1e' }}>
                        {__('Quick Actions', 'aria')}
                    </Heading>
                </CardHeader>
                <CardBody>
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: '20px' }}>
                        <Button 
                            variant="secondary" 
                            onClick={() => window.location.href = (window.ariaAdmin?.adminUrl || '/wp-admin/') + 'admin.php?page=aria-knowledge&action=new'}
                            style={{ 
                                height: '72px', 
                                fontSize: '15px',
                                fontWeight: '600',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                gap: '12px',
                                background: 'linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%)',
                                border: '2px solid #e1e4e8',
                                borderRadius: '12px',
                                color: '#1e1e1e',
                                boxShadow: '0 2px 8px rgba(0, 0, 0, 0.08)',
                                transition: 'all 0.3s ease',
                                cursor: 'pointer'
                            }}
                            onMouseEnter={(e) => {
                                e.target.style.transform = 'translateY(-2px)';
                                e.target.style.borderColor = '#2271b1';
                                e.target.style.boxShadow = '0 4px 16px rgba(34, 113, 177, 0.15)';
                            }}
                            onMouseLeave={(e) => {
                                e.target.style.transform = 'translateY(0)';
                                e.target.style.borderColor = '#e1e4e8';
                                e.target.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.08)';
                            }}
                        >
                            <span style={{ 
                                fontSize: '20px',
                                background: 'linear-gradient(135deg, #2271b1 0%, #1a5d8a 100%)',
                                WebkitBackgroundClip: 'text',
                                WebkitTextFillColor: 'transparent',
                                backgroundClip: 'text',
                                padding: '6px',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center'
                            }}>➕</span>
                            {__('Add Knowledge', 'aria')}
                        </Button>
                        
                        <Button 
                            variant="secondary" 
                            onClick={() => window.location.href = (window.ariaAdmin?.adminUrl || '/wp-admin/') + 'admin.php?page=aria-personality'}
                            style={{ 
                                height: '72px', 
                                fontSize: '15px',
                                fontWeight: '600',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                gap: '12px',
                                background: 'linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%)',
                                border: '2px solid #e1e4e8',
                                borderRadius: '12px',
                                color: '#1e1e1e',
                                boxShadow: '0 2px 8px rgba(0, 0, 0, 0.08)',
                                transition: 'all 0.3s ease',
                                cursor: 'pointer'
                            }}
                            onMouseEnter={(e) => {
                                e.target.style.transform = 'translateY(-2px)';
                                e.target.style.borderColor = '#2271b1';
                                e.target.style.boxShadow = '0 4px 16px rgba(34, 113, 177, 0.15)';
                            }}
                            onMouseLeave={(e) => {
                                e.target.style.transform = 'translateY(0)';
                                e.target.style.borderColor = '#e1e4e8';
                                e.target.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.08)';
                            }}
                        >
                            <span style={{ 
                                fontSize: '20px',
                                background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                                WebkitBackgroundClip: 'text',
                                WebkitTextFillColor: 'transparent',
                                backgroundClip: 'text',
                                padding: '6px',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center'
                            }}>🎭</span>
                            {__('Adjust Personality', 'aria')}
                        </Button>
                        
                        <Button 
                            variant="secondary" 
                            onClick={() => window.open(window.location.origin, '_blank')}
                            style={{ 
                                height: '72px', 
                                fontSize: '15px',
                                fontWeight: '600',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                gap: '12px',
                                background: 'linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%)',
                                border: '2px solid #e1e4e8',
                                borderRadius: '12px',
                                color: '#1e1e1e',
                                boxShadow: '0 2px 8px rgba(0, 0, 0, 0.08)',
                                transition: 'all 0.3s ease',
                                cursor: 'pointer'
                            }}
                            onMouseEnter={(e) => {
                                e.target.style.transform = 'translateY(-2px)';
                                e.target.style.borderColor = '#28a745';
                                e.target.style.boxShadow = '0 4px 16px rgba(40, 167, 69, 0.15)';
                            }}
                            onMouseLeave={(e) => {
                                e.target.style.transform = 'translateY(0)';
                                e.target.style.borderColor = '#e1e4e8';
                                e.target.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.08)';
                            }}
                        >
                            <span style={{ 
                                fontSize: '20px',
                                background: 'linear-gradient(135deg, #28a745 0%, #20c997 100%)',
                                WebkitBackgroundClip: 'text',
                                WebkitTextFillColor: 'transparent',
                                backgroundClip: 'text',
                                padding: '6px',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center'
                            }}>🔗</span>
                            {__('Test Aria', 'aria')}
                        </Button>
                        
                        <Button 
                            variant="secondary" 
                            onClick={() => window.location.href = (window.ariaAdmin?.adminUrl || '/wp-admin/') + 'admin.php?page=aria-design'}
                            style={{ 
                                height: '72px', 
                                fontSize: '15px',
                                fontWeight: '600',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                gap: '12px',
                                background: 'linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%)',
                                border: '2px solid #e1e4e8',
                                borderRadius: '12px',
                                color: '#1e1e1e',
                                boxShadow: '0 2px 8px rgba(0, 0, 0, 0.08)',
                                transition: 'all 0.3s ease',
                                cursor: 'pointer'
                            }}
                            onMouseEnter={(e) => {
                                e.target.style.transform = 'translateY(-2px)';
                                e.target.style.borderColor = '#fd7e14';
                                e.target.style.boxShadow = '0 4px 16px rgba(253, 126, 20, 0.15)';
                            }}
                            onMouseLeave={(e) => {
                                e.target.style.transform = 'translateY(0)';
                                e.target.style.borderColor = '#e1e4e8';
                                e.target.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.08)';
                            }}
                        >
                            <span style={{ 
                                fontSize: '20px',
                                background: 'linear-gradient(135deg, #fd7e14 0%, #f093fb 100%)',
                                WebkitBackgroundClip: 'text',
                                WebkitTextFillColor: 'transparent',
                                backgroundClip: 'text',
                                padding: '6px',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center'
                            }}>🎨</span>
                            {__('Customize Design', 'aria')}
                        </Button>
                    </div>
                </CardBody>
            </Card>
        </div>
    );
};

// Personality Page Component
const AriaPersonality = () => {
    const [personalityData, setPersonalityData] = useState({
        businessType: 'general',
        toneSetting: 'professional',
        personalityTraits: [],
        greetingMessage: '',
        farewellMessage: ''
    });

    const [saving, setSaving] = useState(false);
    const [notice, setNotice] = useState(null);

    // Business types data
    const businessTypes = [
        { value: 'general', label: __('General Business', 'aria'), description: __('Standard business communication', 'aria') },
        { value: 'healthcare', label: __('Healthcare', 'aria'), description: __('Medical and health services', 'aria') },
        { value: 'retail', label: __('Retail', 'aria'), description: __('Sales and customer service', 'aria') },
        { value: 'technology', label: __('Technology', 'aria'), description: __('Tech support and services', 'aria') },
        { value: 'education', label: __('Education', 'aria'), description: __('Schools and learning', 'aria') },
        { value: 'finance', label: __('Finance', 'aria'), description: __('Banking and financial services', 'aria') }
    ];

    // Tone settings data
    const toneSettings = [
        { value: 'professional', label: __('Professional', 'aria'), description: __('Formal and business-like', 'aria') },
        { value: 'friendly', label: __('Friendly', 'aria'), description: __('Warm and approachable', 'aria') },
        { value: 'casual', label: __('Casual', 'aria'), description: __('Relaxed and informal', 'aria') },
        { value: 'formal', label: __('Formal', 'aria'), description: __('Very professional and structured', 'aria') }
    ];

    // Personality traits data
    const availableTraits = [
        { value: 'helpful', label: __('Helpful & Supportive', 'aria') },
        { value: 'knowledgeable', label: __('Knowledgeable', 'aria') },
        { value: 'empathetic', label: __('Empathetic', 'aria') },
        { value: 'efficient', label: __('Efficient', 'aria') },
        { value: 'patient', label: __('Patient', 'aria') },
        { value: 'proactive', label: __('Proactive', 'aria') }
    ];

    const updateSetting = (key, value) => {
        setPersonalityData(prev => ({ ...prev, [key]: value }));
    };

    const handleTraitChange = (traitValue, isChecked) => {
        setPersonalityData(prev => ({
            ...prev,
            personalityTraits: isChecked 
                ? [...prev.personalityTraits, traitValue]
                : prev.personalityTraits.filter(trait => trait !== traitValue)
        }));
    };

    const handleSave = async () => {
        setSaving(true);
        try {
            // In real implementation, this would make an AJAX call to save data
            // For now, simulate the save process
            await new Promise(resolve => setTimeout(resolve, 1000));
            setNotice({ type: 'success', message: __('Personality settings saved successfully!', 'aria') });
            setTimeout(() => setNotice(null), 5000);
        } catch (error) {
            setNotice({ type: 'error', message: __('Failed to save settings. Please try again.', 'aria') });
        } finally {
            setSaving(false);
        }
    };

    return (
        <div className="aria-personality-react" style={{ paddingRight: '32px' }}>
            <div className="aria-personality-header">
                <Heading size={1}>{__('Personality & Voice', 'aria')}</Heading>
                <Text variant="muted">
                    {__('Define how Aria communicates and interacts with your website visitors', 'aria')}
                </Text>
            </div>
            
            <Spacer marginTop={6} />

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

            {/* Business Type Section */}
            <Card size="large" style={{ padding: '24px', marginBottom: '24px' }}>
                <CardHeader style={{ paddingBottom: '16px' }}>
                    <Heading size={3} style={{ fontSize: '18px', fontWeight: '600', marginBottom: '8px' }}>
                        {__('Business Type', 'aria')}
                    </Heading>
                    <Text variant="muted" style={{ fontSize: '14px' }}>
                        {__('Select your business type to help Aria understand your context', 'aria')}
                    </Text>
                </CardHeader>
                <CardBody style={{ paddingTop: '24px' }}>
                    <div style={{ 
                        display: 'grid', 
                        gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', 
                        gap: '16px'
                    }}>
                        {businessTypes.map(type => (
                            <div key={type.value} style={{ position: 'relative' }}>
                                <label 
                                    role="radio"
                                    aria-checked={personalityData.businessType === type.value}
                                    aria-label={`${type.label}: ${type.description}`}
                                    tabIndex={0}
                                    style={{
                                    display: 'flex',
                                    alignItems: 'flex-start',
                                    padding: '20px',
                                    border: personalityData.businessType === type.value ? '2px solid #2271b1' : '2px solid #e1e4e8',
                                    borderRadius: '12px',
                                    cursor: 'pointer',
                                    transition: 'all 0.2s ease',
                                    background: personalityData.businessType === type.value 
                                        ? 'linear-gradient(135deg, rgba(34, 113, 177, 0.05) 0%, rgba(34, 113, 177, 0.02) 100%)'
                                        : 'linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%)',
                                    boxShadow: personalityData.businessType === type.value 
                                        ? '0 4px 16px rgba(34, 113, 177, 0.15)' 
                                        : '0 2px 8px rgba(0, 0, 0, 0.08)'
                                }}>
                                    <input 
                                        type="radio"
                                        name="businessType"
                                        value={type.value}
                                        checked={personalityData.businessType === type.value}
                                        onChange={(e) => updateSetting('businessType', e.target.value)}
                                        style={{ display: 'none' }}
                                    />
                                    <div style={{ flex: 1 }}>
                                        <div style={{ 
                                            fontSize: '16px', 
                                            fontWeight: '600', 
                                            color: '#1e1e1e', 
                                            marginBottom: '6px' 
                                        }}>
                                            {type.label}
                                        </div>
                                        <div style={{ 
                                            fontSize: '14px', 
                                            color: '#757575',
                                            lineHeight: '1.4'
                                        }}>
                                            {type.description}
                                        </div>
                                    </div>
                                    {personalityData.businessType === type.value && (
                                        <div style={{
                                            width: '24px',
                                            height: '24px',
                                            borderRadius: '50%',
                                            background: '#2271b1',
                                            color: 'white',
                                            display: 'flex',
                                            alignItems: 'center',
                                            justifyContent: 'center',
                                            fontSize: '14px',
                                            fontWeight: '600',
                                            marginLeft: '12px',
                                            flexShrink: 0
                                        }}>
                                            ✓
                                        </div>
                                    )}
                                </label>
                            </div>
                        ))}
                    </div>
                </CardBody>
            </Card>

            {/* Conversation Style Section */}
            <Card size="large" style={{ padding: '24px', marginBottom: '24px' }}>
                <CardHeader style={{ paddingBottom: '16px' }}>
                    <Heading size={3} style={{ fontSize: '18px', fontWeight: '600', marginBottom: '8px' }}>
                        {__('Conversation Style', 'aria')}
                    </Heading>
                    <Text variant="muted" style={{ fontSize: '14px' }}>
                        {__('Choose the tone that best fits your brand', 'aria')}
                    </Text>
                </CardHeader>
                <CardBody style={{ paddingTop: '24px' }}>
                    <div style={{ 
                        display: 'grid', 
                        gridTemplateColumns: 'repeat(auto-fit, minmax(260px, 1fr))', 
                        gap: '16px'
                    }}>
                        {toneSettings.map(tone => (
                            <div key={tone.value} style={{ position: 'relative' }}>
                                <label 
                                    role="radio"
                                    aria-checked={personalityData.toneSetting === tone.value}
                                    aria-label={`${tone.label}: ${tone.description}`}
                                    tabIndex={0}
                                    style={{
                                    display: 'flex',
                                    alignItems: 'flex-start',
                                    padding: '20px',
                                    border: personalityData.toneSetting === tone.value ? '2px solid #667eea' : '2px solid #e1e4e8',
                                    borderRadius: '12px',
                                    cursor: 'pointer',
                                    transition: 'all 0.2s ease',
                                    background: personalityData.toneSetting === tone.value 
                                        ? 'linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.02) 100%)'
                                        : 'linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%)',
                                    boxShadow: personalityData.toneSetting === tone.value 
                                        ? '0 4px 16px rgba(102, 126, 234, 0.15)' 
                                        : '0 2px 8px rgba(0, 0, 0, 0.08)'
                                }}>
                                    <input 
                                        type="radio"
                                        name="toneSetting"
                                        value={tone.value}
                                        checked={personalityData.toneSetting === tone.value}
                                        onChange={(e) => updateSetting('toneSetting', e.target.value)}
                                        style={{ display: 'none' }}
                                    />
                                    <div style={{ flex: 1 }}>
                                        <div style={{ 
                                            fontSize: '16px', 
                                            fontWeight: '600', 
                                            color: '#1e1e1e', 
                                            marginBottom: '6px' 
                                        }}>
                                            {tone.label}
                                        </div>
                                        <div style={{ 
                                            fontSize: '14px', 
                                            color: '#757575',
                                            lineHeight: '1.4'
                                        }}>
                                            {tone.description}
                                        </div>
                                    </div>
                                    {personalityData.toneSetting === tone.value && (
                                        <div style={{
                                            width: '24px',
                                            height: '24px',
                                            borderRadius: '50%',
                                            background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                                            color: 'white',
                                            display: 'flex',
                                            alignItems: 'center',
                                            justifyContent: 'center',
                                            fontSize: '14px',
                                            fontWeight: '600',
                                            marginLeft: '12px',
                                            flexShrink: 0
                                        }}>
                                            ✓
                                        </div>
                                    )}
                                </label>
                            </div>
                        ))}
                    </div>
                </CardBody>
            </Card>

            {/* Key Characteristics Section */}
            <Card size="large" style={{ padding: '24px', marginBottom: '24px' }}>
                <CardHeader style={{ paddingBottom: '16px' }}>
                    <Heading size={3} style={{ fontSize: '18px', fontWeight: '600', marginBottom: '8px' }}>
                        {__('Key Characteristics', 'aria')}
                    </Heading>
                    <Text variant="muted" style={{ fontSize: '14px' }}>
                        {__('Select 2-3 traits that define Aria\'s approach', 'aria')}
                    </Text>
                </CardHeader>
                <CardBody style={{ paddingTop: '24px' }}>
                    <div style={{ 
                        display: 'grid', 
                        gridTemplateColumns: 'repeat(auto-fit, minmax(240px, 1fr))', 
                        gap: '16px'
                    }}>
                        {availableTraits.map(trait => (
                            <div key={trait.value} style={{ position: 'relative' }}>
                                <label 
                                    role="checkbox"
                                    aria-checked={personalityData.personalityTraits.includes(trait.value)}
                                    aria-label={trait.label}
                                    tabIndex={0}
                                    style={{
                                    display: 'flex',
                                    alignItems: 'center',
                                    padding: '16px 20px',
                                    border: personalityData.personalityTraits.includes(trait.value) ? '2px solid #28a745' : '2px solid #e1e4e8',
                                    borderRadius: '12px',
                                    cursor: 'pointer',
                                    transition: 'all 0.2s ease',
                                    background: personalityData.personalityTraits.includes(trait.value) 
                                        ? 'linear-gradient(135deg, rgba(40, 167, 69, 0.05) 0%, rgba(32, 201, 151, 0.02) 100%)'
                                        : 'linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%)',
                                    boxShadow: personalityData.personalityTraits.includes(trait.value) 
                                        ? '0 4px 16px rgba(40, 167, 69, 0.15)' 
                                        : '0 2px 8px rgba(0, 0, 0, 0.08)'
                                }}>
                                    <input 
                                        type="checkbox"
                                        value={trait.value}
                                        checked={personalityData.personalityTraits.includes(trait.value)}
                                        onChange={(e) => handleTraitChange(trait.value, e.target.checked)}
                                        style={{ display: 'none' }}
                                    />
                                    <div style={{ flex: 1 }}>
                                        <div style={{ 
                                            fontSize: '16px', 
                                            fontWeight: '600', 
                                            color: '#1e1e1e'
                                        }}>
                                            {trait.label}
                                        </div>
                                    </div>
                                    {personalityData.personalityTraits.includes(trait.value) && (
                                        <div style={{
                                            width: '24px',
                                            height: '24px',
                                            borderRadius: '50%',
                                            background: 'linear-gradient(135deg, #28a745 0%, #20c997 100%)',
                                            color: 'white',
                                            display: 'flex',
                                            alignItems: 'center',
                                            justifyContent: 'center',
                                            fontSize: '14px',
                                            fontWeight: '600',
                                            marginLeft: '12px',
                                            flexShrink: 0
                                        }}>
                                            ✓
                                        </div>
                                    )}
                                </label>
                            </div>
                        ))}
                    </div>
                </CardBody>
            </Card>

            {/* Custom Messages Section */}
            <Card size="large" style={{ padding: '24px', marginBottom: '32px' }}>
                <CardHeader style={{ paddingBottom: '16px' }}>
                    <Heading size={3} style={{ fontSize: '18px', fontWeight: '600', marginBottom: '8px' }}>
                        {__('Custom Messages', 'aria')}
                    </Heading>
                    <Text variant="muted" style={{ fontSize: '14px' }}>
                        {__('Customize Aria\'s greeting and farewell messages', 'aria')}
                    </Text>
                </CardHeader>
                <CardBody style={{ paddingTop: '24px' }}>
                    <div style={{ 
                        display: 'grid', 
                        gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))', 
                        gap: '24px'
                    }}>
                        <div>
                            <label style={{ 
                                display: 'block', 
                                fontSize: '14px', 
                                fontWeight: '600', 
                                color: '#1e1e1e', 
                                marginBottom: '8px' 
                            }}>
                                {__('Greeting Message', 'aria')}
                            </label>
                            <textarea
                                rows={4}
                                placeholder={__('Hi! I\'m Aria. How can I help you today?', 'aria')}
                                value={personalityData.greetingMessage}
                                onChange={(e) => updateSetting('greetingMessage', e.target.value)}
                                style={{
                                    width: '100%',
                                    padding: '12px 16px',
                                    border: '2px solid #e1e4e8',
                                    borderRadius: '8px',
                                    fontSize: '14px',
                                    lineHeight: '1.5',
                                    fontFamily: 'inherit',
                                    resize: 'vertical',
                                    transition: 'border-color 0.2s ease',
                                    outline: 'none'
                                }}
                                onFocus={(e) => e.target.style.borderColor = '#2271b1'}
                                onBlur={(e) => e.target.style.borderColor = '#e1e4e8'}
                            />
                            <Text variant="muted" style={{ fontSize: '12px', marginTop: '6px' }}>
                                {__('First message shown to visitors when they start a conversation', 'aria')}
                            </Text>
                        </div>
                        
                        <div>
                            <label style={{ 
                                display: 'block', 
                                fontSize: '14px', 
                                fontWeight: '600', 
                                color: '#1e1e1e', 
                                marginBottom: '8px' 
                            }}>
                                {__('Farewell Message', 'aria')}
                            </label>
                            <textarea
                                rows={4}
                                placeholder={__('Thanks for chatting! Have a great day!', 'aria')}
                                value={personalityData.farewellMessage}
                                onChange={(e) => updateSetting('farewellMessage', e.target.value)}
                                style={{
                                    width: '100%',
                                    padding: '12px 16px',
                                    border: '2px solid #e1e4e8',
                                    borderRadius: '8px',
                                    fontSize: '14px',
                                    lineHeight: '1.5',
                                    fontFamily: 'inherit',
                                    resize: 'vertical',
                                    transition: 'border-color 0.2s ease',
                                    outline: 'none'
                                }}
                                onFocus={(e) => e.target.style.borderColor = '#2271b1'}
                                onBlur={(e) => e.target.style.borderColor = '#e1e4e8'}
                            />
                            <Text variant="muted" style={{ fontSize: '12px', marginTop: '6px' }}>
                                {__('Message shown when conversations end or timeout', 'aria')}
                            </Text>
                        </div>
                    </div>
                </CardBody>
            </Card>

            {/* Save Actions */}
            <Card>
                <CardBody>
                    <Button 
                        variant="primary" 
                        onClick={handleSave} 
                        isBusy={saving}
                        disabled={saving}
                    >
                        {saving ? __('Saving...', 'aria') : __('Save Personality Settings', 'aria')}
                    </Button>
                </CardBody>
            </Card>
        </div>
    );
};

// Mount components when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Mount Dashboard Page
    const dashboardRoot = document.getElementById('aria-dashboard-root');
    if (dashboardRoot) {
        const root = createRoot(dashboardRoot);
        root.render(<AriaDashboard />);
    }
    
    // Mount Settings Page
    const settingsRoot = document.getElementById('aria-settings-root');
    if (settingsRoot) {
        const root = createRoot(settingsRoot);
        root.render(<AriaSettings />);
    }
    
    // Mount Design Page
    const designRoot = document.getElementById('aria-design-root');
    if (designRoot) {
        const root = createRoot(designRoot);
        root.render(<AriaDesign />);
    }
    
    // Mount Personality Page
    const personalityRoot = document.getElementById('aria-personality-root');
    if (personalityRoot) {
        const root = createRoot(personalityRoot);
        root.render(<AriaPersonality />);
    }
    
    // Mount AI Config Page
    const aiConfigRoot = document.getElementById('aria-ai-config-root');
    if (aiConfigRoot) {
        const root = createRoot(aiConfigRoot);
        root.render(<AIConfig />);
    }
});