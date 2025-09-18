import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';

/**
 * Reusable page header component with Card-based design
 * @param {Object} props               - Component props
 * @param {string} props.title         - The page title
 * @param {string} [props.description] - Optional description text
 * @param {*}      [props.children]    - Optional children elements
 * @param {string} [props.className]   - Optional CSS class name
 * @return {JSX.Element} PageHeader component
 */
const PageHeader = ({ title, description, children, className = '' }) => {
	const pluginUrl =
		typeof window !== 'undefined' && window.ariaAdmin
			? window.ariaAdmin.pluginUrl
			: '';
	const logoSrc = pluginUrl
		? `${pluginUrl}assets/images/wordmark.png`
		: null;

	return (
		<header className={`aria-page-header-card ${className}`.trim()}>
			<div className="aria-page-header-card__layout">
				{logoSrc && (
					<div className="aria-page-header-card__brand" aria-hidden="true">
						<img
							className="aria-page-header-card__logo"
							src={logoSrc}
							alt={__('ARIA', 'aria')}
							loading="lazy"
						/>
					</div>
				)}

				<div className="aria-page-header-card__content">
					<h1 className="aria-page-header-card__title">{title}</h1>
					{description && (
						<p className="aria-page-header-card__description">{description}</p>
					)}
					{children && (
						<div className="aria-page-header-card__actions">{children}</div>
					)}
				</div>
			</div>
		</header>
	);
};

PageHeader.propTypes = {
	title: PropTypes.string.isRequired,
	description: PropTypes.string,
	children: PropTypes.node,
	className: PropTypes.string,
};

export default PageHeader;
