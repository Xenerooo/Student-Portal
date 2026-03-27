<?php
require_once __DIR__ . '/Core/db_connect.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/Models/Event.php';
require_once __DIR__ . '/Core/BaseModel.php';

use App\Models\Event;

$conn = connect();
$eventModel = new Event($conn);

// Simulate FullCalendar date range for March 2026
$start = '2026-02-22T00:00:00Z';
$end = '2026-04-05T00:00:00Z';

echo "Testing getExpandedEvents for range: $start to $end\n";
try {
    $events = $eventModel->getExpandedEvents($start, $end);
    echo "Count: " . count($events) . "\n";
    foreach ($events as $e) {
        echo "ID: {$e['id']} | Title: {$e['title']} | Start: {$e['start_date']} | End: {$e['end_date']} | RRule: {$e['rrule']}\n";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
