import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { SectionCard } from '../../components';
import { SparklesIcon } from './icons.jsx';

const getContentPreview = (content) => {
	if (!content) {
		return '';
	}

	const plain = content.replace(/<[^>]+>/g, '').replace(/\s+/g, ' ').trim();
	return plain.length > 260 ? `${plain.slice(0, 260)}…` : plain;
};

const AiGenerationSummary = ({ visible, formData }) => {
	if (!visible) {
		return null;
	}

	const tagList = typeof formData.tags === 'string'
		? formData.tags
			.split(',')
			.map((tag) => tag.trim())
			.filter(Boolean)
		: Array.isArray(formData.tags)
			? formData.tags.filter(Boolean)
			: [];

	const contentPreview = getContentPreview(formData.content);

	return (
		<SectionCard
			title={__('AI generated draft', 'aria')}
			description={__(
				'Review the snapshot below. Everything stays editable in the form that follows.',
				'aria'
			)}
			className="aria-knowledge-entry__summary-card"
		>
			<div className="aria-knowledge-entry__summary-header">
				<span className="aria-knowledge-entry__summary-icon" aria-hidden="true">
					<SparklesIcon />
				</span>
				<strong>{__('AI suggestion ready for review', 'aria')}</strong>
			</div>

			<div className="aria-knowledge-entry__summary-grid">
				<div className="aria-knowledge-entry__summary-item">
					<span className="aria-knowledge-entry__summary-label">
						{__('Title', 'aria')}
					</span>
					<h3 className="aria-knowledge-entry__summary-title">{formData.title}</h3>
				</div>

				<div className="aria-knowledge-entry__summary-item">
					<span className="aria-knowledge-entry__summary-label">
						{__('Category', 'aria')}
					</span>
					<p className="aria-knowledge-entry__summary-value">
						{formData.category || __('Not set', 'aria')}
					</p>

					{tagList.length > 0 && (
						<div className="aria-knowledge-entry__summary-tags">
							{tagList.map((tag) => (
								<span key={tag} className="aria-knowledge-entry__summary-tag">
									{tag}
								</span>
							))}
						</div>
					)}
				</div>

				<div className="aria-knowledge-entry__summary-item aria-knowledge-entry__summary-item--full">
					<span className="aria-knowledge-entry__summary-label">
						{__('Content preview', 'aria')}
					</span>
					<p className="aria-knowledge-entry__summary-content">
						{contentPreview || __('The AI did not generate content for this entry yet.', 'aria')}
					</p>
				</div>

				<div className="aria-knowledge-entry__summary-item">
					<span className="aria-knowledge-entry__summary-label">
						{__('Context cues', 'aria')}
					</span>
					<p className="aria-knowledge-entry__summary-value">
						{formData.context || __('No context provided', 'aria')}
					</p>
				</div>

				<div className="aria-knowledge-entry__summary-item">
					<span className="aria-knowledge-entry__summary-label">
						{__('Response guidance', 'aria')}
					</span>
					<p className="aria-knowledge-entry__summary-value">
						{formData.response_instructions || __('No guidance provided', 'aria')}
					</p>
				</div>
			</div>
		</SectionCard>
	);
};

AiGenerationSummary.propTypes = {
	visible: PropTypes.bool.isRequired,
	formData: PropTypes.shape({
		title: PropTypes.string,
		category: PropTypes.string,
		tags: PropTypes.oneOfType([
			PropTypes.string,
			PropTypes.arrayOf(PropTypes.string),
		]),
		content: PropTypes.string,
		context: PropTypes.string,
		response_instructions: PropTypes.string,
	}).isRequired,
};

export default AiGenerationSummary;
