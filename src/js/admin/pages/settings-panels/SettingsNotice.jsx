import { Notice } from '@wordpress/components';
import PropTypes from 'prop-types';

const SettingsNotice = ({ notice, onRemove }) => {
	if (!notice) {
		return null;
	}

	return (
		<Notice
			status={notice.type}
			isDismissible={true}
			onRemove={onRemove}
			className="aria-settings__notice"
		>
			{notice.message}
		</Notice>
	);
};

SettingsNotice.propTypes = {
	notice: PropTypes.shape({
		type: PropTypes.string.isRequired,
		message: PropTypes.string.isRequired,
	}),
	onRemove: PropTypes.func.isRequired,
};

export default SettingsNotice;
