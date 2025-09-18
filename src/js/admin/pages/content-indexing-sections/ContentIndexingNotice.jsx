import PropTypes from 'prop-types';
import { Notice } from '@wordpress/components';

const ContentIndexingNotice = ({ notice, onRemove }) => {
	if (!notice) {
		return null;
	}

	return (
		<Notice
			className="aria-content-indexing__notice"
			status={notice.type}
			isDismissible={true}
			onRemove={onRemove}
		>
			{notice.message}
		</Notice>
	);
};

ContentIndexingNotice.propTypes = {
	notice: PropTypes.shape({
		type: PropTypes.string.isRequired,
		message: PropTypes.string.isRequired,
	}),
	onRemove: PropTypes.func.isRequired,
};

ContentIndexingNotice.defaultProps = {
	notice: null,
};

export default ContentIndexingNotice;
