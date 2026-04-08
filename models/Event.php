<?php
namespace App\Models;

use App\Core\BaseModel;
use RRule\RRule;

class Event extends BaseModel {

    public function __construct($dbConnection = null) {
        if ($dbConnection === null) {
            $dbConnection = \connect();
        }
        parent::__construct($dbConnection);
    }

    public function createEvent($data) {
        $stmt = $this->conn->prepare("
            INSERT INTO events (title, description, location, start_date, end_date, color, all_day, rrule, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "ssssssisi",
            $data['title'],
            $data['description'],
            $data['location'],
            $data['start_date'],
            $data['end_date'],
            $data['color'],
            $data['all_day'],
            $data['rrule'],
            $data['created_by']
        );
        
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function updateEvent($data) {
        $stmt = $this->conn->prepare("
            UPDATE events 
            SET title = ?, description = ?, location = ?, start_date = ?, end_date = ?, color = ?, all_day = ?, rrule = ?
            WHERE id = ?
        ");
        
        $stmt->bind_param(
            "ssssssisi",
            $data['title'],
            $data['description'],
            $data['location'],
            $data['start_date'],
            $data['end_date'],
            $data['color'],
            $data['all_day'],
            $data['rrule'],
            $data['id']
        );
        
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function getAllEvents($start = null, $end = null) {
        $query = "SELECT * FROM events";
        if ($start && $end) {
            $query .= " WHERE (start_date <= ? AND end_date >= ?)";
        }
        
        $stmt = $this->conn->prepare($query);
        if ($start && $end) {
            $stmt->bind_param("ss", $end, $start);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $events = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $events;
    }

    public function getExpandedEvents($start, $end) {
        $events = $this->getAllEvents();
        $expandedEvents = [];
        
        // Sanitize and normalize input date strings
        // Handle common suffix typos like ':1' or '+08:00:1'
        if ($start) {
            $start = preg_replace('/:[0-9]$/', '', str_replace(' ', '+', $start));
        }
        if ($end) {
            $end = preg_replace('/:[0-9]$/', '', str_replace(' ', '+', $end));
        }

        try {
            $startDt = $start ? new \DateTime($start) : null;
            $endDt = $end ? new \DateTime($end) : null;
        } catch (\Exception $e) {
            error_log("Calendar Date Parsing Error: " . $e->getMessage() . " (start=$start, end=$end)");
            $startDt = $endDt = null;
        }

        foreach ($events as $event) {
            if (!empty($event['rrule'])) {
                try {
                    // Ensure the library class exists
                    if (!class_exists('RRule\RRule')) {
                        throw new \Exception("RRule class not found. Check composer dependencies.");
                    }

                    $rrule = new RRule($event['rrule'], $event['start_date']);
                    
                    // getOccurrencesBetween handles DateTime instances better than strings
                    $occurrences = $rrule->getOccurrencesBetween($startDt, $endDt);

                    foreach ($occurrences as $occurrence) {
                        try {
                            $occurrenceStart = clone $occurrence;
                            
                            // Calculate duration safely
                            $eventStart = new \DateTime($event['start_date']);
                            $eventEnd = new \DateTime($event['end_date']);
                            $duration = $eventEnd->getTimestamp() - $eventStart->getTimestamp();
                            
                            $occurrenceEnd = (clone $occurrenceStart)->modify("+$duration seconds");

                            $expandedEvents[] = array_merge($event, [
                                'id' => $event['id'], // same id for all occurrences
                                'start_date' => $occurrenceStart->format('Y-m-d H:i:s'),
                                'end_date' => $occurrenceEnd->format('Y-m-d H:i:s'),
                            ]);
                        } catch (\Throwable $occEx) {
                            error_log("Error processing occurrence for event {$event['id']}: " . $occEx->getMessage());
                            continue;
                        }
                    }
                } catch (\Throwable $e) {
                    error_log("RRule Error for event {$event['id']}: " . $e->getMessage());
                    // Fallback: if RRule fails, we might still want to add the base event if it's in range
                    $this->addEventIfInWindow($expandedEvents, $event, $startDt, $endDt);
                }
            } else {
                $this->addEventIfInWindow($expandedEvents, $event, $startDt, $endDt);
            }
        }
        return $expandedEvents;
    }

    private function addEventIfInWindow(&$expandedEvents, $event, $startDt, $endDt) {
        try {
            $eventStart = new \DateTime($event['start_date']);
            $eventEnd = new \DateTime($event['end_date']);

            $isOverlap = true;
            if ($startDt && $eventEnd < $startDt) $isOverlap = false;
            if ($endDt && $eventStart > $endDt) $isOverlap = false;

            if ($isOverlap) {
                $expandedEvents[] = $event;
            }
        } catch (\Exception $e) {
            error_log("Error check window for event {$event['id']}: " . $e->getMessage());
        }
    }

    public function deleteEvent($id) {
        $stmt = $this->conn->prepare("DELETE FROM events WHERE id = ?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
}
