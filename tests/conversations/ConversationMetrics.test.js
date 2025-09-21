import React from 'react';
import { render, screen } from '@testing-library/react';
import ConversationMetrics from '../../src/js/admin/pages/conversations-sections/ConversationMetrics.jsx';

jest.mock('@wordpress/i18n', () => ({
	__: (text) => text,
}));

describe('ConversationMetrics', () => {
	it('renders all conversation metric cards', () => {
		render(
			<ConversationMetrics
				metrics={{
					totalConversations: {
						icon: 'chat',
						title: 'Total Conversations',
						value: 128,
						subtitle: 'All Time',
						theme: 'primary',
					},
					activeConversations: {
						icon: 'activity',
						title: 'Active Conversations',
						value: 7,
						subtitle: 'Currently Active',
						theme: 'success',
					},
					avgResponseTime: {
						icon: 'clock',
						title: 'Avg Response Time',
						value: '12s',
						subtitle: 'AI Response Speed',
						theme: 'info',
					},
					satisfactionRate: {
						icon: 'smile',
						title: 'Satisfaction Rate',
						value: '92%',
						subtitle: 'Visitor Satisfaction',
						theme: 'warning',
					},
				}}
			/>
		);

		expect(screen.getByText('Total Conversations')).toBeInTheDocument();
		expect(screen.getByText('Active Conversations')).toBeInTheDocument();
		expect(screen.getByText('Avg Response Time')).toBeInTheDocument();
		expect(screen.getByText('Satisfaction Rate')).toBeInTheDocument();
		expect(screen.getByText('128')).toBeInTheDocument();
		expect(screen.getByText('7')).toBeInTheDocument();
		expect(screen.getByText('12s')).toBeInTheDocument();
		expect(screen.getByText('92%')).toBeInTheDocument();
	});
});
