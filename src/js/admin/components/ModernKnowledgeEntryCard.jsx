import { __ } from '@wordpress/i18n';
import { Button, Flex } from '@wordpress/components';
import PropTypes from 'prop-types';

/**
 * Modern knowledge entry card component with enhanced styling
 *
 * @param {Object} props           - Component props
 * @param {Object} props.entry     - Knowledge entry data
 * @param {Function} props.onEdit  - Edit handler function
 * @param {Function} props.onDelete - Delete handler function
 * @return {JSX.Element} ModernKnowledgeEntryCard component
 */
const ModernKnowledgeEntryCard = ({ entry, onEdit, onDelete }) => {
	const formatDate = (dateString) => {
		return new Date(dateString).toLocaleDateString();
	};

	const getCategoryColor = (category) => {
		switch (category) {
			case 'general':
				return { bg: '#f0f6fc', color: '#0969da', border: '#d1d9e0' };
			case 'products':
				return { bg: '#f0f9ff', color: '#0284c7', border: '#bae6fd' };
			case 'support':
				return { bg: '#f0fdf4', color: '#059669', border: '#bbf7d0' };
			case 'company':
				return { bg: '#fef3c7', color: '#d97706', border: '#fde68a' };
			case 'policies':
				return { bg: '#fdf2f8', color: '#be185d', border: '#fce7f3' };
			default:
				return { bg: '#f8fafc', color: '#64748b', border: '#e2e8f0' };
		}
	};

	const categoryColors = getCategoryColor(entry.category);

	return (
		<div
			className="modern-knowledge-card"
			style={{
				background: 'linear-gradient(135deg, #ffffff 0%, #f8fafc 100%)',
				border: '1px solid #e2e8f0',
				borderRadius: '16px',
				padding: '24px',
				marginBottom: '16px',
				boxShadow: '0 2px 8px rgba(0, 0, 0, 0.06), 0 1px 3px rgba(0, 0, 0, 0.1)',
				transition: 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
				position: 'relative',
				overflow: 'hidden',
			}}
		>
			{/* Subtle background pattern */}
			<div
				style={{
					position: 'absolute',
					top: 0,
					right: 0,
					width: '120px',
					height: '120px',
					background: 'radial-gradient(circle, rgba(59, 130, 246, 0.03) 0%, transparent 70%)',
					borderRadius: '50%',
					transform: 'translate(40%, -40%)',
				}}
			/>

			<Flex justify="space-between" align="flex-start">
				<div style={{ flex: 1, minWidth: 0 }}>
					{/* Title */}
					<h4
						style={{
							fontSize: '18px',
							fontWeight: '700',
							margin: '0 0 12px 0',
							color: '#1e293b',
							lineHeight: '1.4',
						}}
					>
						{entry.title}
					</h4>

					{/* Category and Date */}
					<div style={{ display: 'flex', alignItems: 'center', gap: '12px', marginBottom: '16px' }}>
						<span
							style={{
								fontSize: '12px',
								padding: '4px 12px',
								backgroundColor: categoryColors.bg,
								color: categoryColors.color,
								borderRadius: '20px',
								border: `1px solid ${categoryColors.border}`,
								fontWeight: '600',
								textTransform: 'uppercase',
								letterSpacing: '0.05em',
							}}
						>
							{entry.categoryLabel}
						</span>
						<span
							style={{
								fontSize: '13px',
								color: '#64748b',
								fontWeight: '500',
							}}
						>
							{__('Updated', 'aria')} {formatDate(entry.updated_at)}
						</span>
					</div>

					{/* Content Preview */}
					<p
						style={{
							fontSize: '15px',
							color: '#334155',
							margin: '0 0 16px 0',
							overflow: 'hidden',
							textOverflow: 'ellipsis',
							display: '-webkit-box',
							WebkitLineClamp: 3,
							WebkitBoxOrient: 'vertical',
							lineHeight: '1.6',
						}}
					>
						{entry.content}
					</p>

					{/* Tags */}
					{entry.tags && entry.tags.length > 0 && (
						<div style={{ display: 'flex', gap: '8px', flexWrap: 'wrap' }}>
							{entry.tags.map((tag, index) => (
								<span
									key={index}
									style={{
										fontSize: '12px',
										padding: '4px 10px',
										backgroundColor: '#f1f5f9',
										color: '#475569',
										borderRadius: '14px',
										border: '1px solid #e2e8f0',
										fontWeight: '500',
									}}
								>
									#{tag}
								</span>
							))}
						</div>
					)}
				</div>

				{/* Action Buttons */}
				<div style={{ 
					display: 'flex', 
					gap: '8px', 
					marginLeft: '24px', 
					position: 'relative', 
					zIndex: 10,
					pointerEvents: 'auto'
				}}>
					<Button
						variant="secondary"
						size="small"
						onClick={() => onEdit(entry)}
						style={{
							borderRadius: '8px',
							fontWeight: '500',
							minHeight: '36px',
							paddingLeft: '16px',
							paddingRight: '16px',
							position: 'relative',
							zIndex: 10,
							pointerEvents: 'auto',
							cursor: 'pointer',
						}}
					>
						{__('Edit', 'aria')}
					</Button>
					<Button
						variant="secondary"
						size="small"
						onClick={() => onDelete(entry.id)}
						style={{
							borderRadius: '8px',
							fontWeight: '500',
							minHeight: '36px',
							paddingLeft: '16px',
							paddingRight: '16px',
							color: '#dc2626',
							borderColor: '#fca5a5',
							backgroundColor: '#fef2f2',
							position: 'relative',
							zIndex: 10,
							pointerEvents: 'auto',
							cursor: 'pointer',
						}}
					>
						{__('Delete', 'aria')}
					</Button>
				</div>
			</Flex>
		</div>
	);
};

ModernKnowledgeEntryCard.propTypes = {
	entry: PropTypes.object.isRequired,
	onEdit: PropTypes.func.isRequired,
	onDelete: PropTypes.func.isRequired,
};

export default ModernKnowledgeEntryCard;