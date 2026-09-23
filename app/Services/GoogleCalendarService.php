<?php

namespace App\Services;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    protected ?Calendar $calendarService = null;
    protected string $calendarId;

    public function __construct()
    {
        $credentialsPath = config('services.google.service_account_credentials_path');
        $this->calendarId = config('services.google.calendar_id', 'primary');

        if (!empty($credentialsPath) && file_exists($credentialsPath)) {
            $client = new Client();
            $client->setAuthConfig($credentialsPath);
            $client->addScope(Calendar::CALENDAR);
            $client->setAccessType('offline');

            $this->calendarService = new Calendar($client);
        }
    }

    /**
     * Kiểm tra service đã cấu hình credentials hợp lệ hay chưa
     */
    public function isConfigured(): bool
    {
        return $this->calendarService !== null;
    }

    /**
     * Xác định thời gian bắt đầu và kết thúc dựa trên ngày và ca dạy (Asia/Ho_Chi_Minh)
     */
    protected function getTimeRange(string $date, string $shift): array
    {
        $timeZone = 'Asia/Ho_Chi_Minh';
        
        if ($shift === 'Sáng') {
            $startDateTime = Carbon::createFromFormat('Y-m-d H:i:s', "{$date} 07:30:00", $timeZone);
            $endDateTime   = Carbon::createFromFormat('Y-m-d H:i:s', "{$date} 11:30:00", $timeZone);
        } else {
            // 'Chiều'
            $startDateTime = Carbon::createFromFormat('Y-m-d H:i:s', "{$date} 13:00:00", $timeZone);
            $endDateTime   = Carbon::createFromFormat('Y-m-d H:i:s', "{$date} 17:00:00", $timeZone);
        }

        return [
            'start' => $startDateTime->toRfc3339String(),
            'end'   => $endDateTime->toRfc3339String(),
            'timeZone' => $timeZone,
        ];
    }

    /**
     * Tạo mới một Google Calendar Event
     */
    public function createEvent(string $summary, string $description, string $date, string $shift): string
    {
        if (!$this->isConfigured()) {
            throw new Exception("Google Service Account credentials JSON chưa được cấu hình hoặc file không tồn tại.");
        }

        $timeRange = $this->getTimeRange($date, $shift);

        $event = new Event([
            'summary'     => $summary,
            'description' => $description,
            'start' => new EventDateTime([
                'dateTime' => $timeRange['start'],
                'timeZone' => $timeRange['timeZone'],
            ]),
            'end' => new EventDateTime([
                'dateTime' => $timeRange['end'],
                'timeZone' => $timeRange['timeZone'],
            ]),
        ]);

        $createdEvent = $this->calendarService->events->insert($this->calendarId, $event);
        return $createdEvent->getId();
    }

    /**
     * Cập nhật Google Calendar Event đã có
     */
    public function updateEvent(string $eventId, string $summary, string $description, string $date, string $shift): void
    {
        if (!$this->isConfigured()) {
            throw new Exception("Google Service Account credentials JSON chưa được cấu hình hoặc file không tồn tại.");
        }

        $timeRange = $this->getTimeRange($date, $shift);

        $event = $this->calendarService->events->get($this->calendarId, $eventId);
        $event->setSummary($summary);
        $event->setDescription($description);

        $event->setStart(new EventDateTime([
            'dateTime' => $timeRange['start'],
            'timeZone' => $timeRange['timeZone'],
        ]));

        $event->setEnd(new EventDateTime([
            'dateTime' => $timeRange['end'],
            'timeZone' => $timeRange['timeZone'],
        ]));

        $this->calendarService->events->update($this->calendarId, $eventId, $event);
    }
}
