import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { SectionCard } from '../../components';

const DesignPreviewSection = ({ title, welcomeMessage, colors, iconUrl, avatarUrl }) => {
	const previewTitle = title || __('Chat with us', 'aria');
	const previewMessage =
		welcomeMessage || __('Hi! How can I help you today?', 'aria');
	const avatarInitial = previewTitle.trim().charAt(0).toUpperCase() || 'A';

	const frameStyles = {
		'--aria-design-preview-bg': colors.backgroundColor,
		'--aria-design-preview-border': colors.primaryColor,
		'--aria-design-preview-text': colors.textColor,
		'--aria-design-preview-button': colors.primaryColor,
	};

	return (
		<SectionCard
			title={__('Live Preview', 'aria')}
			description={__(
				'See a quick snapshot of your widget styling before publishing.',
				'aria'
			)}
		>
			<div className="aria-design__preview">
				<div className="aria-design__preview-frame" style={frameStyles}>
					<header className="aria-design__preview-header">
						{iconUrl ? (
							<img
								src={iconUrl}
								alt={__('Widget icon preview', 'aria')}
								className="aria-design__preview-icon"
							/>
						) : null}
						<span>{previewTitle}</span>
					</header>
					<div className="aria-design__preview-message-row">
						{avatarUrl ? (
							<img
								src={avatarUrl}
								alt={__('Avatar preview', 'aria')}
								className="aria-design__preview-avatar"
							/>
						) : (
							<div className="aria-design__preview-avatar aria-design__preview-avatar--placeholder">
								{avatarInitial}
							</div>
						)}
						<p className="aria-design__preview-message">
							{previewMessage}
						</p>
					</div>
					<footer className="aria-design__preview-footer">
						<span className="aria-design__preview-button">
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
		iconUrl: PropTypes.string,
		avatarUrl: PropTypes.string,
	};

	DesignPreviewSection.defaultProps = {
		title: '',
		welcomeMessage: '',
		iconUrl: '',
		avatarUrl: '',
	};

export default DesignPreviewSection;
