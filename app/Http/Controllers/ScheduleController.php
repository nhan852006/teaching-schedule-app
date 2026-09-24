<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Subject;
use App\Models\SubjectContent;
use App\Models\Classes;
use App\Models\Schedule;
use App\Services\GoogleCalendarService;
use App\Services\WordExportService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Carbon\Carbon;
use Exception;

class ScheduleController extends Controller
{
    /**
     * Lấy thông tin Giảng viên đang làm việc trong phiên hiện tại
     */
    protected function getCurrentTeacher(): User
    {
        $teacherId = session('active_teacher_id');
        $teacher = null;

        if ($teacherId) {
            $teacher = User::find($teacherId);
        }

        if (!$teacher) {
            $teacher = User::first() ?? User::create([
                'name' => 'ThS. Hoàng Văn Nhân',
                'email' => 'nhan.hv@university.edu.vn',
                'password' => bcrypt('password123'),
            ]);
            session(['active_teacher_id' => $teacher->id]);
        }

        return $teacher;
    }

    /**
     * Chuyển đổi Giảng viên làm việc (Teacher Switcher)
     */
    public function switchTeacher(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required|exists:users,id',
        ]);

        session(['active_teacher_id' => $request->teacher_id]);
        $teacher = User::find($request->teacher_id);

        return redirect()->route('schedules.index')
            ->with('success', "Đã chuyển sang không gian làm việc của giảng viên: {$teacher->name}");
    }

    /**
     * Dashboard chính: Thống kê KPI, Quản lý lớp môn & Lập kế hoạch cá nhân
     */
    public function index()
    {
        $currentTeacher = $this->getCurrentTeacher();
        $allTeachers = User::orderBy('name')->get();

        // 1. Lọc Lịch giảng dạy thuộc về Giảng viên hiện tại
        $mySchedules = Schedule::where('user_id', $currentTeacher->id)->get();

        // Danh sách các cặp (Lớp - Môn) của Thầy/Cô này
        $pairs = Schedule::query()
            ->where('user_id', $currentTeacher->id)
            ->select('class_id', 'subject_id', DB::raw('count(*) as total_schedules'))
            ->with(['class', 'subject'])
            ->groupBy('class_id', 'subject_id')
            ->get();

        // Danh sách môn học do Giảng viên này phụ trách hoặc tất cả môn trong trường
        $mySubjects = Subject::where('user_id', $currentTeacher->id)
            ->withCount('contents')
            ->orderBy('name')
            ->get();

        $allSubjects = Subject::orderBy('name')->get();
        $classes = Classes::orderBy('name')->get();

        // 2. Thống kê KPI cá nhân hóa
        $totalClassesCount = $mySchedules->pluck('class_id')->unique()->count();
        $totalSubjectsCount = $mySchedules->pluck('subject_id')->unique()->count();
        $totalSessionsCount = $mySchedules->count();

        // Thống kê đồng bộ Calendar
        $syncSynced = $mySchedules->where('sync_status', 'synced')->count();
        $syncModified = $mySchedules->where('sync_status', 'modified')->count();
        $syncPending = $mySchedules->where('sync_status', 'pending')->count();
        $syncPercentage = $totalSessionsCount > 0 ? round(($syncSynced / $totalSessionsCount) * 100) : 0;

        // 3. Tối ưu truy vấn dữ liệu cho Interactive Calendar
        $allContents = SubjectContent::all()->groupBy('subject_id');

        $allSchedules = Schedule::with(['class', 'subject', 'teacher'])->get();

        $allCalendarEvents = $allSchedules->map(function ($s) use ($allContents) {
            $c = $allContents->get($s->subject_id)?->firstWhere('session_number', $s->session_number);
            return [
                'id' => $s->id,
                'date' => $s->teaching_date ? $s->teaching_date->format('Y-m-d') : '',
                'shift' => $s->session_shift,
                'session' => $s->session_number,
                'class_id' => $s->class_id,
                'class_name' => $s->class?->name ?? '',
                'subject_id' => $s->subject_id,
                'subject_name' => $s->subject?->name ?? '',
                'teacher_id' => $s->user_id,
                'teacher_name' => $s->teacher?->name ?? '',
                'content' => $c?->content ?? 'Chưa cập nhật nội dung',
                'theory_time' => $c?->theory_time ?? 0,
                'practice_time' => $c?->practice_time ?? 0,
                'sync_status' => $s->sync_status,
            ];
        });

        // Lọc sự kiện riêng của Giảng viên hiện tại
        $calendarEvents = $allCalendarEvents->where('teacher_id', $currentTeacher->id)->values();

        // Tháng hiển thị mặc định: Tháng có lịch dạy gần nhất của giảng viên (hoặc tháng hiện tại)
        $firstScheduleDate = $mySchedules->min('teaching_date');
        $defaultMonth = $firstScheduleDate ? Carbon::parse($firstScheduleDate)->format('Y-m') : Carbon::now()->format('Y-m');

        return view('schedules.index', compact(
            'currentTeacher',
            'allTeachers',
            'pairs',
            'mySubjects',
            'allSubjects',
            'classes',
            'totalClassesCount',
            'totalSubjectsCount',
            'totalSessionsCount',
            'syncSynced',
            'syncModified',
            'syncPending',
            'syncPercentage',
            'calendarEvents',
            'allCalendarEvents',
            'defaultMonth'
        ));
    }

    /**
     * Thêm mới Môn học & các Mô-đun/Buổi học trực quan từ giao diện
     */
    public function storeSubjectWithModules(Request $request)
    {
        $currentTeacher = $this->getCurrentTeacher();

        $request->validate([
            'code' => 'required|string|max:50|unique:subjects,code',
            'name' => 'required|string|max:255',
            'modules' => 'required|array|min:1',
            'modules.*.content' => 'required|string',
            'modules.*.theory_time' => 'required|numeric|min:0',
            'modules.*.practice_time' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $totalSessions = count($request->modules);

            $subject = Subject::create([
                'user_id' => $currentTeacher->id,
                'code' => strtoupper(trim($request->code)),
                'name' => trim($request->name),
                'total_sessions' => $totalSessions,
            ]);

            foreach ($request->modules as $index => $mod) {
                SubjectContent::create([
                    'subject_id' => $subject->id,
                    'session_number' => $index + 1,
                    'content' => trim($mod['content']),
                    'theory_time' => (int)$mod['theory_time'],
                    'practice_time' => (int)$mod['practice_time'],
                ]);
            }

            DB::commit();

            return back()->with('success', "Đã tạo thành công môn học [{$subject->name}] với {$totalSessions} mô-đun bài giảng!");
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi khi tạo môn học: ' . $e->getMessage());
        }
    }

    /**
     * Thêm nhanh Lớp học mới
     */
    public function storeClassQuick(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:classes,name',
        ]);

        Classes::create(['name' => trim($request->name)]);

        return back()->with('success', "Đã thêm thành công lớp học mới: {$request->name}");
    }

    /**
     * Tự động sinh Kế hoạch Giảng dạy cá nhân (Thứ 2 đến Thứ 7)
     */
    public function generateSchedulePlan(Request $request)
    {
        $currentTeacher = $this->getCurrentTeacher();

        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'start_date' => 'required|date',
            'days_of_week' => 'required|array|min:1',
            'days_of_week.*' => 'in:1,2,3,4,5,6', // 1=Mon, 2=Tue, 3=Wed, 4=Thu, 5=Fri, 6=Sat
            'shift_mode' => 'required|in:Sáng,Chiều,Xen kẽ',
        ]);

        $subject = Subject::with('contents')->findOrFail($request->subject_id);
        $totalSessions = $subject->total_sessions > 0 ? $subject->total_sessions : $subject->contents->count();

        if ($totalSessions <= 0) {
            return back()->with('error', "Môn học [{$subject->name}] chưa có mô-đun buổi học nào. Vui lòng thêm mô-đun trước khi lập lịch.");
        }

        $selectedDays = array_map('intval', $request->days_of_week);
        $currentDate = Carbon::parse($request->start_date);
        $sessionNumber = 1;
        $shiftMode = $request->shift_mode;

        DB::beginTransaction();
        try {
            // Duyệt ngày liên tục từ ngày bắt đầu đến khi đủ số buổi
            $safetyCounter = 0;
            while ($sessionNumber <= $totalSessions && $safetyCounter < 365) {
                $safetyCounter++;

                // Bỏ qua Chủ Nhật
                if (!$currentDate->isSunday()) {
                    // Carbon: Monday=1, Tuesday=2, ... Saturday=6
                    $dayOfWeekIso = $currentDate->dayOfWeekIso;

                    if (in_array($dayOfWeekIso, $selectedDays)) {
                        $shiftCandidates = [];
                        if ($shiftMode === 'Sáng') {
                            $shiftCandidates = ['Sáng'];
                        } elseif ($shiftMode === 'Chiều') {
                            $shiftCandidates = ['Chiều'];
                        } else {
                            // Xen kẽ
                            $preferred = ($sessionNumber % 2 === 1) ? 'Sáng' : 'Chiều';
                            $other = ($preferred === 'Sáng') ? 'Chiều' : 'Sáng';
                            $shiftCandidates = [$preferred, $other];
                        }

                        foreach ($shiftCandidates as $shift) {
                            $dateStr = $currentDate->format('Y-m-d');

                            // Kiểm tra Giảng viên có bận ca này không
                            $teacherBusy = Schedule::where('user_id', $currentTeacher->id)
                                ->where('teaching_date', $dateStr)
                                ->where('session_shift', $shift)
                                ->exists();

                            // Kiểm tra Lớp học có bận ca này không
                            $classBusy = Schedule::where('class_id', $request->class_id)
                                ->where('teaching_date', $dateStr)
                                ->where('session_shift', $shift)
                                ->exists();

                            if (!$teacherBusy && !$classBusy) {
                                Schedule::updateOrCreate(
                                    [
                                        'class_id' => $request->class_id,
                                        'subject_id' => $subject->id,
                                        'session_number' => $sessionNumber,
                                    ],
                                    [
                                        'user_id' => $currentTeacher->id,
                                        'teaching_date' => $dateStr,
                                        'session_shift' => $shift,
                                        'sync_status' => 'pending',
                                        'google_event_id' => null,
                                    ]
                                );

                                $sessionNumber++;
                                break;
                            }
                        }
                    }
                }

                $currentDate->addDay();
            }

            DB::commit();

            return redirect()->route('schedules.preview', [
                'class_id' => $request->class_id,
                'subject_id' => $subject->id
            ])->with('success', "Đã lập thành công kế hoạch giảng dạy {$totalSessions} buổi học!");
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi khi lập kế hoạch: ' . $e->getMessage());
        }
    }

    /**
     * Feature A: Import Subject Modules (CSV)
     */
    public function importSubjectContents(Request $request)
    {
        $currentTeacher = $this->getCurrentTeacher();

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        if (!$handle) {
            return back()->with('error', 'Không thể đọc file CSV tải lên.');
        }

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle, 1000, ',');
        if (!$header) {
            fclose($handle);
            return back()->with('error', 'File CSV không có dữ liệu.');
        }

        $header = array_map('trim', $header);
        $rowCount = 0;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 2000, ',')) !== false) {
                if (empty(array_filter($row))) {
                    continue;
                }

                $data = array_combine($header, array_map('trim', $row));
                if (!$data) {
                    continue;
                }

                $subjectCode   = $data['Mã Môn'] ?? null;
                $subjectName   = $data['Tên Môn'] ?? null;
                $sessionNumber = isset($data['Buổi số']) ? (int)$data['Buổi số'] : 0;
                $content       = $data['Nội dung giảng dạy'] ?? '';
                $theoryTime    = isset($data['Số tiết LT']) ? (int)$data['Số tiết LT'] : 0;
                $practiceTime  = isset($data['Số tiết TH']) ? (int)$data['Số tiết TH'] : 0;

                if (!$subjectCode || !$sessionNumber) {
                    continue;
                }

                $subject = Subject::firstOrCreate(
                    ['code' => $subjectCode],
                    [
                        'user_id' => $currentTeacher->id,
                        'name' => $subjectName ?: $subjectCode,
                        'total_sessions' => 0
                    ]
                );

                if ($subjectName && $subject->name !== $subjectName) {
                    $subject->update(['name' => $subjectName]);
                }

                SubjectContent::updateOrCreate(
                    [
                        'subject_id'     => $subject->id,
                        'session_number' => $sessionNumber,
                    ],
                    [
                        'content'       => $content,
                        'theory_time'   => $theoryTime,
                        'practice_time' => $practiceTime,
                    ]
                );

                $maxSession = SubjectContent::where('subject_id', $subject->id)->max('session_number');
                $subject->update(['total_sessions' => $maxSession]);

                $rowCount++;
            }

            DB::commit();
            fclose($handle);

            return back()->with('success', "Đã import thành công {$rowCount} buổi nội dung môn học cho {$currentTeacher->name}.");
        } catch (Exception $e) {
            DB::rollBack();
            fclose($handle);
            return back()->with('error', 'Lỗi khi xử lý CSV: ' . $e->getMessage());
        }
    }

    /**
     * Feature B: Import Teaching Schedule (CSV)
     */
    public function importSchedules(Request $request)
    {
        $currentTeacher = $this->getCurrentTeacher();

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        if (!$handle) {
            return back()->with('error', 'Không thể đọc file CSV tải lên.');
        }

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle, 1000, ',');
        if (!$header) {
            fclose($handle);
            return back()->with('error', 'File CSV không có dữ liệu.');
        }
        $header = array_map('trim', $header);

        $rawRows = [];
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            if (empty(array_filter($row))) {
                continue;
            }
            $mapped = array_combine($header, array_map('trim', $row));
            if ($mapped) {
                $rawRows[] = $mapped;
            }
        }
        fclose($handle);

        DB::beginTransaction();
        try {
            $grouped = [];

            foreach ($rawRows as $item) {
                $className   = $item['Tên Lớp'] ?? '';
                $subjectName = $item['Tên Môn'] ?? '';
                $teachingDate = $item['Ngày'] ?? ($item['Ngày (YYYY-MM-DD)'] ?? '');
                $shift        = $item['Buổi'] ?? ($item['Buổi (Sáng/Chiều)'] ?? 'Sáng');

                $shift = (stripos($shift, 'chiều') !== false || stripos($shift, 'chieu') !== false) ? 'Chiều' : 'Sáng';

                if (!$className || !$subjectName || !$teachingDate) {
                    continue;
                }

                $class = Classes::firstOrCreate(['name' => $className]);

                $subject = Subject::where('name', $subjectName)
                    ->orWhere('code', $subjectName)
                    ->first();

                if (!$subject) {
                    $subject = Subject::create([
                        'user_id' => $currentTeacher->id,
                        'code' => strtoupper(\Illuminate\Support\Str::slug($subjectName, '')),
                        'name' => $subjectName,
                        'total_sessions' => 0
                    ]);
                }

                $key = $class->id . '_' . $subject->id;
                $grouped[$key]['class'] = $class;
                $grouped[$key]['subject'] = $subject;
                $grouped[$key]['rows'][] = [
                    'date'  => date('Y-m-d', strtotime($teachingDate)),
                    'shift' => $shift,
                ];
            }

            $totalImported = 0;

            foreach ($grouped as $group) {
                $class = $group['class'];
                $subject = $group['subject'];
                $rows = $group['rows'];

                usort($rows, function ($a, $b) {
                    return strcmp($a['date'], $b['date']);
                });

                $sessionNumber = 1;
                foreach ($rows as $rowData) {
                    Schedule::updateOrCreate(
                        [
                            'class_id'       => $class->id,
                            'subject_id'     => $subject->id,
                            'session_number' => $sessionNumber,
                        ],
                        [
                            'user_id'       => $currentTeacher->id,
                            'teaching_date' => $rowData['date'],
                            'session_shift' => $rowData['shift'],
                            'sync_status'   => 'pending',
                        ]
                    );
                    $sessionNumber++;
                    $totalImported++;
                }
            }

            DB::commit();

            return back()->with('success', "Đã import và gán lịch dạy thành công cho {$currentTeacher->name}!");
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi khi import lịch giảng: ' . $e->getMessage());
        }
    }

    /**
     * Helper lấy schedules kèm subjectContent tối ưu
     */
    protected function getSchedulesWithContent($classId, $subjectId)
    {
        $schedules = Schedule::where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->orderBy('session_number', 'asc')
            ->get();

        $contents = SubjectContent::where('subject_id', $subjectId)
            ->get()
            ->keyBy('session_number');

        foreach ($schedules as $s) {
            $s->setRelation('subjectContent', $contents->get($s->session_number));
        }

        return $schedules;
    }

    /**
     * Feature C: Detail / Preview Page (The Core UI)
     */
    public function preview($classId, $subjectId)
    {
        $class = Classes::findOrFail($classId);
        $subject = Subject::findOrFail($subjectId);
        $currentTeacher = $this->getCurrentTeacher();

        $schedules = $this->getSchedulesWithContent($classId, $subjectId);

        return view('schedules.preview', compact('class', 'subject', 'schedules', 'currentTeacher'));
    }

    /**
     * Cập nhật Inline qua AJAX cho teaching_date và session_shift có kiểm tra xung đột lịch
     */
    public function updateInline(Request $request, $id): JsonResponse
    {
        $request->validate([
            'teaching_date' => 'required|date_format:Y-m-d',
            'session_shift' => 'required|in:Sáng,Chiều',
        ]);

        try {
            $schedule = Schedule::with(['class', 'subject'])->findOrFail($id);

            $oldDate  = $schedule->teaching_date->format('Y-m-d');
            $oldShift = $schedule->session_shift;

            $hasChanged = ($oldDate !== $request->teaching_date) || ($oldShift !== $request->session_shift);

            if ($hasChanged) {
                $newDate = Carbon::parse($request->teaching_date);

                // Luật 1: Không xếp vào Chủ Nhật
                if ($newDate->isSunday()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Quy định: Không xếp lịch dạy vào ngày Chủ Nhật!',
                    ], 422);
                }

                // Luật 2: 1 Giảng viên chỉ dạy 1 lớp trong 1 ca
                if ($schedule->user_id) {
                    $teacherConflict = Schedule::where('user_id', $schedule->user_id)
                        ->where('teaching_date', $request->teaching_date)
                        ->where('session_shift', $request->session_shift)
                        ->where('id', '!=', $schedule->id)
                        ->with(['class', 'subject'])
                        ->first();

                    if ($teacherConflict) {
                        return response()->json([
                            'success' => false,
                            'message' => "Trùng lịch Giảng viên! Thầy/Cô đã có lịch dạy lớp [{$teacherConflict->class?->name}] môn [{$teacherConflict->subject?->name}] vào ca {$request->session_shift} ngày {$request->teaching_date}.",
                        ], 422);
                    }
                }

                // Luật 3: 1 Lớp học chỉ học 1 môn trong 1 ca
                $classConflict = Schedule::where('class_id', $schedule->class_id)
                    ->where('teaching_date', $request->teaching_date)
                    ->where('session_shift', $request->session_shift)
                    ->where('id', '!=', $schedule->id)
                    ->with(['subject'])
                    ->first();

                if ($classConflict) {
                    return response()->json([
                        'success' => false,
                        'message' => "Trùng lịch Lớp học! Lớp [{$schedule->class?->name}] đã có lịch học môn [{$classConflict->subject?->name}] vào ca {$request->session_shift} ngày {$request->teaching_date}.",
                    ], 422);
                }

                $schedule->teaching_date = $request->teaching_date;
                $schedule->session_shift = $request->session_shift;
                
                if ($schedule->sync_status === 'synced') {
                    $schedule->sync_status = 'modified';
                }
                
                $schedule->save();
            }

            return response()->json([
                'success'     => true,
                'message'     => 'Cập nhật lịch học thành công!',
                'sync_status' => $schedule->sync_status,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Feature D: Google Calendar Integration Trigger
     */
    public function syncGoogleCalendar($classId, $subjectId, GoogleCalendarService $calendarService): JsonResponse
    {
        $class = Classes::findOrFail($classId);
        $subject = Subject::findOrFail($subjectId);

        if (!$calendarService->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Chưa cấu hình file Google Service Account JSON (storage/app/google-calendar/credentials.json). Vui lòng thêm credentials trước khi đồng bộ.',
            ], 422);
        }

        $schedules = $this->getSchedulesWithContent($classId, $subjectId);

        $syncedCount = 0;
        $errors = [];

        foreach ($schedules as $schedule) {
            $contentModel = $schedule->subjectContent;
            $contentStr   = $contentModel?->content ?? 'Nội dung đang cập nhật';
            $lt           = $contentModel?->theory_time ?? 0;
            $th           = $contentModel?->practice_time ?? 0;

            $summary = "{$class->name} - {$subject->name} - Buổi {$schedule->session_number}";
            $description = "{$contentStr}\nLT: {$lt} - TH: {$th}";
            $date = $schedule->teaching_date->format('Y-m-d');
            $shift = $schedule->session_shift;

            try {
                if (empty($schedule->google_event_id)) {
                    $eventId = $calendarService->createEvent($summary, $description, $date, $shift);
                    $schedule->update([
                        'google_event_id' => $eventId,
                        'sync_status'     => 'synced',
                    ]);
                    $syncedCount++;
                } elseif ($schedule->sync_status === 'modified') {
                    $calendarService->updateEvent($schedule->google_event_id, $summary, $description, $date, $shift);
                    $schedule->update([
                        'sync_status' => 'synced',
                    ]);
                    $syncedCount++;
                }
            } catch (Exception $e) {
                Log::error("Lỗi sync Calendar buổi {$schedule->session_number}: " . $e->getMessage());
                $errors[] = "Buổi {$schedule->session_number}: " . $e->getMessage();
            }
        }

        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => "Đồng bộ hoàn tất một phần ({$syncedCount} buổi). Có lỗi ở: " . implode('; ', $errors),
            ], 207);
        }

        return response()->json([
            'success' => true,
            'message' => "Đã đồng bộ thành công {$syncedCount} buổi lên Google Calendar!",
        ]);
    }

    /**
     * Feature E: Export Word Handbook Trigger
     */
    public function exportWord($classId, $subjectId, WordExportService $wordService): BinaryFileResponse
    {
        $class = Classes::findOrFail($classId);
        $subject = Subject::findOrFail($subjectId);

        $schedules = $this->getSchedulesWithContent($classId, $subjectId);

        $filePath = $wordService->exportHandbook($class, $subject, $schedules);

        $downloadName = 'So_Tay_Giang_Day_' . $class->name . '_' . $subject->code . '.docx';

        return response()->download($filePath, $downloadName)->deleteFileAfterSend(true);
    }
}
