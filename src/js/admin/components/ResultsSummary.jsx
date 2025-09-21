import PropTypes from 'prop-types';
import { __, sprintf } from '@wordpress/i18n';

const ResultsSummary = ({ totalCount, filteredCount, isFiltered, label = __('entries', 'aria') }) => {
	if (typeof totalCount !== 'number' || totalCount < 0) {
		return null;
	}

	const summaryText = isFiltered
		? sprintf(
			/* translators: 1: number of filtered items, 2: total items, 3: item label */
			__('Showing %1$d of %2$d %3$s', 'aria'),
			filteredCount,
			totalCount,
			label
		)
		: sprintf(
			/* translators: 1: total items, 2: item label */
			__('Showing %1$d %2$s', 'aria'),
			totalCount,
			label
		);

	return <div className="aria-results-summary">{summaryText}</div>;
};

ResultsSummary.propTypes = {
	totalCount: PropTypes.number.isRequired,
	filteredCount: PropTypes.number.isRequired,
	isFiltered: PropTypes.bool,
	label: PropTypes.string,
};

ResultsSummary.defaultProps = {
	isFiltered: false,
	label: __('entries', 'aria'),
};

export default ResultsSummary;
