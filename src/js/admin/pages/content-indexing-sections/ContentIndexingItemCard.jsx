import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { Button, Icon } from '@wordpress/components';
import { ToggleControl } from '../../components';
import { file as fileIcon, calendar, typography, link as linkIcon } from '@wordpress/icons';

const statusClassMap = {
	indexed: 'is-indexed',
	pending: 'is-pending',
	failed: 'is-failed',
	excluded: 'is-excluded',
};

const ContentIndexingItemCard = ({ item, onToggleIndex, onViewContent }) => {
	const statusClass = statusClassMap[item.status] || 'is-pending';

	return (
		<div className="aria-content-indexing__item">
			<div className="aria-content-indexing__item-main">
				<header className="aria-content-indexing__item-header">
					<h4 className="aria-content-indexing__item-title">
						{item.title}
					</h4>
					<span
						className={`aria-content-indexing__item-status ${statusClass}`}
					>
						{item.status.charAt(0).toUpperCase() +
							item.status.slice(1)}
					</span>
				</header>
				<div className="aria-content-indexing__item-meta">
					<span className="aria-content-indexing__item-meta-chip">
						<Icon icon={fileIcon} size={14} /> {item.typeLabel || item.type}
					</span>
					<span className="aria-content-indexing__item-meta-chip">
						<Icon icon={calendar} size={14} /> {item.updated_at}
					</span>
					<span className="aria-content-indexing__item-meta-chip">
						<Icon icon={typography} size={14} /> {item.word_count} {__('words', 'aria')}
					</span>
					<span className="aria-content-indexing__item-meta-chip">
						<Icon icon={linkIcon} size={14} /> {item.url}
					</span>
				</div>
				<p className="aria-content-indexing__item-excerpt">
					{item.excerpt}
				</p>
				{item.tags && item.tags.length > 0 && (
					<div className="aria-content-indexing__item-tags">
						{item.tags.map((tag, index) => (
							<span
								key={`${tag}-${index}`}
								className="aria-content-indexing__item-tag"
							>
								{tag}
							</span>
						))}
					</div>
				)}
			</div>
			<div className="aria-content-indexing__item-actions">
				<Button
					variant="secondary"
					size="small"
					onClick={() => onViewContent(item)}
				>
					{__('View', 'aria')}
				</Button>
				<ToggleControl
					label={
						item.status === 'indexed'
							? __('Indexed', 'aria')
							: __('Include', 'aria')
					}
					checked={item.status === 'indexed'}
					onChange={() => onToggleIndex(item.id)}
				/>
			</div>
		</div>
	);
};

ContentIndexingItemCard.propTypes = {
	item: PropTypes.shape({
		id: PropTypes.oneOfType([PropTypes.string, PropTypes.number])
			.isRequired,
		title: PropTypes.string.isRequired,
				type: PropTypes.string.isRequired,
			typeLabel: PropTypes.string,
		status: PropTypes.string.isRequired,
		url: PropTypes.string.isRequired,
		updated_at: PropTypes.string.isRequired,
		word_count: PropTypes.oneOfType([PropTypes.string, PropTypes.number])
			.isRequired,
		excerpt: PropTypes.string.isRequired,
		tags: PropTypes.arrayOf(PropTypes.string),
	}).isRequired,
	onToggleIndex: PropTypes.func.isRequired,
	onViewContent: PropTypes.func.isRequired,
};

export default ContentIndexingItemCard;
