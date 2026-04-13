"""
Unit tests for Speech Analysis NLP metrics.
Run with: python -m pytest test_analysis.py -v
"""

import pytest
import sys
import os

# Add parent directory to path for imports
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

# Import functions to test
from app import (
    analyze_vocabulary,
    analyze_sentence_complexity,
    analyze_word_complexity,
    calculate_readability_scores,
    generate_developmental_feedback,
    evaluate_child_vocab
)


class TestAnalyzeVocabulary:
    """Tests for basic vocabulary analysis."""

    def test_empty_transcript(self):
        """Empty transcript should return 0 unique words."""
        count, words = analyze_vocabulary("")
        assert count == 0
        assert words == []

    def test_single_word(self):
        """Single word transcript."""
        count, words = analyze_vocabulary("hello")
        assert count == 1
        assert "hello" in words

    def test_duplicate_words(self):
        """Duplicate words should only count once."""
        count, words = analyze_vocabulary("hello hello hello")
        assert count == 1
        assert "hello" in words

    def test_multiple_unique_words(self):
        """Multiple unique words should all be counted."""
        count, words = analyze_vocabulary("cat dog bird fish")
        assert count == 4
        assert len(words) == 4

    def test_case_insensitive(self):
        """Analysis should be case-insensitive."""
        count, words = analyze_vocabulary("Hello hello HELLO")
        assert count == 1

    def test_with_punctuation(self):
        """Words with punctuation should be handled."""
        count, words = analyze_vocabulary("Hello, world! How are you?")
        # Note: current implementation splits on spaces, punctuation stays attached
        assert count == 5


class TestAnalyzeSentenceComplexity:
    """Tests for sentence structure analysis."""

    def test_empty_transcript(self):
        """Empty transcript should return zero metrics."""
        result = analyze_sentence_complexity("")
        assert result['sentence_count'] == 0
        assert result['avg_sentence_length'] == 0
        assert result['complexity_score'] == 0

    def test_single_sentence(self):
        """Single sentence analysis."""
        result = analyze_sentence_complexity("The cat sits on the mat.")
        assert result['sentence_count'] == 1
        assert result['avg_sentence_length'] == 6
        assert result['max_sentence_length'] == 6

    def test_multiple_sentences(self):
        """Multiple sentences should be analyzed correctly."""
        result = analyze_sentence_complexity("Hello there. How are you today? I am fine!")
        assert result['sentence_count'] == 3
        assert result['avg_sentence_length'] > 0

    def test_complexity_score_short_sentences(self):
        """Short sentences should get lower complexity scores."""
        result = analyze_sentence_complexity("Hi. Bye. Go.")
        assert result['complexity_score'] <= 0.5

    def test_complexity_score_long_sentences(self):
        """Longer sentences should get higher complexity scores."""
        long_sentence = "The beautiful butterfly is flying gracefully through the garden."
        result = analyze_sentence_complexity(long_sentence)
        assert result['complexity_score'] >= 0.7


class TestAnalyzeWordComplexity:
    """Tests for word-level complexity analysis."""

    def test_empty_transcript(self):
        """Empty transcript should return zero metrics."""
        result = analyze_word_complexity("")
        assert result['avg_word_length'] == 0
        assert result['avg_syllables_per_word'] == 0

    def test_simple_words(self):
        """Simple words should have low syllable counts."""
        result = analyze_word_complexity("cat dog run go")
        assert result['avg_syllables_per_word'] <= 1.5

    def test_complex_words(self):
        """Complex words should have higher syllable counts."""
        result = analyze_word_complexity("elephant beautiful butterfly experiment")
        assert result['avg_syllables_per_word'] >= 2.5
        assert result['polysyllabic_word_count'] >= 2

    def test_mixed_vocabulary(self):
        """Mixed vocabulary should show moderate complexity."""
        result = analyze_word_complexity("The cat runs quickly through the garden")
        assert result['avg_word_length'] > 0
        assert result['avg_syllables_per_word'] > 0


class TestCalculateReadabilityScores:
    """Tests for readability metric calculations."""

    def test_empty_transcript(self):
        """Empty transcript should return zero scores."""
        result = calculate_readability_scores("")
        assert result['flesch_reading_ease'] == 0
        assert result['flesch_kincaid_grade'] == 0

    def test_simple_text(self):
        """Simple text should have high readability (low grade level)."""
        result = calculate_readability_scores("I see the cat. The cat is big.")
        assert result['flesch_kincaid_grade'] < 5

    def test_complex_text(self):
        """Complex text should have lower readability scores."""
        complex_text = "The sophisticated experimentation demonstrates remarkable physiological characteristics."
        result = calculate_readability_scores(complex_text)
        assert result['flesch_kincaid_grade'] > 5


class TestEvaluateChildVocab:
    """Tests for age-appropriate vocabulary evaluation."""

    def test_12_month_threshold(self):
        """12-month-old vocabulary thresholds."""
        status, expected = evaluate_child_vocab(5, 12)
        assert expected == 5
        assert status == "Within expected range"

    def test_below_expected(self):
        """Below expected vocabulary should be flagged."""
        status, expected = evaluate_child_vocab(10, 24)  # 24mo expects 200 words
        assert status == "Below expected range"

    def test_above_expected(self):
        """Above expected vocabulary should be flagged."""
        status, expected = evaluate_child_vocab(300, 24)  # 24mo expects 200 words
        assert status == "Above expected range"

    def test_age_interpolation(self):
        """Should use nearest lower age threshold."""
        _, expected = evaluate_child_vocab(50, 20)  # Between 18 and 24 months
        assert expected == 50  # Should use 18-month threshold


class TestGenerateDevelopmentalFeedback:
    """Tests for developmental feedback generation."""

    def test_advanced_child(self):
        """Advanced children should get appropriate feedback."""
        feedback = generate_developmental_feedback(
            vocab_size=250,
            age_months=24,
            sentence_complexity={'avg_sentence_length': 6, 'complexity_score': 0.8},
            word_complexity={'avg_syllables_per_word': 2.0, 'complexity_score': 0.8},
            readability={'overall_readability_score': 0.9},
            status='Above expected range'
        )
        assert feedback['milestone_status'] == 'advanced'
        assert len(feedback['strengths']) > 0

    def test_on_track_child(self):
        """On-track children should get encouraging feedback."""
        feedback = generate_developmental_feedback(
            vocab_size=180,
            age_months=24,
            sentence_complexity={'avg_sentence_length': 4, 'complexity_score': 0.6},
            word_complexity={'avg_syllables_per_word': 1.5, 'complexity_score': 0.6},
            readability={'overall_readability_score': 0.7},
            status='Within expected range'
        )
        assert feedback['milestone_status'] == 'on_track'

    def test_needs_attention_child(self):
        """Children needing attention should get supportive feedback."""
        feedback = generate_developmental_feedback(
            vocab_size=50,
            age_months=24,
            sentence_complexity={'avg_sentence_length': 2, 'complexity_score': 0.3},
            word_complexity={'avg_syllables_per_word': 1.0, 'complexity_score': 0.3},
            readability={'overall_readability_score': 0.4},
            status='Below expected range'
        )
        assert feedback['milestone_status'] == 'needs_attention'
        assert len(feedback['areas_to_practice']) > 0

    def test_feedback_has_all_required_fields(self):
        """Feedback should include all required fields."""
        feedback = generate_developmental_feedback(
            vocab_size=100,
            age_months=18,
            sentence_complexity={'avg_sentence_length': 3, 'complexity_score': 0.5},
            word_complexity={'avg_syllables_per_word': 1.2, 'complexity_score': 0.5},
            readability={'overall_readability_score': 0.6},
            status='Within expected range'
        )
        assert 'strengths' in feedback
        assert 'areas_to_practice' in feedback
        assert 'milestone_status' in feedback
        assert 'recommendations' in feedback


class TestIntegrationScenarios:
    """Integration tests simulating real-world scenarios."""

    def test_typical_18_month_session(self):
        """Simulate analysis for a typical 18-month-old."""
        transcript = "Mama dada ball. Ball go! Up up."

        vocab_count, vocab_words = analyze_vocabulary(transcript)
        sentence = analyze_sentence_complexity(transcript)
        word = analyze_word_complexity(transcript)
        readability = calculate_readability_scores(transcript)
        feedback = generate_developmental_feedback(
            vocab_count, 18, sentence, word, readability,
            evaluate_child_vocab(vocab_count, 18)[1]
        )

        assert vocab_count > 0
        assert sentence['sentence_count'] > 0
        assert feedback['milestone_status'] in ['on_track', 'advanced', 'needs_attention']

    def test_typical_36_month_session(self):
        """Simulate analysis for a typical 36-month-old."""
        transcript = "The big dog is running fast. I like to play outside with my friend."

        vocab_count, vocab_words = analyze_vocabulary(transcript)
        sentence = analyze_sentence_complexity(transcript)
        word = analyze_word_complexity(transcript)
        readability = calculate_readability_scores(transcript)
        status, expected = evaluate_child_vocab(vocab_count, 36)
        feedback = generate_developmental_feedback(
            vocab_count, 36, sentence, word, readability, status
        )

        assert vocab_count >= 5
        assert sentence['avg_sentence_length'] > 4
        assert feedback['milestone_status'] in ['on_track', 'advanced']


if __name__ == '__main__':
    pytest.main([__file__, '-v', '--tb=short'])
