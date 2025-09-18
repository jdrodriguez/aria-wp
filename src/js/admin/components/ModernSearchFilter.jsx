import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import SearchInput from './SearchInput.jsx';
import { SelectControl } from './WPControls.jsx';

/**
 * SVG Search Icon
 */
const SearchIcon = () => (
	<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
		<circle cx="11" cy="11" r="8"></circle>
		<path d="m21 21-4.35-4.35"></path>
	</svg>
);

/**
 * SVG Filter Icon
 */
const FilterIcon = () => (
	<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
		<polygon points="22,3 2,3 10,12.46 10,19 14,21 14,12.46"></polygon>
	</svg>
);

/**
 * Modern search and filter component with gradient styling
 *
 * @param {Object} props                    - Component props
 * @param {string} props.searchValue        - Current search value
 * @param {Function} props.onSearchChange   - Search change handler
 * @param {string} props.searchPlaceholder  - Search input placeholder
 * @param {string} props.filterValue        - Current filter value
 * @param {Function} props.onFilterChange   - Filter change handler
 * @param {Array} props.filterOptions       - Filter select options
 * @param {string} props.filterLabel        - Filter select label
 * @param {string} props.title              - Section title
 * @param {string} props.description        - Section description
 * @return {JSX.Element} ModernSearchFilter component
 */
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
}) => {
	return (
		<div
			style={{
				background: 'linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%)',
				border: '1px solid #e2e8f0',
				borderRadius: '16px',
				padding: '24px',
				marginBottom: '32px',
				boxShadow: '0 4px 16px rgba(0, 0, 0, 0.04), 0 2px 4px rgba(0, 0, 0, 0.06)',
				position: 'relative',
				overflow: 'hidden',
			}}
		>
			{/* Subtle background pattern */}
			<div
				style={{
					position: 'absolute',
					top: 0,
					right: 0,
					width: '100px',
					height: '100px',
					background: 'radial-gradient(circle, rgba(59, 130, 246, 0.03) 0%, transparent 70%)',
					borderRadius: '50%',
					transform: 'translate(30%, -30%)',
				}}
			/>

			{/* Header */}
			<div style={{ marginBottom: '24px', position: 'relative', zIndex: 1 }}>
				<div style={{ display: 'flex', alignItems: 'center', gap: '12px', marginBottom: '8px' }}>
					<div
						style={{
							width: '40px',
							height: '40px',
							borderRadius: '12px',
							background: 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)',
							display: 'flex',
							alignItems: 'center',
							justifyContent: 'center',
							color: 'white',
							flexShrink: 0,
							boxShadow: '0 4px 12px rgba(59, 130, 246, 0.25)',
						}}
					>
						<SearchIcon />
					</div>
					<div>
						<h3
							style={{
								fontSize: '18px',
								fontWeight: '700',
								margin: 0,
								color: '#1e293b',
								lineHeight: '1.4',
							}}
						>
							{title}
						</h3>
						<p
							style={{
								fontSize: '14px',
								color: '#64748b',
								margin: 0,
								fontWeight: '500',
							}}
						>
							{description}
						</p>
					</div>
				</div>
			</div>

			{/* Search and Filter Controls */}
			<div
				style={{
					display: 'grid',
					gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))',
					gap: '20px',
					position: 'relative',
					zIndex: 1,
				}}
			>
				{/* Search Input Container */}
				<div>
					<label
						style={{
							display: 'block',
							fontSize: '14px',
							fontWeight: '600',
							marginBottom: '8px',
							color: '#374151',
							textTransform: 'uppercase',
							letterSpacing: '0.05em',
						}}
					>
						{__('Search Knowledge', 'aria')}
					</label>
					<div
						style={{
							position: 'relative',
							background: 'white',
							borderRadius: '12px',
							border: '1px solid #d1d5db',
							boxShadow: '0 2px 4px rgba(0, 0, 0, 0.06)',
							transition: 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
						}}
						onMouseEnter={(e) => {
							e.currentTarget.style.borderColor = '#3b82f6';
							e.currentTarget.style.boxShadow = '0 4px 12px rgba(59, 130, 246, 0.15), 0 0 0 3px rgba(59, 130, 246, 0.1)';
						}}
						onMouseLeave={(e) => {
							if (document.activeElement !== e.currentTarget.querySelector('input')) {
								e.currentTarget.style.borderColor = '#d1d5db';
								e.currentTarget.style.boxShadow = '0 2px 4px rgba(0, 0, 0, 0.06)';
							}
						}}
					>
						<SearchInput
							value={searchValue}
							onChange={onSearchChange}
							placeholder={searchPlaceholder}
							debounceMs={300}
							showClearBtn={true}
							size="medium"
							style={{
								border: 'none',
								borderRadius: '12px',
								padding: '12px 16px',
								fontSize: '15px',
								backgroundColor: 'transparent',
								outline: 'none',
								width: '100%',
							}}
						/>
					</div>
				</div>

				{/* Filter Select Container */}
				<div>
					<label
						style={{
							display: 'block',
							fontSize: '14px',
							fontWeight: '600',
							marginBottom: '8px',
							color: '#374151',
							textTransform: 'uppercase',
							letterSpacing: '0.05em',
						}}
					>
						<div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
							<FilterIcon />
							{filterLabel}
						</div>
					</label>
					<div
						style={{
							background: 'white',
							borderRadius: '12px',
							border: '1px solid #d1d5db',
							boxShadow: '0 2px 4px rgba(0, 0, 0, 0.06)',
							transition: 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
						}}
						onMouseEnter={(e) => {
							e.currentTarget.style.borderColor = '#3b82f6';
							e.currentTarget.style.boxShadow = '0 4px 12px rgba(59, 130, 246, 0.15), 0 0 0 3px rgba(59, 130, 246, 0.1)';
						}}
						onMouseLeave={(e) => {
							e.currentTarget.style.borderColor = '#d1d5db';
							e.currentTarget.style.boxShadow = '0 2px 4px rgba(0, 0, 0, 0.06)';
						}}
					>
						<SelectControl
							value={filterValue}
							options={filterOptions}
							onChange={onFilterChange}
							style={{
								border: 'none',
								borderRadius: '12px',
								padding: '12px 16px',
								fontSize: '15px',
								backgroundColor: 'transparent',
								outline: 'none',
								width: '100%',
								minHeight: '48px',
							}}
							__nextHasNoMarginBottom
						/>
					</div>
				</div>
			</div>

			{/* Results Summary */}
			{searchValue && (
				<div
					style={{
						marginTop: '16px',
						padding: '12px 16px',
						background: 'linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%)',
						border: '1px solid #bfdbfe',
						borderRadius: '10px',
						position: 'relative',
						zIndex: 1,
					}}
				>
					<div
						style={{
							fontSize: '13px',
							color: '#1e40af',
							fontWeight: '500',
							display: 'flex',
							alignItems: 'center',
							gap: '8px',
						}}
					>
						<div
							style={{
								width: '16px',
								height: '16px',
								borderRadius: '50%',
								background: '#3b82f6',
								display: 'flex',
								alignItems: 'center',
								justifyContent: 'center',
								color: 'white',
								fontSize: '10px',
								fontWeight: '700',
							}}
						>
							🔍
						</div>
						{__('Active search:', 'aria')} "{searchValue}"
					</div>
				</div>
			)}
		</div>
	);
};

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
