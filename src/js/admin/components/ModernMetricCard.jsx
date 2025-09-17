import PropTypes from 'prop-types';

/**
 * SVG Icons for metrics
 */
const Icons = {
	activity: (
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
			<polyline points="22,12 18,12 15,21 9,3 6,12 2,12"></polyline>
		</svg>
	),
	users: (
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
			<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
			<circle cx="9" cy="7" r="4"></circle>
			<path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
			<path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
		</svg>
	),
	knowledge: (
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
			<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
			<path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
		</svg>
	),
	license: (
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
			<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
			<circle cx="12" cy="16" r="1"></circle>
			<path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
		</svg>
	)
};

/**
 * Modern metric card component with gradients and professional styling
 *
 * @param {Object} props               - Component props
 * @param {string} props.icon          - Icon type (activity, users, knowledge, license)
 * @param {string} props.title         - Card title
 * @param {string|number} props.value  - Main metric value
 * @param {string} props.subtitle      - Subtitle text
 * @param {string} props.theme         - Color theme (primary, success, warning, info)
 * @return {JSX.Element} ModernMetricCard component
 */
const ModernMetricCard = ({ icon, title, value, subtitle, theme = 'primary' }) => {
	const getThemeStyles = () => {
		switch (theme) {
			case 'success':
				return {
					background: 'linear-gradient(135deg, #f0f9ff 0%, #e0f7fa 100%)',
					borderColor: '#10b981',
					iconBg: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
					valueColor: '#10b981',
					shadowColor: 'rgba(16, 185, 129, 0.15)'
				};
			case 'warning':
				return {
					background: 'linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%)',
					borderColor: '#f59e0b',
					iconBg: 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
					valueColor: '#f59e0b',
					shadowColor: 'rgba(245, 158, 11, 0.15)'
				};
			case 'info':
				return {
					background: 'linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%)',
					borderColor: '#3b82f6',
					iconBg: 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)',
					valueColor: '#3b82f6',
					shadowColor: 'rgba(59, 130, 246, 0.15)'
				};
			default:
				return {
					background: 'linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%)',
					borderColor: '#2271b1',
					iconBg: 'linear-gradient(135deg, #2271b1 0%, #1e40af 100%)',
					valueColor: '#2271b1',
					shadowColor: 'rgba(34, 113, 177, 0.15)'
				};
		}
	};

	const themeStyles = getThemeStyles();
	const iconComponent = Icons[icon] || Icons.activity;

	return (
		<div
			style={{
				background: themeStyles.background,
				border: `1px solid ${themeStyles.borderColor}20`,
				borderRadius: '16px',
				padding: '24px',
				display: 'flex',
				alignItems: 'center',
				gap: '20px',
				boxShadow: `0 4px 16px ${themeStyles.shadowColor}, 0 2px 4px rgba(0, 0, 0, 0.06)`,
				transition: 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
				cursor: 'default',
				position: 'relative',
				overflow: 'hidden',
			}}
			onMouseEnter={(e) => {
				e.currentTarget.style.transform = 'translateY(-2px)';
				e.currentTarget.style.boxShadow = `0 8px 32px ${themeStyles.shadowColor}, 0 4px 8px rgba(0, 0, 0, 0.1)`;
			}}
			onMouseLeave={(e) => {
				e.currentTarget.style.transform = 'translateY(0)';
				e.currentTarget.style.boxShadow = `0 4px 16px ${themeStyles.shadowColor}, 0 2px 4px rgba(0, 0, 0, 0.06)`;
			}}
		>
			{/* Subtle background pattern */}
			<div
				style={{
					position: 'absolute',
					top: 0,
					right: 0,
					width: '100px',
					height: '100px',
					background: `radial-gradient(circle, ${themeStyles.borderColor}08 0%, transparent 70%)`,
					borderRadius: '50%',
					transform: 'translate(30%, -30%)',
				}}
			/>

			{/* Icon Container */}
			<div
				style={{
					width: '56px',
					height: '56px',
					borderRadius: '16px',
					background: themeStyles.iconBg,
					display: 'flex',
					alignItems: 'center',
					justifyContent: 'center',
					color: 'white',
					flexShrink: 0,
					boxShadow: `0 4px 12px ${themeStyles.shadowColor}`,
				}}
			>
				{iconComponent}
			</div>

			{/* Content */}
			<div style={{ flex: 1, minWidth: 0 }}>
				<div
					style={{
						fontSize: '14px',
						fontWeight: '600',
						color: '#64748b',
						marginBottom: '4px',
						textTransform: 'uppercase',
						letterSpacing: '0.05em',
					}}
				>
					{title}
				</div>
				<div
					style={{
						fontSize: '32px',
						fontWeight: '800',
						color: themeStyles.valueColor,
						lineHeight: '1.2',
						marginBottom: '2px',
					}}
				>
					{value}
				</div>
				<div
					style={{
						fontSize: '13px',
						color: '#64748b',
						fontWeight: '500',
					}}
				>
					{subtitle}
				</div>
			</div>
		</div>
	);
};

ModernMetricCard.propTypes = {
	icon: PropTypes.oneOf(['activity', 'users', 'knowledge', 'license']).isRequired,
	title: PropTypes.string.isRequired,
	value: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
	subtitle: PropTypes.string.isRequired,
	theme: PropTypes.oneOf(['primary', 'success', 'warning', 'info']),
};

export default ModernMetricCard;