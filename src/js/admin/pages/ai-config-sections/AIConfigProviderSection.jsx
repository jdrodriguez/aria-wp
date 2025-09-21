import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { Button, Icon } from '@wordpress/components';
import { SectionCard, SelectControl, TextControl } from '../../components';
import { plugins, lock } from '@wordpress/icons';

const AIConfigProviderSection = ({
	provider,
	onChangeProvider,
	apiKey,
	onChangeApiKey,
	showApiKey,
	onToggleShowApiKey,
	onTestApiKey,
	onTestSavedKey,
	isTesting,
	testingKeyType,
	currentApiKey,
	apiKeyHelp,
}) => {
	const disableNewKeyTest =
		!apiKey ||
		apiKey.includes('*') ||
		(isTesting && testingKeyType === 'saved');
	const isTestingNewKey = isTesting && testingKeyType === 'new';
	const isTestingSavedKey = isTesting && testingKeyType === 'saved';

	return (
		<SectionCard
			title={__('AI Provider & Authentication', 'aria')}
			description={__(
				'Select your AI provider and manage API key credentials for secure access.',
				'aria'
			)}
		>
			<div className="aria-ai-config__grid">
			<div className="aria-ai-config__panel">
				<div className="aria-ai-config__panel-heading">
					<span className="aria-ai-config__panel-icon" aria-hidden="true">
						<Icon icon={plugins} size={20} />
					</span>
					<div className="aria-ai-config__panel-text">
						<h3>{__('Provider Selection', 'aria')}</h3>
						<p>{__('Choose which AI service powers Aria and manage its credentials.', 'aria')}</p>
					</div>
				</div>
				<SelectControl
						label={__('Provider', 'aria')}
						value={provider}
						onChange={onChangeProvider}
						options={[
							{ value: 'openai', label: __('OpenAI (ChatGPT)', 'aria') },
							{ value: 'gemini', label: __('Google Gemini', 'aria') },
						]}
					/>
				</div>

			<div className="aria-ai-config__panel">
				<div className="aria-ai-config__panel-heading">
					<span className="aria-ai-config__panel-icon" aria-hidden="true">
						<Icon icon={lock} size={20} />
					</span>
					<div className="aria-ai-config__panel-text">
						<h3>{__('API Credentials', 'aria')}</h3>
						<p>{__('Store and test the API key used to authenticate with your AI provider.', 'aria')}</p>
					</div>
				</div>
					<TextControl
						type={showApiKey ? 'text' : 'password'}
						label={__('API Key', 'aria')}
						value={apiKey}
						placeholder={
							currentApiKey
								? __('Enter new API key (leave blank to keep current)', 'aria')
								: __('Enter your API key', 'aria')
						}
						onChange={onChangeApiKey}
					/>

					{currentApiKey && (
						<p className="aria-ai-config__masked-key">
							{__('Currently saved key:', 'aria')}{' '}
							<strong>{currentApiKey}</strong>
						</p>
					)}

					<div className="aria-ai-config__field-actions">
						<Button
							variant="secondary"
							onClick={onToggleShowApiKey}
						>
							{showApiKey ? __('Hide Key', 'aria') : __('Show Key', 'aria')}
						</Button>
						<Button
							variant="primary"
							onClick={onTestApiKey}
							isBusy={isTestingNewKey}
							disabled={disableNewKeyTest}
						>
							{__('Test New Key', 'aria')}
						</Button>
						{currentApiKey && (
							<Button
								variant="secondary"
								onClick={onTestSavedKey}
								isBusy={isTestingSavedKey}
								disabled={isTesting && testingKeyType === 'new'}
							>
								{__('Test Saved Key', 'aria')}
							</Button>
						)}
					</div>

					<div className="aria-ai-config__help-text">{apiKeyHelp}</div>
				</div>
			</div>
		</SectionCard>
	);
};

AIConfigProviderSection.propTypes = {
	provider: PropTypes.oneOf(['openai', 'gemini']).isRequired,
	onChangeProvider: PropTypes.func.isRequired,
	apiKey: PropTypes.string.isRequired,
	onChangeApiKey: PropTypes.func.isRequired,
	showApiKey: PropTypes.bool.isRequired,
	onToggleShowApiKey: PropTypes.func.isRequired,
	onTestApiKey: PropTypes.func.isRequired,
	onTestSavedKey: PropTypes.func,
	isTesting: PropTypes.bool.isRequired,
	testingKeyType: PropTypes.oneOf(['new', 'saved']),
	currentApiKey: PropTypes.string,
	apiKeyHelp: PropTypes.node.isRequired,
};

AIConfigProviderSection.defaultProps = {
	onTestSavedKey: undefined,
	currentApiKey: '',
	testingKeyType: null,
};

export default AIConfigProviderSection;
