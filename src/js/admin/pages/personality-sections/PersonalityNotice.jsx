import PropTypes from 'prop-types';
import { Notice } from '@wordpress/components';

const PersonalityNotice = ({ notice, onRemove }) => {
	if (!notice) {
		return null;
	}

	return (
		<Notice
			className="aria-personality__notice"
			status={notice.type}
			isDismissible={true}
			onRemove={onRemove}
		>
			{notice.message}
		</Notice>
	);
};

PersonalityNotice.propTypes = {
	notice: PropTypes.shape({
		type: PropTypes.string.isRequired,
		message: PropTypes.string.isRequired,
	}),
	onRemove: PropTypes.func.isRequired,
};

PersonalityNotice.defaultProps = {
	notice: null,
};

export default PersonalityNotice;
