import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { SectionCard } from '../../components';

const ContentIndexingActions = ({ onStart, isIndexing }) => (
	<SectionCard
		title={__('Indexing Controls', 'aria')}
		description={__(
			'Start manual indexing or review the queue to keep your AI knowledge base fresh.',
			'aria'
		)}
		actions={
			<Button
				variant="primary"
				onClick={onStart}
				isBusy={isIndexing}
				disabled={isIndexing}
			>
				{isIndexing
					? __('Indexing…', 'aria')
					: __('Start Indexing', 'aria')}
			</Button>
		}
	>
		<ul className="aria-content-indexing__actions-list">
			<li>{__('Runs a full crawl of eligible content types', 'aria')}</li>
			<li>
				{__(
					'Updates vector store entries and clears stale items',
					'aria'
				)}
			</li>
			<li>
				{__(
					'Continues in the background if you close this window',
					'aria'
				)}
			</li>
		</ul>
	</SectionCard>
);

ContentIndexingActions.propTypes = {
	onStart: PropTypes.func.isRequired,
	isIndexing: PropTypes.bool.isRequired,
};

export default ContentIndexingActions;
