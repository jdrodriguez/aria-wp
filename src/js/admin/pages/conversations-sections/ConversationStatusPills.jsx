import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';

const ConversationStatusPills = ({ statuses, activeValue, onSelect }) => {
	if (!statuses || statuses.length === 0) {
		return null;
	}

	return (
		<div className="aria-conversations__status-pills">
			<span className="aria-conversations__status-label">
				{__('Quick status filter', 'aria')}
			</span>
			<div className="aria-conversations__status-list">
				{statuses.map((status) => {
					const isActive = activeValue === status.value;
					return (
						<button
							type="button"
							key={status.value}
							className={`aria-conversations__status-pill${
								isActive ? ' aria-conversations__status-pill--active' : ''
							}`}
							onClick={() => onSelect(status.value)}
							aria-pressed={isActive}
						>
							<span>{status.label}</span>
							{typeof status.count === 'number' && (
								<span className="aria-conversations__status-count">
									{status.count}
								</span>
							)}
						</button>
					);
				})}
			</div>
		</div>
	);
};

ConversationStatusPills.propTypes = {
	statuses: PropTypes.arrayOf(
		PropTypes.shape({
			label: PropTypes.string.isRequired,
			value: PropTypes.string.isRequired,
			count: PropTypes.number,
		})
	).isRequired,
	activeValue: PropTypes.string.isRequired,
	onSelect: PropTypes.func.isRequired,
};

export default ConversationStatusPills;
