import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { SectionCard, TextareaControl } from '../../components';

const PersonalityMessagesSection = ({
	greetingMessage,
	onGreetingChange,
	farewellMessage,
	onFarewellChange,
}) => (
	<SectionCard
		title={__('Custom Messages', 'aria')}
		description={__(
			"Customize Aria's greeting and farewell messages.",
			'aria'
		)}
	>
		<div className="aria-grid-two-column">
			<TextareaControl
				label={__('Greeting Message', 'aria')}
				value={greetingMessage}
				onChange={onGreetingChange}
				placeholder={__(
					"Hi! I'm Aria. How can I help you today?",
					'aria'
				)}
				rows={5}
			/>
			<TextareaControl
				label={__('Farewell Message', 'aria')}
				value={farewellMessage}
				onChange={onFarewellChange}
				placeholder={__(
					"Thanks for chatting! I'll follow up soon with the details we've discussed.",
					'aria'
				)}
				rows={5}
			/>
		</div>
	</SectionCard>
);

PersonalityMessagesSection.propTypes = {
	greetingMessage: PropTypes.string.isRequired,
	onGreetingChange: PropTypes.func.isRequired,
	farewellMessage: PropTypes.string.isRequired,
	onFarewellChange: PropTypes.func.isRequired,
};

export default PersonalityMessagesSection;
