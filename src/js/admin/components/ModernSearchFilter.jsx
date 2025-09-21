import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import SearchInput from './SearchInput.jsx';
import { SelectControl } from './WPControls.jsx';

const SearchIcon = ({ size = 20 }) => (
	<svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
		<circle cx="11" cy="11" r="8"></circle>
		<path d="m21 21-4.35-4.35"></path>
	</svg>
);

const FilterIcon = ({ size = 20 }) => (
	<svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
		<polygon points="22,3 2,3 10,12.46 10,19 14,21 14,12.46"></polygon>
	</svg>
);

const ModernSearchFilter = ({
	searchValue,
	onSearchChange,
	searchPlaceholder = __('Search...', 'aria'),
	filterValue,
	onFilterChange,
	filterOptions = [],
	filterLabel = __('Filter', 'aria'),
	title = __('Search & Filter', 'aria'),
	description = __('Find specific entries', 'aria'),
}) => (
	<div className="aria-search-filter">
		<div className="aria-search-filter__pattern" aria-hidden="true" />

		<div className="aria-search-filter__header">
			<div className="aria-search-filter__header-icon">
				<SearchIcon />
			</div>
			<div className="aria-search-filter__header-text">
				<h3 className="aria-search-filter__title">{title}</h3>
				<p className="aria-search-filter__description">{description}</p>
			</div>
		</div>

		<div className="aria-search-filter__grid">
			<div className="aria-search-filter__field">
				<span className="aria-search-filter__label">{__('Search Knowledge', 'aria')}</span>
				<SearchInput
					value={searchValue}
					onChange={onSearchChange}
					placeholder={searchPlaceholder}
					size="large"
					className="aria-search-filter__search-input"
				/>
			</div>

			<div className="aria-search-filter__field">
				<span className="aria-search-filter__label">{filterLabel}</span>
				<div className="aria-search-filter__select">
					<div className="aria-search-filter__select-header">
						<div className="aria-search-filter__select-icon">
							<FilterIcon />
						</div>
						<span className="aria-search-filter__select-title">{__('Filter By', 'aria')}</span>
					</div>
					<div className="aria-search-filter__select-body">
						<SelectControl
							value={filterValue}
							onChange={onFilterChange}
							options={filterOptions}
						/>
					</div>
				</div>
			</div>
		</div>

		{searchValue && (
			<div className="aria-search-filter__summary">
				<span className="aria-search-filter__summary-icon">
					<SearchIcon size={12} />
				</span>
				<span>
					{__('Active search:', 'aria')} “{searchValue}”
				</span>
			</div>
		)}
	</div>
);

ModernSearchFilter.propTypes = {
	searchValue: PropTypes.string.isRequired,
	onSearchChange: PropTypes.func.isRequired,
	searchPlaceholder: PropTypes.string,
	filterValue: PropTypes.string.isRequired,
	onFilterChange: PropTypes.func.isRequired,
	filterOptions: PropTypes.array.isRequired,
	filterLabel: PropTypes.string,
	title: PropTypes.string,
	description: PropTypes.string,
};

export default ModernSearchFilter;
