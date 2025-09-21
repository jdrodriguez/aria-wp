import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import PropTypes from 'prop-types';

const CATEGORY_CLASS_MAP = {
	general: 'modern-knowledge-card__category--general',
	products: 'modern-knowledge-card__category--products',
	support: 'modern-knowledge-card__category--support',
	company: 'modern-knowledge-card__category--company',
	policies: 'modern-knowledge-card__category--policies',
};

const formatStatusLabel = (status = '') =>
	status
		.replace(/_/g, ' ')
		.replace(/\b\w/g, (char) => char.toUpperCase());

const ModernKnowledgeEntryCard = ({ entry, onEdit, onDelete }) => {
	const formatDate = (dateString) => new Date(dateString).toLocaleDateString();
	const status = entry.status || 'pending_processing';
	const statusLabel = formatStatusLabel(status);
	const categoryClass = CATEGORY_CLASS_MAP[entry.category] || 'modern-knowledge-card__category--default';

	const chunkMeta =
		typeof entry.totalChunks === 'number'
			? sprintf(__('Chunks: %d', 'aria'), entry.totalChunks)
			: null;

	return (
		<div className="modern-knowledge-card">
			<div className="modern-knowledge-card__pattern" aria-hidden="true" />
			<div className="modern-knowledge-card__header">
				<div className="modern-knowledge-card__text">
					<h4 className="modern-knowledge-card__title">{entry.title}</h4>
				</div>
			</div>

			<div className="modern-knowledge-card__meta">
				<span className={`modern-knowledge-card__category ${categoryClass}`}>
					{entry.categoryLabel}
				</span>
				<span className="modern-knowledge-card__date">
					{__('Updated', 'aria')} {formatDate(entry.updated_at)}
				</span>
				<span
					className={`modern-knowledge-card__status modern-knowledge-card__status--${status}`}
				>
					{statusLabel}
				</span>
				{chunkMeta && (
					<span className="modern-knowledge-card__chunks">{chunkMeta}</span>
				)}
			</div>

			<p className="modern-knowledge-card__content">{entry.content}</p>

			{entry.tags && entry.tags.length > 0 && (
				<div className="modern-knowledge-card__tags">
					{entry.tags.map((tag, index) => (
						<span key={index} className="modern-knowledge-card__tag">
							#{tag}
						</span>
					))}
				</div>
			)}

			<div className="modern-knowledge-card__actions">
				<Button variant="secondary" size="small" onClick={() => onEdit(entry)}>
					{__('Edit', 'aria')}
				</Button>
				<Button
					variant="secondary"
					size="small"
					onClick={() => onDelete(entry.id)}
					className="modern-knowledge-card__button--delete"
				>
					{__('Delete', 'aria')}
				</Button>
			</div>
		</div>
	);
};

ModernKnowledgeEntryCard.propTypes = {
	entry: PropTypes.shape({
		id: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
		title: PropTypes.string.isRequired,
		content: PropTypes.string.isRequired,
		fullContent: PropTypes.string,
		category: PropTypes.string,
		categoryLabel: PropTypes.string,
		tags: PropTypes.arrayOf(PropTypes.string),
		updated_at: PropTypes.string,
		status: PropTypes.string,
		totalChunks: PropTypes.number,
	}).isRequired,
	onEdit: PropTypes.func.isRequired,
	onDelete: PropTypes.func.isRequired,
};

export default ModernKnowledgeEntryCard;
