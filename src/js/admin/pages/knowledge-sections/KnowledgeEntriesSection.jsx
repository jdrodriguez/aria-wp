import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { SectionCard } from '../../components';
import ModernKnowledgeEntryCard from '../../components/ModernKnowledgeEntryCard.jsx';

const KnowledgeEntriesSection = ({
	entries,
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
				<span
					role="img"
					aria-hidden="true"
					className="aria-knowledge__empty-icon"
				>
					📚
				</span>
				<p>
					{__(
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
			category: PropTypes.string,
			categoryLabel: PropTypes.string,
			tags: PropTypes.arrayOf(PropTypes.string),
			updated_at: PropTypes.string,
		})
	).isRequired,
	onAddEntry: PropTypes.func.isRequired,
	onEditEntry: PropTypes.func.isRequired,
	onDeleteEntry: PropTypes.func.isRequired,
};

export default KnowledgeEntriesSection;
