import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { SearchControl, SelectControl } from '@wordpress/components';
import { SectionCard } from '../../components';

const ContentIndexingFilters = ({
	searchValue,
	onSearchChange,
	typeValue,
	onTypeChange,
	typeOptions,
	statusValue,
	onStatusChange,
	statusOptions,
}) => (
	<SectionCard
		title={__('Search & Filter', 'aria')}
		description={__(
			'Locate specific content by keywords, type, or indexing status.',
			'aria'
		)}
	>
		<div className="aria-content-indexing__filters">
			<SearchControl
				label={__('Search content', 'aria')}
				value={searchValue}
				onChange={onSearchChange}
				placeholder={__('Search titles, content, or tags…', 'aria')}
			/>
			<SelectControl
				label={__('Filter by type', 'aria')}
				value={typeValue}
				onChange={onTypeChange}
				options={typeOptions}
			/>
			<SelectControl
				label={__('Filter by status', 'aria')}
				value={statusValue}
				onChange={onStatusChange}
				options={statusOptions}
			/>
		</div>
	</SectionCard>
);

ContentIndexingFilters.propTypes = {
	searchValue: PropTypes.string.isRequired,
	onSearchChange: PropTypes.func.isRequired,
	typeValue: PropTypes.string.isRequired,
	onTypeChange: PropTypes.func.isRequired,
	typeOptions: PropTypes.arrayOf(PropTypes.object).isRequired,
	statusValue: PropTypes.string.isRequired,
	onStatusChange: PropTypes.func.isRequired,
	statusOptions: PropTypes.arrayOf(PropTypes.object).isRequired,
};

export default ContentIndexingFilters;
