<?php

namespace App\Notifications;

use App\Models\Incident;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class IncidentUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Incident $incident,
        public string $title,
        public string $message,
    ) {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'incident_id' => $this->incident->incident_id,
            'report_id' => $this->incident->report_id,
            'title' => $this->title,
            'message' => $this->message,
            'status' => $this->incident->status,
            'url' => route('incidents.show', ['incidentId' => $this->incident->incident_id]),
            'occurred_at' => now()->toIso8601String(),
        ];
    }
}
