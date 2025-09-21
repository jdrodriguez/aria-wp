import React from 'react';
import { render, screen } from '@testing-library/react';
import DashboardMetricsSection from '../../src/js/admin/pages/dashboard-sections/DashboardMetricsSection.jsx';

jest.mock('@wordpress/i18n', () => ({
	__: (text) => text,
}));

describe('DashboardMetricsSection', () => {
	it('renders metric cards with provided values', () => {
		render(
			<DashboardMetricsSection
				conversationsToday={5}
				totalConversations={42}
				knowledgeCount={12}
				licenseLabel="Active"
			/>
		);

		expect(screen.getByText("Today's Activity")).toBeInTheDocument();
		expect(screen.getByText('Total Activity')).toBeInTheDocument();
		expect(screen.getByText('Knowledge Base')).toBeInTheDocument();
		expect(screen.getByText('License Status')).toBeInTheDocument();
		expect(screen.getByText('5')).toBeInTheDocument();
		expect(screen.getByText('42')).toBeInTheDocument();
		expect(screen.getByText('12')).toBeInTheDocument();
		expect(screen.getByText('Active')).toBeInTheDocument();
	});
});
