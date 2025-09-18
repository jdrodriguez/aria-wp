import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import PropTypes from 'prop-types';

const categoryStyles = {
	general: { bg: '#f0f6fc', color: '#0969da', border: '#d1d9e0' },
	products: { bg: '#f0f9ff', color: '#0284c7', border: '#bae6fd' },
	support: { bg: '#f0fdf4', color: '#059669', border: '#bbf7d0' },
	company: { bg: '#fef3c7', color: '#d97706', border: '#fde68a' },
	policies: { bg: '#fdf2f8', color: '#be185d', border: '#fce7f3' },
	default: { bg: '#f8fafc', color: '#64748b', border: '#e2e8f0' },
};

const formatStatusLabel = (status = '') =>
	status
		.replace(/_/g, ' ')
		.replace(/\b\w/g, (char) => char.toUpperCase());

const ModernKnowledgeEntryCard = ({ entry, onEdit, onDelete }) => {
	const formatDate = (dateString) => new Date(dateString).toLocaleDateString();
	const styles = categoryStyles[entry.category] || categoryStyles.default;
	const status = entry.status || 'pending_processing';
	const statusLabel = formatStatusLabel(status);

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
				<span
					className="modern-knowledge-card__category"
					style={{
						backgroundColor: styles.bg,
						color: styles.color,
						borderColor: styles.border,
					}}
				>
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
