import PropTypes from 'prop-types';

/**
 * Standard wrapper that applies consistent padding, max width, and grid spacing
 * across admin pages.
 */
const PageShell = ({ children, className = '', width = 'default', padding = 'lg' }) => {
	const classes = [
		'aria-page-shell',
		`aria-page-shell--width-${width}`,
		`aria-page-shell--padding-${padding}`,
		className,
	]
		.filter(Boolean)
		.join(' ');

	return <div className={classes}>{children}</div>;
};

PageShell.propTypes = {
	children: PropTypes.node.isRequired,
	className: PropTypes.string,
	width: PropTypes.oneOf(['default', 'wide', 'full']),
	padding: PropTypes.oneOf(['none', 'sm', 'md', 'lg']),
};

export default PageShell;
