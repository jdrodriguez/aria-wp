import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { SectionCard } from '../../components';

const PersonalityTraitsSection = ({
	traits,
	selectedTraits,
	onToggleTrait,
}) => {
	const renderTrait = (trait) => {
		const isSelected = selectedTraits.includes(trait.value);

		return (
			<li key={trait.value}>
				<button
					type="button"
					className={`aria-personality__trait${isSelected ? ' is-selected' : ''}`}
					onClick={() => onToggleTrait(trait.value)}
				>
					<span className="aria-personality__trait-label">
						{trait.label}
					</span>
					<span
						className="aria-personality__trait-status"
						aria-hidden="true"
					>
						{isSelected ? '✓' : '+'}
					</span>
				</button>
			</li>
		);
	};

	return (
		<SectionCard
			title={__('Key Characteristics', 'aria')}
			description={__(
				"Select 2-3 traits that define Aria's approach.",
				'aria'
			)}
		>
			<ul className="aria-personality__trait-list">
				{traits.map((trait) => renderTrait(trait))}
			</ul>
		</SectionCard>
	);
};

PersonalityTraitsSection.propTypes = {
	traits: PropTypes.arrayOf(
		PropTypes.shape({
			label: PropTypes.string.isRequired,
			value: PropTypes.string.isRequired,
		})
	).isRequired,
	selectedTraits: PropTypes.arrayOf(PropTypes.string).isRequired,
	onToggleTrait: PropTypes.func.isRequired,
};

export default PersonalityTraitsSection;
