import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { Button, Spinner } from '@wordpress/components';
import { SectionCard, TextareaControl } from '../../components';
import { MagicWandIcon } from './icons.jsx';

const AiGenerationWizard = ({
	step,
	rawContent,
	onRawContentChange,
	onGenerate,
	onCancel,
	onSwitchToManual,
	generating,
}) => {
	if (step === 'generating') {
		return (
			<SectionCard title={__('Preparing your content', 'aria')}>
				<div className="aria-knowledge-entry__wizard-progress">
					<Spinner className="aria-knowledge-entry__wizard-spinner" />
					<h3 className="aria-knowledge-entry__wizard-heading">
						{__('AI is analyzing your content…', 'aria')}
					</h3>
					<p className="aria-knowledge-entry__wizard-copy">
						{__(
							'Our AI is structuring your content into a comprehensive knowledge entry. This may take a moment.',
							'aria'
						)}
					</p>
				</div>
			</SectionCard>
		);
	}

	if (step !== 'input') {
		return null;
	}

	return (
		<SectionCard
			title={__('AI-powered knowledge generation', 'aria')}
			description={__(
				'Paste raw content (emails, documents, notes, FAQs) and let Aria organise it into a structured entry.',
				'aria'
			)}
		>
			<TextareaControl
				label={__('Raw content', 'aria')}
				value={rawContent}
				onChange={onRawContentChange}
				rows={12}
				placeholder={__(
					'Include customer emails, documentation, notes, or policies to give the AI plenty of context.',
					'aria'
				)}
				help={__(
					'The more context you provide, the better the AI can structure your knowledge entry.',
					'aria'
				)}
			/>

			<div className="aria-knowledge-entry__divider" />

			<div className="aria-knowledge-entry__wizard-actions">
				<Button
					variant="tertiary"
					onClick={onSwitchToManual}
					className="aria-knowledge-entry__wizard-link"
				>
					{__('Create manually instead', 'aria')}
				</Button>
				<div className="aria-knowledge-entry__wizard-buttons">
					<Button variant="secondary" onClick={onCancel}>
						{__('Cancel', 'aria')}
					</Button>
					<Button
						className="aria-knowledge-entry__generate-button"
						variant="primary"
						onClick={onGenerate}
						disabled={generating || !rawContent.trim()}
					>
						{generating ? (
							<>
								<Spinner className="aria-knowledge-entry__generate-spinner" />
								{__('Generating…', 'aria')}
							</>
						) : (
							<>
								<MagicWandIcon />
								{__('Generate with AI', 'aria')}
							</>
						)}
					</Button>
				</div>
			</div>
		</SectionCard>
	);
};

AiGenerationWizard.propTypes = {
	step: PropTypes.string.isRequired,
	rawContent: PropTypes.string.isRequired,
	onRawContentChange: PropTypes.func.isRequired,
	onGenerate: PropTypes.func.isRequired,
	onCancel: PropTypes.func.isRequired,
	onSwitchToManual: PropTypes.func.isRequired,
	generating: PropTypes.bool.isRequired,
};

export default AiGenerationWizard;
