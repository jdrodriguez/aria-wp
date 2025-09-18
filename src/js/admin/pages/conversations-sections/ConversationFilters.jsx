import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { SectionCard, SearchControl, SelectControl } from '../../components';

const ConversationFilters = ({
	searchValue,
	onSearchChange,
	statusValue,
	onStatusChange,
	statusOptions,
	sourceValue,
	onSourceChange,
	sourceOptions,
}) => (
	<SectionCard
		title={__('Filter Conversations', 'aria')}
		description={__(
			'Narrow down conversations by status, source, or keywords to find the context you need quickly.',
			'aria'
		)}
	>
		<div className="aria-conversations__filters">
			<SearchControl
				value={searchValue}
				onChange={onSearchChange}
				label={__('Search conversations', 'aria')}
				placeholder={__(
					'Search by visitor, email, or message…',
					'aria'
				)}
			/>
			<SelectControl
				label={__('Status', 'aria')}
				value={statusValue}
				onChange={onStatusChange}
				options={statusOptions}
			/>
			<SelectControl
				label={__('Source', 'aria')}
				value={sourceValue}
				onChange={onSourceChange}
				options={sourceOptions}
			/>
		</div>
	</SectionCard>
);

ConversationFilters.propTypes = {
	searchValue: PropTypes.string.isRequired,
	onSearchChange: PropTypes.func.isRequired,
	statusValue: PropTypes.string.isRequired,
	onStatusChange: PropTypes.func.isRequired,
	statusOptions: PropTypes.arrayOf(PropTypes.object).isRequired,
	sourceValue: PropTypes.string.isRequired,
	onSourceChange: PropTypes.func.isRequired,
	sourceOptions: PropTypes.arrayOf(PropTypes.object).isRequired,
};

export default ConversationFilters;
