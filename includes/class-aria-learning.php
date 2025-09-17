<?php
/**
 * Learning system handler
 *
 * @package    Aria
 * @subpackage Aria/includes
 */

/**
 * Handle learning and improvement system.
 */
class Aria_Learning {

	/**
	 * Minimum quality score threshold.
	 *
	 * @var int
	 */
	const MIN_QUALITY_SCORE = 60;

	/**
	 * Record learning data from conversation.
	 *
	 * @param int    $conversation_id Conversation ID.
	 * @param string $question User question.
	 * @param string $response AI response.
	 * @param array  $metadata Additional metadata.
	 * @return int|false Learning record ID or false.
	 */
	public static function record_interaction( $conversation_id, $question, $response, $metadata = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aria_learning_data';

		// Analyze the interaction
		$analysis = self::analyze_interaction( $question, $response );

		$data = array(
			'conversation_id'       => $conversation_id,
			'question'              => $question,
			'response'              => $response,
			'response_quality_score' => $analysis['quality_score'],
			'knowledge_gap'         => $analysis['knowledge_gap'] ? 1 : 0,
			'site_id'               => get_current_blog_id(),
			'created_at'            => current_time( 'mysql' ),
		);

		$result = $wpdb->insert( $table, $data );

		if ( false !== $result ) {
			$learning_id = $wpdb->insert_id;

			// Trigger learning process if needed
			if ( $analysis['knowledge_gap'] || $analysis['quality_score'] < self::MIN_QUALITY_SCORE ) {
				self::process_learning_opportunity( $learning_id, $analysis );
			}

			return $learning_id;
		}

		return false;
	}

	/**
	 * Analyze interaction quality.
	 *
	 * @param string $question User question.
	 * @param string $response AI response.
	 * @return array Analysis results.
	 */
	public static function analyze_interaction( $question, $response ) {
		$analysis = array(
			'quality_score'  => 100,
			'knowledge_gap'  => false,
			'issues'         => array(),
			'suggestions'    => array(),
		);

		// Check for knowledge gap indicators
		$gap_indicators = array(
			'i don\'t have information',
			'i\'m not sure',
			'i cannot answer',
			'connect you with a human',
			'contact our team',
			'i don\'t know',
			'unable to provide',
		);

		$response_lower = strtolower( $response );
		foreach ( $gap_indicators as $indicator ) {
			if ( strpos( $response_lower, $indicator ) !== false ) {
				$analysis['knowledge_gap'] = true;
				$analysis['quality_score'] -= 40;
				$analysis['issues'][] = 'knowledge_gap';
				$analysis['suggestions'][] = sprintf(
					__( 'Add knowledge about: %s', 'aria' ),
					self::extract_topic( $question )
				);
				break;
			}
		}

		// Check response length
		$response_length = strlen( $response );
		if ( $response_length < 50 ) {
			$analysis['quality_score'] -= 20;
			$analysis['issues'][] = 'response_too_short';
			$analysis['suggestions'][] = __( 'Response may be too brief', 'aria' );
		} elseif ( $response_length > 1000 ) {
			$analysis['quality_score'] -= 10;
			$analysis['issues'][] = 'response_too_long';
			$analysis['suggestions'][] = __( 'Response may be too verbose', 'aria' );
		}

		// Check for greeting/farewell in non-greeting questions
		if ( ! self::is_greeting_question( $question ) ) {
			$greeting_patterns = array( 'hello', 'hi there', 'welcome', 'good morning', 'good afternoon' );
			foreach ( $greeting_patterns as $pattern ) {
				if ( stripos( $response, $pattern ) === 0 ) {
					$analysis['quality_score'] -= 10;
					$analysis['issues'][] = 'unnecessary_greeting';
					break;
				}
			}
		}

		// Check for question answering
		if ( self::is_question( $question ) && strpos( $response, '?' ) === false ) {
			// Response doesn't seem to address the question format
			$analysis['quality_score'] -= 15;
			$analysis['issues'][] = 'may_not_answer_question';
		}

		// Ensure score is within bounds
		$analysis['quality_score'] = max( 0, min( 100, $analysis['quality_score'] ) );

		return $analysis;
	}

	/**
	 * Process learning opportunity.
	 *
	 * @param int   $learning_id Learning record ID.
	 * @param array $analysis Analysis results.
	 */
	private static function process_learning_opportunity( $learning_id, $analysis ) {
		// Log the learning opportunity
		$opportunity = array(
			'learning_id'    => $learning_id,
			'analysis'       => $analysis,
			'processed'      => false,
			'created_at'     => current_time( 'mysql' ),
		);

		// Store in transient for batch processing
		$opportunities = get_transient( 'aria_learning_opportunities' );
		if ( ! is_array( $opportunities ) ) {
			$opportunities = array();
		}
		$opportunities[] = $opportunity;
		set_transient( 'aria_learning_opportunities', $opportunities, DAY_IN_SECONDS );

		// Schedule processing if not already scheduled
		if ( ! wp_next_scheduled( 'aria_process_learning' ) ) {
			wp_schedule_single_event( time() + 300, 'aria_process_learning' );
		}
	}

	/**
	 * Process batch learning opportunities.
	 */
	public static function process_batch_learning() {
		$opportunities = get_transient( 'aria_learning_opportunities' );
		if ( ! is_array( $opportunities ) || empty( $opportunities ) ) {
			return;
		}

		$processed = array();
		$knowledge_gaps = array();

		foreach ( $opportunities as $opportunity ) {
			if ( ! $opportunity['processed'] ) {
				// Group by similar topics
				$topic = self::extract_topic_from_learning( $opportunity['learning_id'] );
				if ( ! isset( $knowledge_gaps[ $topic ] ) ) {
					$knowledge_gaps[ $topic ] = array(
						'count'       => 0,
						'questions'   => array(),
						'suggestions' => array(),
					);
				}

				$knowledge_gaps[ $topic ]['count']++;
				$knowledge_gaps[ $topic ]['questions'][] = $opportunity['learning_id'];
				$knowledge_gaps[ $topic ]['suggestions'] = array_merge(
					$knowledge_gaps[ $topic ]['suggestions'],
					$opportunity['analysis']['suggestions']
				);

				$opportunity['processed'] = true;
			}
			$processed[] = $opportunity;
		}

		// Update transient with processed status
		set_transient( 'aria_learning_opportunities', $processed, DAY_IN_SECONDS );

		// Create admin notifications for significant gaps
		foreach ( $knowledge_gaps as $topic => $data ) {
			if ( $data['count'] >= 3 ) {
				self::create_knowledge_gap_notification( $topic, $data );
			}
		}
	}

	/**
	 * Update response quality score.
	 *
	 * @param int $learning_id Learning record ID.
	 * @param int $score New quality score.
	 * @return bool Success status.
	 */
	public static function update_quality_score( $learning_id, $score ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aria_learning_data';

		return false !== $wpdb->update(
			$table,
			array( 'response_quality_score' => $score ),
			array( 'id' => $learning_id ),
			array( '%d' ),
			array( '%d' )
		);
	}

	/**
	 * Record user feedback.
	 *
	 * @param int    $conversation_id Conversation ID.
	 * @param string $feedback Feedback type (positive, negative, neutral).
	 * @param int    $rating Optional rating (1-5).
	 * @return bool Success status.
	 */
	public static function record_feedback( $conversation_id, $feedback, $rating = null ) {
		global $wpdb;
		$conversations_table = $wpdb->prefix . 'aria_conversations';
		$learning_table = $wpdb->prefix . 'aria_learning_data';

		// Update conversation satisfaction rating
		if ( null !== $rating ) {
			$wpdb->update(
				$conversations_table,
				array( 'satisfaction_rating' => $rating ),
				array( 'id' => $conversation_id ),
				array( '%d' ),
				array( '%d' )
			);
		}

		// Update learning data with feedback
		$result = $wpdb->update(
			$learning_table,
			array( 'user_feedback' => $feedback ),
			array( 'conversation_id' => $conversation_id ),
			array( '%s' ),
			array( '%d' )
		);

		// Adjust quality scores based on feedback
		if ( 'positive' === $feedback ) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE $learning_table 
					SET response_quality_score = LEAST(100, response_quality_score + 10)
					WHERE conversation_id = %d",
					$conversation_id
				)
			);
		} elseif ( 'negative' === $feedback ) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE $learning_table 
					SET response_quality_score = GREATEST(0, response_quality_score - 20)
					WHERE conversation_id = %d",
					$conversation_id
				)
			);
		}

		return false !== $result;
	}

	/**
	 * Get learning insights.
	 *
	 * @param string $period Time period (day, week, month).
	 * @return array Learning insights.
	 */
	public static function get_insights( $period = 'week' ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aria_learning_data';

		$interval = '7 DAY';
		switch ( $period ) {
			case 'day':
				$interval = '1 DAY';
				break;
			case 'month':
				$interval = '30 DAY';
				break;
		}

		// Overall statistics
		$stats = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT 
					COUNT(*) as total_interactions,
					AVG(response_quality_score) as avg_quality_score,
					SUM(CASE WHEN knowledge_gap = 1 THEN 1 ELSE 0 END) as knowledge_gaps,
					SUM(CASE WHEN user_feedback = 'positive' THEN 1 ELSE 0 END) as positive_feedback,
					SUM(CASE WHEN user_feedback = 'negative' THEN 1 ELSE 0 END) as negative_feedback
				FROM $table 
				WHERE site_id = %d 
					AND created_at >= DATE_SUB(NOW(), INTERVAL $interval)",
				get_current_blog_id()
			),
			ARRAY_A
		);

		// Trending topics with issues
		$problematic_topics = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT 
					question,
					COUNT(*) as occurrences,
					AVG(response_quality_score) as avg_score
				FROM $table 
				WHERE site_id = %d 
					AND created_at >= DATE_SUB(NOW(), INTERVAL $interval)
					AND (knowledge_gap = 1 OR response_quality_score < %d)
				GROUP BY question
				ORDER BY occurrences DESC
				LIMIT 10",
				get_current_blog_id(),
				self::MIN_QUALITY_SCORE
			),
			ARRAY_A
		);

		// Improvement over time
		$quality_trend = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT 
					DATE(created_at) as date,
					AVG(response_quality_score) as avg_score,
					COUNT(*) as interactions
				FROM $table 
				WHERE site_id = %d 
					AND created_at >= DATE_SUB(NOW(), INTERVAL $interval)
				GROUP BY DATE(created_at)
				ORDER BY date ASC",
				get_current_blog_id()
			),
			ARRAY_A
		);

		return array(
			'statistics'         => $stats,
			'problematic_topics' => $problematic_topics,
			'quality_trend'      => $quality_trend,
			'recommendations'    => self::generate_recommendations( $stats, $problematic_topics ),
		);
	}

	/**
	 * Generate recommendations based on insights.
	 *
	 * @param array $stats Overall statistics.
	 * @param array $topics Problematic topics.
	 * @return array Recommendations.
	 */
	private static function generate_recommendations( $stats, $topics ) {
		$recommendations = array();

		// Check average quality score
		if ( $stats['avg_quality_score'] < 70 ) {
			$recommendations[] = array(
				'type'     => 'quality',
				'priority' => 'high',
				'message'  => __( 'Response quality is below optimal. Consider reviewing and expanding your knowledge base.', 'aria' ),
			);
		}

		// Check knowledge gaps
		if ( $stats['knowledge_gaps'] > $stats['total_interactions'] * 0.2 ) {
			$recommendations[] = array(
				'type'     => 'knowledge',
				'priority' => 'high',
				'message'  => __( 'High number of knowledge gaps detected. Add more content to your knowledge base.', 'aria' ),
			);
		}

		// Check negative feedback ratio
		if ( $stats['negative_feedback'] > $stats['positive_feedback'] ) {
			$recommendations[] = array(
				'type'     => 'satisfaction',
				'priority' => 'medium',
				'message'  => __( 'More negative than positive feedback. Review conversation logs to identify issues.', 'aria' ),
			);
		}

		// Topic-specific recommendations
		foreach ( $topics as $topic ) {
			if ( $topic['occurrences'] >= 5 && $topic['avg_score'] < 50 ) {
				$recommendations[] = array(
					'type'     => 'topic',
					'priority' => 'medium',
					'message'  => sprintf(
						__( 'Frequent issues with questions about "%s". Consider adding specific knowledge.', 'aria' ),
						self::truncate_text( $topic['question'], 50 )
					),
				);
			}
		}

		return $recommendations;
	}

	/**
	 * Check if question is a greeting.
	 *
	 * @param string $question Question text.
	 * @return bool Is greeting.
	 */
	private static function is_greeting_question( $question ) {
		$greetings = array( 'hello', 'hi', 'hey', 'good morning', 'good afternoon', 'good evening', 'greetings' );
		$question_lower = strtolower( trim( $question ) );
		
		foreach ( $greetings as $greeting ) {
			if ( strpos( $question_lower, $greeting ) === 0 ) {
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Check if text is a question.
	 *
	 * @param string $text Text to check.
	 * @return bool Is question.
	 */
	private static function is_question( $text ) {
		// Check for question mark
		if ( strpos( $text, '?' ) !== false ) {
			return true;
		}
		
		// Check for question words
		$question_words = array( 'what', 'when', 'where', 'why', 'how', 'who', 'which', 'can', 'could', 'would', 'is', 'are', 'do', 'does' );
		$text_lower = strtolower( trim( $text ) );
		
		foreach ( $question_words as $word ) {
			if ( strpos( $text_lower, $word . ' ' ) === 0 ) {
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Extract topic from question.
	 *
	 * @param string $question Question text.
	 * @return string Extracted topic.
	 */
	private static function extract_topic( $question ) {
		// Remove common question words
		$remove_words = array( 'what', 'when', 'where', 'why', 'how', 'who', 'which', 'can', 'could', 'would', 'is', 'are', 'do', 'does', 'the', 'a', 'an', 'about', 'your', 'you' );
		
		$words = explode( ' ', strtolower( $question ) );
		$topic_words = array();
		
		foreach ( $words as $word ) {
			$word = trim( $word, '?.,!;:' );
			if ( strlen( $word ) > 2 && ! in_array( $word, $remove_words, true ) ) {
				$topic_words[] = $word;
			}
		}
		
		return implode( ' ', array_slice( $topic_words, 0, 3 ) );
	}

	/**
	 * Extract topic from learning record.
	 *
	 * @param int $learning_id Learning record ID.
	 * @return string Topic.
	 */
	private static function extract_topic_from_learning( $learning_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aria_learning_data';
		
		$question = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT question FROM $table WHERE id = %d",
				$learning_id
			)
		);
		
		return self::extract_topic( $question );
	}

	/**
	 * Create knowledge gap notification.
	 *
	 * @param string $topic Topic with gap.
	 * @param array  $data Gap data.
	 */
	private static function create_knowledge_gap_notification( $topic, $data ) {
		$notification = array(
			'type'       => 'knowledge_gap',
			'topic'      => $topic,
			'count'      => $data['count'],
			'created_at' => current_time( 'mysql' ),
		);
		
		// Store in option
		$notifications = get_option( 'aria_knowledge_notifications', array() );
		$notifications[] = $notification;
		
		// Keep only last 20 notifications
		$notifications = array_slice( $notifications, -20 );
		update_option( 'aria_knowledge_notifications', $notifications );
	}

	/**
	 * Truncate text to specified length.
	 *
	 * @param string $text Text to truncate.
	 * @param int    $length Maximum length.
	 * @return string Truncated text.
	 */
	private static function truncate_text( $text, $length ) {
		if ( strlen( $text ) <= $length ) {
			return $text;
		}
		
		return substr( $text, 0, $length - 3 ) . '...';
	}
}