<?php
require 'connection.php';

// Add milestones for 36-60 months age range across all 5 developmental pillars
// These are based on CDC/WHO developmental milestones

$milestones = [
    // Attention (category_id = 7)
    ['cat' => 7, 'details' => 'Focuses on activity for 10-15 minutes', 'indicator' => '36-42 months'],
    ['cat' => 7, 'details' => 'Follows multi-step instructions', 'indicator' => '36-42 months'],
    ['cat' => 7, 'details' => 'Completes age-appropriate puzzles (8+ pieces)', 'indicator' => '42-48 months'],
    ['cat' => 7, 'details' => 'Maintains attention during story time', 'indicator' => '42-48 months'],
    ['cat' => 7, 'details' => 'Focuses on a chosen activity for 15+ minutes', 'indicator' => '48-54 months'],
    ['cat' => 7, 'details' => 'Can ignore minor distractions while working', 'indicator' => '48-54 months'],
    ['cat' => 7, 'details' => 'Plans and carries out a multi-step task', 'indicator' => '54-60 months'],
    ['cat' => 7, 'details' => 'Remembers and follows classroom-style rules', 'indicator' => '54-60 months'],

    // Communication (category_id = 8)
    ['cat' => 8, 'details' => 'Uses sentences of 4-5 words', 'indicator' => '36-42 months'],
    ['cat' => 8, 'details' => 'Tells simple stories about experiences', 'indicator' => '36-42 months'],
    ['cat' => 8, 'details' => 'Asks who, what, where, why questions', 'indicator' => '36-42 months'],
    ['cat' => 8, 'details' => 'Uses past tense correctly most of the time', 'indicator' => '42-48 months'],
    ['cat' => 8, 'details' => 'Speaks clearly enough for strangers to understand', 'indicator' => '42-48 months'],
    ['cat' => 8, 'details' => 'Recites familiar songs and rhymes', 'indicator' => '42-48 months'],
    ['cat' => 8, 'details' => 'Uses future tense (will, going to)', 'indicator' => '48-54 months'],
    ['cat' => 8, 'details' => 'Tells longer stories with a beginning and end', 'indicator' => '48-54 months'],
    ['cat' => 8, 'details' => 'Uses compound sentences with and, but, because', 'indicator' => '54-60 months'],
    ['cat' => 8, 'details' => 'Can explain how common objects are used', 'indicator' => '54-60 months'],

    // Social Skills (category_id = 9)
    ['cat' => 9, 'details' => 'Takes turns during games', 'indicator' => '36-42 months'],
    ['cat' => 9, 'details' => 'Shows concern when a friend is upset', 'indicator' => '36-42 months'],
    ['cat' => 9, 'details' => 'Plays cooperatively with other children', 'indicator' => '42-48 months'],
    ['cat' => 9, 'details' => 'Understands the concept of mine vs theirs', 'indicator' => '42-48 months'],
    ['cat' => 9, 'details' => 'Negotiates solutions to conflicts with words', 'indicator' => '48-54 months'],
    ['cat' => 9, 'details' => 'Prefers playing with friends over alone', 'indicator' => '48-54 months'],
    ['cat' => 9, 'details' => 'Understands and follows simple game rules', 'indicator' => '54-60 months'],
    ['cat' => 9, 'details' => 'Shows empathy and comforts others', 'indicator' => '54-60 months'],

    // Gross Motor (category_id = 10)
    ['cat' => 10, 'details' => 'Hops on one foot several times', 'indicator' => '36-42 months'],
    ['cat' => 10, 'details' => 'Catches a bounced ball most of the time', 'indicator' => '36-42 months'],
    ['cat' => 10, 'details' => 'Walks up and down stairs alternating feet', 'indicator' => '36-42 months'],
    ['cat' => 10, 'details' => 'Stands on one foot for 5+ seconds', 'indicator' => '42-48 months'],
    ['cat' => 10, 'details' => 'Throws ball overhand with aim', 'indicator' => '42-48 months'],
    ['cat' => 10, 'details' => 'Skips on alternating feet', 'indicator' => '48-54 months'],
    ['cat' => 10, 'details' => 'Rides a bicycle with training wheels', 'indicator' => '48-54 months'],
    ['cat' => 10, 'details' => 'Does a somersault', 'indicator' => '48-54 months'],
    ['cat' => 10, 'details' => 'Swings on a swing independently', 'indicator' => '54-60 months'],
    ['cat' => 10, 'details' => 'Can walk on a balance beam', 'indicator' => '54-60 months'],

    // Fine Motor (category_id = 11)
    ['cat' => 11, 'details' => 'Draws a person with 2-4 body parts', 'indicator' => '36-42 months'],
    ['cat' => 11, 'details' => 'Uses scissors to cut along a straight line', 'indicator' => '36-42 months'],
    ['cat' => 11, 'details' => 'Copies a circle and a cross (+)', 'indicator' => '36-42 months'],
    ['cat' => 11, 'details' => 'Draws a person with 6+ body parts', 'indicator' => '42-48 months'],
    ['cat' => 11, 'details' => 'Copies a square shape', 'indicator' => '42-48 months'],
    ['cat' => 11, 'details' => 'Buttons and unbuttons clothing', 'indicator' => '42-48 months'],
    ['cat' => 11, 'details' => 'Writes some letters and numbers', 'indicator' => '48-54 months'],
    ['cat' => 11, 'details' => 'Copies a triangle shape', 'indicator' => '48-54 months'],
    ['cat' => 11, 'details' => 'Writes own first name', 'indicator' => '54-60 months'],
    ['cat' => 11, 'details' => 'Uses a fork and knife together', 'indicator' => '54-60 months'],
];

$inserted = 0;
$skipped = 0;
$stmt = $connect->prepare("SELECT behavior_id FROM behavior WHERE behavior_details = ? AND category_id = ?");
$ins = $connect->prepare("INSERT INTO behavior (category_id, behavior_type, behavior_details, indicator) VALUES (?, 'milestone', ?, ?)");

foreach ($milestones as $m) {
    $stmt->execute([$m['details'], $m['cat']]);
    if ($stmt->fetchColumn()) {
        $skipped++;
        continue;
    }
    $ins->execute([$m['cat'], $m['details'], $m['indicator']]);
    $inserted++;
}

echo "Done! Inserted: $inserted, Skipped (already exist): $skipped\n";

// Verify
$stmt = $connect->query("SELECT COUNT(*) FROM behavior WHERE indicator LIKE '%36-%' OR indicator LIKE '%42-%' OR indicator LIKE '%48-%' OR indicator LIKE '%54-%'");
echo "Total 36-60 month behaviors: " . $stmt->fetchColumn() . "\n";
