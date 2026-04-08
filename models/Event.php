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
        
        // Handle ISO8601 strings that may have had '+' converted to ' ' by URL encoding
        if ($start) $start = str_replace(' ', '+', $start);
        if ($end) $end = str_replace(' ', '+', $end);

        try {
            $startDt = $start ? new \DateTime($start) : null;
            $endDt = $end ? new \DateTime($end) : null;
        } catch (\Exception $e) {
            error_log("Calendar Date Parsing Error: " . $e->getMessage());
            $startDt = $endDt = null;
        }

        foreach ($events as $event) {
            if (!empty($event['rrule'])) {
                try {
                    $rrule = new RRule($event['rrule'], $event['start_date']);
                    $occurrences = $rrule->getOccurrencesBetween($start, $end);

                    foreach ($occurrences as $occurrence) {
                        $occurrenceStart = clone $occurrence;
                        $duration = (new \DateTime($event['end_date']))->getTimestamp() - (new \DateTime($event['start_date']))->getTimestamp();
                        $occurrenceEnd = (clone $occurrenceStart)->modify("+$duration seconds");

                        $expandedEvents[] = array_merge($event, [
                            'id' => $event['id'], // same id for all occurrences
                            'start_date' => $occurrenceStart->format('Y-m-d H:i:s'),
                            'end_date' => $occurrenceEnd->format('Y-m-d H:i:s'),
                        ]);
                    }
                } catch (\Exception $e) {
                    error_log("RRule Error for event {$event['id']}: " . $e->getMessage());
                }
            } else {
                // Non-recurring event: check if it overlaps with the range
                $eventStart = new \DateTime($event['start_date']);
                $eventEnd = new \DateTime($event['end_date']);

                $isOverlap = true;
                if ($startDt && $eventEnd < $startDt) $isOverlap = false;
                if ($endDt && $eventStart > $endDt) $isOverlap = false;

                if ($isOverlap) {
                    $expandedEvents[] = $event;
                }
            }
        }
        return $expandedEvents;
    }

    public function deleteEvent($id) {
        $stmt = $this->conn->prepare("DELETE FROM events WHERE id = ?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
}
