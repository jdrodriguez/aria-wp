import PropTypes from 'prop-types';
import { Notice } from '@wordpress/components';

const AIConfigNotice = ({ notice, onRemove }) => {
	if (!notice) {
		return null;
	}

	return (
		<Notice
			status={notice.type}
			onRemove={onRemove}
			isDismissible
			className="aria-ai-config__notice"
		>
			{notice.message}
		</Notice>
	);
};

AIConfigNotice.propTypes = {
	notice: PropTypes.shape({
		type: PropTypes.oneOf(['info', 'success', 'warning', 'error']).isRequired,
		message: PropTypes.string.isRequired,
	}),
	onRemove: PropTypes.func.isRequired,
};

export default AIConfigNotice;
