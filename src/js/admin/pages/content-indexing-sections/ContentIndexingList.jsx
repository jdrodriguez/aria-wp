import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { Button, Icon } from '@wordpress/components';
import { SectionCard, ResultsSummary } from '../../components';
import ContentIndexingItemCard from './ContentIndexingItemCard.jsx';
import { file as fileIcon } from '@wordpress/icons';

const ContentIndexingList = ({
	items,
	onToggleIndex,
	onViewContent,
	totalCount,
	filteredCount,
	hasFiltersApplied,
	onStartIndexing,
	isIndexing,
}) => {
	const description = __(
		'Manage which content is available to Aria’s knowledge base.',
		'aria'
	);

	return (
		<SectionCard
			title={__('Content Items', 'aria')}
			description={description}
		>
			{totalCount > 0 && (
				<ResultsSummary
					totalCount={totalCount}
					filteredCount={filteredCount}
					isFiltered={hasFiltersApplied}
					label={__('content items', 'aria')}
				/>
			)}
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
					<div
						role="presentation"
						aria-hidden="true"
						className="aria-content-indexing__empty-icon"
					>
						<Icon icon={fileIcon} size={36} />
					</div>
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
	totalCount: PropTypes.number.isRequired,
	filteredCount: PropTypes.number.isRequired,
	hasFiltersApplied: PropTypes.bool.isRequired,
	onStartIndexing: PropTypes.func.isRequired,
	isIndexing: PropTypes.bool.isRequired,
};

export default ContentIndexingList;
