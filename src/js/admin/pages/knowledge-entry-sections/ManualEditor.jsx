import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { SectionCard, TextControl, TextareaControl, SelectControl } from '../../components';
import { SparklesIcon } from './icons.jsx';

const LANG_OPTIONS = [
	{ label: __('English', 'aria'), value: 'en' },
	{ label: __('Spanish', 'aria'), value: 'es' },
	{ label: __('French', 'aria'), value: 'fr' },
	{ label: __('German', 'aria'), value: 'de' },
	{ label: __('Italian', 'aria'), value: 'it' },
];

const ManualEditor = ({
	visible,
	formData,
	onFieldChange,
	categories,
	generationStep,
}) => {
	if (!visible) {
		return null;
	}

	return (
		<div className="aria-knowledge-entry__editor">
			{generationStep === 'review' && (
				<div className="aria-knowledge-entry__review-banner">
					<div className="aria-knowledge-entry__review-heading">
						<SparklesIcon />
						<strong>{__('AI generation complete!', 'aria')}</strong>
					</div>
					<p className="aria-knowledge-entry__review-text">
						{__(
							'Review the generated fields below and make any adjustments needed. The AI has proposed categories, tags, and formatting for you.',
							'aria'
						)}
					</p>
				</div>
			)}

			<div className="aria-knowledge-entry__panel-grid">
				<SectionCard title={__('Primary information', 'aria')}>
					<div className="aria-knowledge-entry__field-grid">
						<TextControl
							label={__('Title', 'aria')}
							value={formData.title}
							onChange={(value) => onFieldChange('title', value)}
							placeholder={__(
								'Enter a clear, descriptive title',
								'aria'
							)}
							help={__(
								'This is shown in the knowledge list and search results.',
								'aria'
							)}
						/>

						<SelectControl
							label={__('Category', 'aria')}
							value={formData.category}
							onChange={(value) =>
								onFieldChange('category', value)
							}
							options={categories}
							help={__(
								'Choose the most appropriate grouping for this entry.',
								'aria'
							)}
						/>

						<TextareaControl
							label={__('Content', 'aria')}
							value={formData.content}
							onChange={(value) =>
								onFieldChange('content', value)
							}
							rows={8}
							placeholder={__(
								'Enter the main knowledge content…',
								'aria'
							)}
							help={__(
								'This is what Aria uses when answering related questions.',
								'aria'
							)}
						/>
					</div>
				</SectionCard>

				<SectionCard title={__('AI behaviour & context', 'aria')}>
					<div className="aria-knowledge-entry__field-grid">
						<TextareaControl
							label={__('Context', 'aria')}
							value={formData.context}
							onChange={(value) =>
								onFieldChange('context', value)
							}
							rows={3}
							placeholder={__(
								'When should Aria reference this entry? Describe scenarios or customer questions…',
								'aria'
							)}
							help={__(
								'Helps Aria decide when to surface this knowledge.',
								'aria'
							)}
						/>

						<TextareaControl
							label={__('Response instructions', 'aria')}
							value={formData.response_instructions}
							onChange={(value) =>
								onFieldChange('response_instructions', value)
							}
							rows={3}
							placeholder={__(
								'Outline tone preferences, caveats, or formatting requirements for responses…',
								'aria'
							)}
							help={__(
								'Guide how Aria should present this information to customers.',
								'aria'
							)}
						/>
					</div>
				</SectionCard>

				<SectionCard title={__('Organisation & discovery', 'aria')}>
					<div className="aria-knowledge-entry__field-grid">
						<TextControl
							label={__('Tags', 'aria')}
							value={formData.tags}
							onChange={(value) => onFieldChange('tags', value)}
							placeholder={__(
								'Enter keywords separated by commas',
								'aria'
							)}
							help={__(
								'Tags help Aria locate this entry during search and retrieval.',
								'aria'
							)}
						/>

						<SelectControl
							label={__('Language', 'aria')}
							value={formData.language}
							onChange={(value) =>
								onFieldChange('language', value)
							}
							options={LANG_OPTIONS}
						/>
					</div>
				</SectionCard>
			</div>
		</div>
	);
};

ManualEditor.propTypes = {
	visible: PropTypes.bool.isRequired,
	formData: PropTypes.shape({
		title: PropTypes.string.isRequired,
		content: PropTypes.string.isRequired,
		category: PropTypes.string.isRequired,
		tags: PropTypes.string.isRequired,
		context: PropTypes.string.isRequired,
		response_instructions: PropTypes.string.isRequired,
		language: PropTypes.string.isRequired,
	}).isRequired,
	onFieldChange: PropTypes.func.isRequired,
	categories: PropTypes.arrayOf(PropTypes.object).isRequired,
	generationStep: PropTypes.string.isRequired,
};

export default ManualEditor;
