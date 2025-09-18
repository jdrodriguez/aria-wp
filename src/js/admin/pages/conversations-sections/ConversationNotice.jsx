import PropTypes from 'prop-types';
import { Notice } from '@wordpress/components';

const ConversationNotice = ({ notice, onRemove }) => {
	if (!notice) {
		return null;
	}

	return (
		<Notice
			className="aria-conversations__notice"
			status={notice.type}
			isDismissible={true}
			onRemove={onRemove}
		>
			{notice.message}
		</Notice>
	);
};

ConversationNotice.propTypes = {
	notice: PropTypes.shape({
		type: PropTypes.string.isRequired,
		message: PropTypes.string.isRequired,
	}),
	onRemove: PropTypes.func.isRequired,
};

ConversationNotice.defaultProps = {
	notice: null,
};

export default ConversationNotice;
