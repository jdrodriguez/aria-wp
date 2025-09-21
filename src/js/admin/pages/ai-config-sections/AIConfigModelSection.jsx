import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { RangeControl, Notice, Icon } from '@wordpress/components';
import { SectionCard, SelectControl, TextControl } from '../../components';
import { stack, cog } from '@wordpress/icons';

const AIConfigModelSection = ({
	provider,
	currentModel,
	providerModels,
	onChangeModel,
	modelSettings,
	onUpdateModelSettings,
}) => {
	const activeModel = providerModels.find((model) => model.value === currentModel);
	const maxTokensKey = provider === 'openai' ? 'openai_max_tokens' : 'gemini_max_tokens';

	const renderCostNotice = () => {
		if (!activeModel) {
			return null;
		}

		if (activeModel.cost_level === 'high') {
			return (
				<Notice status="warning" isDismissible={false} className="aria-ai-config__model-notice">
					<strong>{__('Cost Warning:', 'aria')}</strong>{' '}
					{__('This model may generate higher usage charges.', 'aria')}
				</Notice>
			);
		}

		if (activeModel.cost_level === 'medium') {
			return (
				<Notice status="info" isDismissible={false} className="aria-ai-config__model-notice">
					{__('Balanced cost and performance option.', 'aria')}
				</Notice>
			);
		}

		if (activeModel.cost_level === 'low') {
			return (
				<Notice status="success" isDismissible={false} className="aria-ai-config__model-notice">
					{__('Cost-efficient model suitable for most use cases.', 'aria')}
				</Notice>
			);
		}

		return null;
	};

	return (
		<SectionCard
			title={__('Model & Response Tuning', 'aria')}
			description={__(
				'Choose the model variant and adjust response behavior to match your brand voice.',
				'aria'
			)}
		>
			<div className="aria-ai-config__form-grid">
				<div className="aria-ai-config__panel">
					<div className="aria-ai-config__panel-heading">
						<span className="aria-ai-config__panel-icon" aria-hidden="true">
							<Icon icon={stack} size={20} />
						</span>
						<div className="aria-ai-config__panel-text">
							<h3>{__('Select Model', 'aria')}</h3>
							<p>{__(
								'Pick the model tier that matches your performance and cost needs.',
								'aria'
							)}</p>
						</div>
					</div>
					<SelectControl
						label={__('Model', 'aria')}
						value={currentModel}
						onChange={(value) => onChangeModel(value)}
						options={providerModels}
					/>

					{activeModel && (
						<div className="aria-ai-config__model-details">
							<p className="aria-ai-config__model-description">{activeModel.description}</p>
							{renderCostNotice()}
						</div>
					)}
				</div>

				<div className="aria-ai-config__panel">
					<div className="aria-ai-config__panel-heading">
						<span className="aria-ai-config__panel-icon" aria-hidden="true">
							<Icon icon={cog} size={20} />
						</span>
						<div className="aria-ai-config__panel-text">
							<h3>{__('Response Controls', 'aria')}</h3>
							<p>{__(
								'Tune response length and creativity so answers stay on-brand.',
								'aria'
							)}</p>
						</div>
					</div>
					<TextControl
						label={__('Max Response Length', 'aria')}
						type="number"
						value={modelSettings[maxTokensKey]}
						onChange={(value) =>
							onUpdateModelSettings(maxTokensKey, parseInt(value, 10) || 0)
						}
						min={50}
						max={2000}
						help={__('Token budget for individual responses (1 token ≈ 4 characters).', 'aria')}
					/>

					{provider === 'openai' && (
						<RangeControl
							label={__('Response Creativity', 'aria')}
							value={modelSettings.openai_temperature}
							onChange={(value) => onUpdateModelSettings('openai_temperature', value)}
							min={0}
							max={1}
							step={0.1}
							help={__('Lower values keep answers focused; higher values encourage creativity.', 'aria')}
						/>
					)}
				</div>
			</div>
		</SectionCard>
	);
};

AIConfigModelSection.propTypes = {
	provider: PropTypes.oneOf(['openai', 'gemini']).isRequired,
	currentModel: PropTypes.string.isRequired,
	providerModels: PropTypes.arrayOf(
		PropTypes.shape({
			value: PropTypes.string.isRequired,
			label: PropTypes.string.isRequired,
			description: PropTypes.string,
			cost_level: PropTypes.oneOf(['low', 'medium', 'high']),
		})
	).isRequired,
	onChangeModel: PropTypes.func.isRequired,
	modelSettings: PropTypes.shape({
		openai_model: PropTypes.string,
		openai_max_tokens: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
		openai_temperature: PropTypes.number,
		gemini_model: PropTypes.string,
		gemini_max_tokens: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
	}).isRequired,
	onUpdateModelSettings: PropTypes.func.isRequired,
};

export default AIConfigModelSection;
