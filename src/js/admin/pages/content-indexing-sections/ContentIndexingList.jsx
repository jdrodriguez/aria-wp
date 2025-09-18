import PropTypes from 'prop-types';
import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { SectionCard } from '../../components';
import ContentIndexingItemCard from './ContentIndexingItemCard.jsx';

const ContentIndexingList = ({
	items,
	onToggleIndex,
	onViewContent,
	count,
	hasFiltersApplied,
	onStartIndexing,
	isIndexing,
}) => {
	const description = __(
		'Manage which content is available to Aria’s knowledge base.',
		'aria'
	);
	const formattedDescription = sprintf(
		/* translators: 1: base description text, 2: content item count */
		__('%1$s (%2$s)', 'aria'),
		description,
		count
	);

	return (
		<SectionCard
			title={__('Content Items', 'aria')}
			description={formattedDescription}
		>
			{items.length > 0 ? (
				<div className="aria-content-indexing__list">
					{items.map((item) => (
						<ContentIndexingItemCard
							key={item.id}
							item={item}
							onToggleIndex={onToggleIndex}
							onViewContent={onViewContent}
						/>
					))}
				</div>
			) : (
				<div className="aria-content-indexing__empty">
					<span
						role="img"
						aria-hidden="true"
						className="aria-content-indexing__empty-icon"
					>
						📄
					</span>
					<p>
						{hasFiltersApplied
							? __(
									'No content items match your filters. Update the search or clear filters to try again.',
									'aria'
								)
							: __(
									'No content items indexed yet. Start indexing to populate this list.',
									'aria'
								)}
					</p>
					{!hasFiltersApplied && (
						<Button
							variant="primary"
							onClick={onStartIndexing}
							isBusy={isIndexing}
							disabled={isIndexing}
						>
							{isIndexing
								? __('Indexing…', 'aria')
								: __('Start Indexing', 'aria')}
						</Button>
					)}
				</div>
			)}
		</SectionCard>
	);
};

ContentIndexingList.propTypes = {
	items: PropTypes.arrayOf(PropTypes.object).isRequired,
	onToggleIndex: PropTypes.func.isRequired,
	onViewContent: PropTypes.func.isRequired,
	count: PropTypes.number.isRequired,
	hasFiltersApplied: PropTypes.bool.isRequired,
	onStartIndexing: PropTypes.func.isRequired,
	isIndexing: PropTypes.bool.isRequired,
};

export default ContentIndexingList;
