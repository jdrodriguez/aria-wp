import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { Button, Icon } from '@wordpress/components';
import { SectionCard } from '../../components';
import ModernKnowledgeEntryCard from '../../components/ModernKnowledgeEntryCard.jsx';
import { tableOfContents } from '@wordpress/icons';
import { ResultsSummary } from '../../components';

const KnowledgeEntriesSection = ({
	entries,
	totalCount,
	filteredCount,
	isFiltered,
	onAddEntry,
	onEditEntry,
	onDeleteEntry,
}) => (
	<SectionCard
		title={__('Knowledge Entries', 'aria')}
		description={__(
			'Manage the information Aria can reference in conversations.',
			'aria'
		)}
		actions={
			<Button variant="primary" onClick={onAddEntry}>
				{__('Add New Entry', 'aria')}
			</Button>
		}
	>
		{totalCount > 0 && (
			<ResultsSummary
				totalCount={totalCount}
				filteredCount={filteredCount}
				isFiltered={isFiltered}
				label={__('knowledge entries', 'aria')}
			/>
		)}
		{entries.length > 0 ? (
			<div className="aria-knowledge__entries-list">
				{entries.map((entry) => (
					<ModernKnowledgeEntryCard
						key={entry.id}
						entry={entry}
						onEdit={onEditEntry}
						onDelete={onDeleteEntry}
					/>
				))}
			</div>
		) : (
			<div className="aria-knowledge__empty">
				<div className="aria-knowledge__empty-icon" aria-hidden="true">
					<Icon icon={tableOfContents} size={36} />
				</div>
				<p>
					{isFiltered
						? __(
							'No knowledge entries match your filters. Adjust your search or category to see more results.',
							'aria'
						)
						: __(
							'No knowledge entries yet. Start by adding your key business information.',
							'aria'
						)}
				</p>
				<Button variant="primary" onClick={onAddEntry}>
					{__('Add Knowledge Entry', 'aria')}
				</Button>
			</div>
		)}
	</SectionCard>
);

KnowledgeEntriesSection.propTypes = {
	entries: PropTypes.arrayOf(
		PropTypes.shape({
			id: PropTypes.oneOfType([PropTypes.string, PropTypes.number])
				.isRequired,
			title: PropTypes.string.isRequired,
			content: PropTypes.string.isRequired,
			fullContent: PropTypes.string,
			category: PropTypes.string,
			categoryLabel: PropTypes.string,
			tags: PropTypes.arrayOf(PropTypes.string),
			updated_at: PropTypes.string,
			status: PropTypes.string,
			totalChunks: PropTypes.number,
		})
	).isRequired,
	totalCount: PropTypes.number.isRequired,
	filteredCount: PropTypes.number.isRequired,
	isFiltered: PropTypes.bool.isRequired,
	onAddEntry: PropTypes.func.isRequired,
	onEditEntry: PropTypes.func.isRequired,
	onDeleteEntry: PropTypes.func.isRequired,
};

export default KnowledgeEntriesSection;
