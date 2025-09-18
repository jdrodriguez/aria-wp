import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import ModernSearchFilter from '../../components/ModernSearchFilter.jsx';

const KnowledgeSearchControls = ({
	searchValue,
	onSearchChange,
	filterValue,
	onFilterChange,
	categories,
}) => (
	<div className="aria-knowledge__search">
		<ModernSearchFilter
			searchValue={searchValue}
			onSearchChange={onSearchChange}
			searchPlaceholder={__('Search titles, content, or tags…', 'aria')}
			filterValue={filterValue}
			onFilterChange={onFilterChange}
			filterOptions={[
				{ label: __('All Categories', 'aria'), value: 'all' },
				...categories,
			]}
			filterLabel={__('Filter by Category', 'aria')}
			title={__('Search & Filter', 'aria')}
			description={__('Find specific knowledge entries', 'aria')}
		/>
	</div>
);

KnowledgeSearchControls.propTypes = {
	searchValue: PropTypes.string.isRequired,
	onSearchChange: PropTypes.func.isRequired,
	filterValue: PropTypes.string.isRequired,
	onFilterChange: PropTypes.func.isRequired,
	categories: PropTypes.arrayOf(
		PropTypes.shape({
			label: PropTypes.string.isRequired,
			value: PropTypes.string.isRequired,
		})
	).isRequired,
};

export default KnowledgeSearchControls;
