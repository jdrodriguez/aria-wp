import { __ } from '@wordpress/i18n';
import { TabPanel } from '@wordpress/components';
import PropTypes from 'prop-types';
import { PageHeader, PageShell } from '../components';
import {
	GeneralSettingsPanel,
	NotificationSettingsPanel,
	AdvancedSettingsPanel,
	PrivacySettingsPanel,
	LicenseSettingsPanel,
} from './settings-panels';

const TAB_COMPONENTS = {
	general: GeneralSettingsPanel,
	notifications: NotificationSettingsPanel,
	advanced: AdvancedSettingsPanel,
	privacy: PrivacySettingsPanel,
	license: LicenseSettingsPanel,
};

const SettingsTabContent = ({ tabName }) => {
	const Component = TAB_COMPONENTS[tabName] || TAB_COMPONENTS.general;

	return <Component />;
};

SettingsTabContent.propTypes = {
	tabName: PropTypes.string.isRequired,
};

const Settings = () => {
	const tabs = [
		{ name: 'general', title: __('General', 'aria') },
		{ name: 'notifications', title: __('Notifications', 'aria') },
		{ name: 'advanced', title: __('Advanced', 'aria') },
		{ name: 'privacy', title: __('Privacy & GDPR', 'aria') },
		{ name: 'license', title: __('License', 'aria') },
	];

	return (
		<PageShell className="aria-settings aria-settings-react" width="wide">
			<PageHeader
				title={__('Settings', 'aria')}
				description={__(
					'Configure how Aria behaves and interacts with your visitors',
					'aria'
				)}
			/>
			<TabPanel
				className="aria-settings__tab-panel"
				activeClass="active-tab"
				tabs={tabs}
				initialTabName="general"
			>
				{(tab) => <SettingsTabContent tabName={tab.name} />}
			</TabPanel>
		</PageShell>
	);
};

export default Settings;
