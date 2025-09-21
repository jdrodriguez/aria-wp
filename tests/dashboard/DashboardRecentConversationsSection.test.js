import React from 'react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import DashboardRecentConversationsSection from '../../src/js/admin/pages/dashboard-sections/DashboardRecentConversationsSection.jsx';

jest.mock('@wordpress/i18n', () => ({
	__: (text) => text,
}));

describe('DashboardRecentConversationsSection', () => {
	const sampleConversation = {
		id: 12,
		guest_name: 'Alex',
		initial_question: 'How do I reset my password?',
		created_at: new Date().toISOString(),
	};

	it('shows empty state when there are no conversations', () => {
		render(
			<DashboardRecentConversationsSection
				conversations={[]}
				onSelectConversation={jest.fn()}
				onViewAll={jest.fn()}
				onTestChat={jest.fn()}
			/>
		);

		expect(screen.getByText('No conversations yet. Aria is ready to start chatting with your visitors!')).toBeInTheDocument();
	});

	it('allows selecting a conversation and viewing all', async () => {
		const onSelectConversation = jest.fn();
		const onViewAll = jest.fn();

		render(
			<DashboardRecentConversationsSection
				conversations={[sampleConversation]}
				onSelectConversation={onSelectConversation}
				onViewAll={onViewAll}
				onTestChat={jest.fn()}
			/>
		);

		await userEvent.click(screen.getByRole('button', { name: /View All/i }));
		expect(onViewAll).toHaveBeenCalledTimes(1);

		await userEvent.click(screen.getByRole('button', { name: /Alex/i }));
		expect(onSelectConversation).toHaveBeenCalledWith(sampleConversation.id);
	});
});
