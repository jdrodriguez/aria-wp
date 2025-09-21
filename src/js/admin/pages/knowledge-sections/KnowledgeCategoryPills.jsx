import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';

const KnowledgeCategoryPills = ({ categories, activeValue, onSelect }) => {
	if (!categories || categories.length === 0) {
		return null;
	}

	return (
		<div className="aria-knowledge__category-pills">
			<span className="aria-knowledge__category-label">
				{__('Quick filters', 'aria')}
			</span>
			<div className="aria-knowledge__category-list">
				{categories.map((category) => {
					const isActive = activeValue === category.value;
					return (
						<button
							type="button"
							key={category.value}
							className={`aria-knowledge__category-pill${
								isActive ? ' aria-knowledge__category-pill--active' : ''
							}`}
							onClick={() => onSelect(category.value)}
							aria-pressed={isActive}
						>
							<span>{category.label}</span>
							{typeof category.count === 'number' && (
								<span className="aria-knowledge__category-count">
									{category.count}
								</span>
							)}
						</button>
					);
				})}
			</div>
		</div>
	);
};

KnowledgeCategoryPills.propTypes = {
	categories: PropTypes.arrayOf(
		PropTypes.shape({
			label: PropTypes.string.isRequired,
			value: PropTypes.string.isRequired,
			count: PropTypes.number,
		})
	).isRequired,
	activeValue: PropTypes.string.isRequired,
	onSelect: PropTypes.func.isRequired,
};

export default KnowledgeCategoryPills;
