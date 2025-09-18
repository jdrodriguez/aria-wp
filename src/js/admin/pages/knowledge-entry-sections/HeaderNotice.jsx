import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';
import { PageHeader } from '../../components';

const HeaderNotice = ({ action, notice, onRemove }) => {
	const isEdit = action === 'edit';
	const title = isEdit
		? __('Edit Knowledge Entry', 'aria')
		: __('Create Knowledge Entry', 'aria');
	const description = isEdit
		? __(
				'Update the content Aria references when answering related questions.',
				'aria'
			)
		: __(
				'Use the AI assistant or manual editor to add new knowledge for Aria.',
				'aria'
			);

	return (
		<div className="aria-stack-lg">
			<PageHeader title={title} description={description} />
			{notice && (
				<Notice
					className="aria-knowledge-entry__notice"
					status={notice.type}
					isDismissible={true}
					onRemove={onRemove}
				>
					{notice.message}
				</Notice>
			)}
		</div>
	);
};

HeaderNotice.propTypes = {
	action: PropTypes.string.isRequired,
	notice: PropTypes.shape({
		type: PropTypes.string.isRequired,
		message: PropTypes.string.isRequired,
	}),
	onRemove: PropTypes.func.isRequired,
};

HeaderNotice.defaultProps = {
	notice: null,
};

export default HeaderNotice;
