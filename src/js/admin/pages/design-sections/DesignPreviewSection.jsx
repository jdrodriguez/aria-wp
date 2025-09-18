import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { SectionCard } from '../../components';

const DesignPreviewSection = ({ title, welcomeMessage, colors }) => {
	const previewTitle = title || __('Chat with us', 'aria');
	const previewMessage =
		welcomeMessage || __('Hi! How can I help you today?', 'aria');

	return (
		<SectionCard
			title={__('Live Preview', 'aria')}
			description={__(
				'See a quick snapshot of your widget styling before publishing.',
				'aria'
			)}
		>
			<div className="aria-design__preview">
				<div
					className="aria-design__preview-frame"
					style={{
						backgroundColor: colors.backgroundColor,
						borderColor: colors.primaryColor,
					}}
				>
					<header
						className="aria-design__preview-header"
						style={{ color: colors.textColor }}
					>
						{previewTitle}
					</header>
					<p
						className="aria-design__preview-message"
						style={{ color: colors.textColor }}
					>
						{previewMessage}
					</p>
					<footer className="aria-design__preview-footer">
						<span
							className="aria-design__preview-button"
							style={{ backgroundColor: colors.primaryColor }}
						>
							{__('Send', 'aria')}
						</span>
					</footer>
				</div>
				<p className="aria-design__preview-note">
					{__(
						'This is a simplified preview. Actual widget styling may vary slightly within your theme.',
						'aria'
					)}
				</p>
			</div>
		</SectionCard>
	);
};

DesignPreviewSection.propTypes = {
	title: PropTypes.string,
	welcomeMessage: PropTypes.string,
	colors: PropTypes.shape({
		primaryColor: PropTypes.string.isRequired,
		backgroundColor: PropTypes.string.isRequired,
		textColor: PropTypes.string.isRequired,
	}).isRequired,
};

DesignPreviewSection.defaultProps = {
	title: '',
	welcomeMessage: '',
};

export default DesignPreviewSection;
