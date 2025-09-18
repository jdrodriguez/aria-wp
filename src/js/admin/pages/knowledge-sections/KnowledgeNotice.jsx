import PropTypes from 'prop-types';
import { Notice } from '@wordpress/components';

const KnowledgeNotice = ({ notice, onRemove }) => {
	if (!notice) {
		return null;
	}

	return (
		<Notice
			className="aria-knowledge__notice"
			status={notice.type}
			isDismissible={true}
			onRemove={onRemove}
		>
			{notice.message}
		</Notice>
	);
};

KnowledgeNotice.propTypes = {
	notice: PropTypes.shape({
		type: PropTypes.string.isRequired,
		message: PropTypes.string.isRequired,
	}),
	onRemove: PropTypes.func.isRequired,
};

KnowledgeNotice.defaultProps = {
	notice: null,
};

export default KnowledgeNotice;
