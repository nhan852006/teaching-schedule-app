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
        $csvData = $this->readCsvRows($file->getRealPath());

        if (empty($csvData['rows'])) {
            return back()->with('error', 'File CSV không có dữ liệu hoặc không đọc được định dạng hàng.');
        }

        $rowCount = 0;
        $processedSubjects = [];

        DB::beginTransaction();
        try {
            foreach ($csvData['rows'] as $data) {
                $subjectCode   = $this->getFlexibleValue($data, ['Mã Môn', 'Mã môn', 'Ma Mon', 'code', 'subject_code', 'Mã HP', 'Mã học phần']);
                $subjectName   = $this->getFlexibleValue($data, ['Tên Môn', 'Tên môn', 'Ten Mon', 'name', 'subject_name', 'Tên HP', 'Tên môn học']);
                $sessionNumber = (int)$this->getFlexibleValue($data, ['Buổi số', 'Buổi', 'Buoi so', 'session_number', 'session', 'STT', 'Tiết']);
                $content       = $this->getFlexibleValue($data, ['Nội dung giảng dạy', 'Nội dung', 'Noi dung', 'content', 'Tên bài giảng', 'Bài học']);
                $theoryTime    = (int)$this->getFlexibleValue($data, ['Số tiết LT', 'Số tiết lý thuyết', 'LT', 'Ly thuyet', 'theory_time']);
                $practiceTime  = (int)$this->getFlexibleValue($data, ['Số tiết TH', 'Số tiết thực hành', 'TH', 'Thuc hanh', 'practice_time']);

                // Nếu không có mã môn nhưng có tên môn, tự sinh mã môn
                if (!$subjectCode && $subjectName) {
                    $subjectCode = strtoupper(\Illuminate\Support\Str::slug($subjectName, ''));
                }

                if (!$subjectCode || $sessionNumber <= 0) {
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

                $processedSubjects[$subject->id] = $subject;
                $rowCount++;
            }

            // Cập nhật tổng số buổi cho từng môn học đã import
            foreach ($processedSubjects as $subId => $subObj) {
                $maxSession = SubjectContent::where('subject_id', $subId)->max('session_number');
                $subObj->update(['total_sessions' => $maxSession]);
            }

            DB::commit();

            $subjectCount = count($processedSubjects);
            $subjectList = collect($processedSubjects)->map(fn($s) => "{$s->name} ({$s->code})")->implode(', ');

            return back()->with('success', "Đã import thành công {$rowCount} buổi học cho {$subjectCount} môn [{$subjectList}] của {$currentTeacher->name} (phân cách: '{$csvData['delimiter']}').");
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Lỗi khi import môn học CSV: " . $e->getMessage());
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
        $csvData = $this->readCsvRows($file->getRealPath());

        if (empty($csvData['rows'])) {
            return back()->with('error', 'File CSV không có dữ liệu hoặc không đọc được định dạng hàng.');
        }

        DB::beginTransaction();
        try {
            $grouped = [];

            foreach ($csvData['rows'] as $item) {
                $className    = $this->getFlexibleValue($item, ['Tên Lớp', 'Tên lớp', 'Lớp', 'Ten Lop', 'class', 'class_name']);
                $subjectCode  = $this->getFlexibleValue($item, ['Mã Môn', 'Mã môn', 'Ma Mon', 'code', 'subject_code', 'Mã HP', 'Mã học phần']);
                $subjectName  = $this->getFlexibleValue($item, ['Tên Môn', 'Tên môn', 'Môn', 'Môn học', 'Ten Mon', 'subject', 'subject_name']);
                $rawDate      = $this->getFlexibleValue($item, ['Ngày', 'Ngày học', 'Ngày (YYYY-MM-DD)', 'date', 'teaching_date']);
                $rawShift     = $this->getFlexibleValue($item, ['Buổi', 'Ca', 'Ca học', 'Buổi học', 'Buổi (Sáng/Chiều)', 'shift', 'session_shift'], '0');

                $cleanShift = trim((string)$rawShift);
                if ($cleanShift === '1' || stripos($cleanShift, 'chiều') !== false || stripos($cleanShift, 'chieu') !== false || strtoupper($cleanShift) === 'C') {
                    $shift = 'Chiều';
                } else {
                    $shift = 'Sáng'; // 0, Sáng, sang, S, v.v.
                }

                $teachingDate = $this->parseFlexibleDate($rawDate);

                $targetSubject = $subjectCode ?: $subjectName;

                if (!$className || !$targetSubject || !$teachingDate) {
                    continue;
                }

                $class = Classes::firstOrCreate(['name' => $className]);

                // Tìm môn học ưu tiên theo Mã Môn, sau đó theo Tên Môn
                $subject = null;
                if ($subjectCode) {
                    $subject = Subject::where('code', $subjectCode)->first();
                }
                if (!$subject && $subjectName) {
                    $subject = Subject::where('name', $subjectName)->first();
                }
                if (!$subject) {
                    $subject = Subject::where('code', $targetSubject)->orWhere('name', $targetSubject)->first();
                }

                if (!$subject) {
                    $subject = Subject::create([
                        'user_id'        => $currentTeacher->id,
                        'code'           => $subjectCode ?: strtoupper(\Illuminate\Support\Str::slug($targetSubject, '')),
                        'name'           => $subjectName ?: $targetSubject,
                        'total_sessions' => 0
                    ]);
                }

                $key = $class->id . '_' . $subject->id;
                $grouped[$key]['class'] = $class;
                $grouped[$key]['subject'] = $subject;
                $grouped[$key]['rows'][] = [
                    'date'  => $teachingDate,
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

            return back()->with('success', "Đã import và phân bổ {$totalImported} buổi dạy thành công cho {$currentTeacher->name} (định dạng dấu: '{$csvData['delimiter']}').");
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Lỗi khi import lịch giảng CSV: " . $e->getMessage());
            return back()->with('error', 'Lỗi khi import lịch giảng: ' . $e->getMessage());
        }
    }

    /**
     * Tự động phát hiện dấu phân cách CSV (; , \t |)
     */
    protected function detectCsvDelimiter(string $filePath): string
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ',';
        }

        // Bỏ qua BOM nếu có
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $firstLine = fgets($handle);
        fclose($handle);

        if (!$firstLine) {
            return ',';
        }

        $delimiters = [';' => 0, ',' => 0, "\t" => 0, '|' => 0];
        foreach (array_keys($delimiters) as $delim) {
            $delimiters[$delim] = substr_count($firstLine, $delim);
        }

        arsort($delimiters);
        $bestDelimiter = key($delimiters);

        return $delimiters[$bestDelimiter] > 0 ? $bestDelimiter : ',';
    }

    /**
     * Đọc và chuẩn hóa dữ liệu từ file CSV (hỗ trợ BOM UTF-8, dấu ; hoặc ,, multiline text)
     */
    protected function readCsvRows(string $filePath): array
    {
        $delimiter = $this->detectCsvDelimiter($filePath);

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ['header' => [], 'rows' => [], 'delimiter' => $delimiter];
        }

        // Bỏ qua BOM UTF-8 nếu có
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Đọc dòng tiêu đề (header)
        $rawHeader = fgetcsv($handle, 2000, $delimiter);
        if (!$rawHeader) {
            fclose($handle);
            return ['header' => [], 'rows' => [], 'delimiter' => $delimiter];
        }

        // Chuẩn hóa header: xóa BOM sót lại, trim khoảng trắng cả 2 đầu
        $header = [];
        foreach ($rawHeader as $col) {
            $cleaned = preg_replace('/^\xEF\xBB\xBF/', '', (string)$col);
            $cleaned = trim($cleaned, " \t\n\r\0\x0B\xc2\xa0");
            $header[] = $cleaned;
        }

        $rows = [];
        $headerCount = count($header);

        while (($row = fgetcsv($handle, 4000, $delimiter)) !== false) {
            // Bỏ qua hàng trống
            if (empty(array_filter($row, fn($val) => trim((string)$val) !== ''))) {
                continue;
            }

            // Đảm bảo số lượng cột khớp chính xác với header, triệt tiêu lỗi array_combine
            $rowCount = count($row);
            if ($rowCount < $headerCount) {
                $row = array_pad($row, $headerCount, '');
            } elseif ($rowCount > $headerCount) {
                $row = array_slice($row, 0, $headerCount);
            }

            $trimmedRow = array_map(function($val) {
                return trim((string)$val, " \t\n\r\0\x0B\xc2\xa0");
            }, $row);

            $rows[] = array_combine($header, $trimmedRow);
        }

        fclose($handle);

        return [
            'delimiter' => $delimiter,
            'header'    => $header,
            'rows'      => $rows,
        ];
    }

    /**
     * Lấy giá trị linh hoạt từ một hàng dựa trên danh sách các tên cột tương đương
     */
    protected function getFlexibleValue(array $row, array $candidateKeys, $default = '')
    {
        // 1. So khớp chính xác
        foreach ($candidateKeys as $key) {
            if (isset($row[$key]) && trim((string)$row[$key]) !== '') {
                return trim((string)$row[$key]);
            }
        }

        // 2. So khớp không phân biệt hoa thường và khoảng trắng
        foreach ($row as $rowKey => $rowVal) {
            $normalizedRowKey = mb_strtolower(trim((string)$rowKey));
            foreach ($candidateKeys as $key) {
                if ($normalizedRowKey === mb_strtolower(trim((string)$key))) {
                    return trim((string)$rowVal);
                }
            }
        }

        return $default;
    }

    /**
     * Chuẩn hóa định dạng ngày từ d/m/yy, dd/mm/yyyy, dd-mm-yyyy hoặc yyyy-mm-dd sang chuẩn yyyy-mm-dd
     * Ưu tiên tuyệt đối định dạng Ngày/Tháng/Năm (Việt Nam) tránh lỗi hiểu nhầm sang Tháng/Ngày/Năm (Mỹ)
     */
    protected function parseFlexibleDate(string $dateStr): ?string
    {
        $dateStr = trim($dateStr);
        if ($dateStr === '') {
            return null;
        }

        // 1. Định dạng ISO: YYYY-MM-DD hoặc YYYY/MM/DD
        if (preg_match('/^(\d{4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})$/', $dateStr, $matches)) {
            $year = (int)$matches[1];
            $month = (int)$matches[2];
            $day = (int)$matches[3];
            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        // 2. Định dạng Việt Nam: Ngày/Tháng/Năm (d/m/yy, dd/mm/yy, d/m/yyyy, dd/mm/yyyy)
        // Ví dụ: 18/8/26, 4/9/26, 04/09/2026, 2/10/26, 3/11/26
        if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{2,4})$/', $dateStr, $matches)) {
            $day = (int)$matches[1];
            $month = (int)$matches[2];
            $rawYear = (int)$matches[3];

            // Xử lý năm 2 chữ số (ví dụ: 26 -> 2026)
            $year = ($rawYear < 100) ? (2000 + $rawYear) : $rawYear;

            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        // 3. Fallback an toàn: thay dấu / bằng - để buộc strtotime hiểu là dd-mm-yyyy (European) thay vì mm/dd/yyyy (US)
        $hyphenated = str_replace('/', '-', $dateStr);
        $ts = strtotime($hyphenated);
        if ($ts !== false) {
            return date('Y-m-d', $ts);
        }

        return null;
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
     * Báo nghỉ đột xuất & Tự động đôn lịch (Cascade Shift Schedule)
     */
    public function postponeAndShift(Request $request): JsonResponse
    {
        $request->validate([
            'schedule_id'       => 'required|exists:schedules,id',
            'replacement_date'  => 'required|date_format:Y-m-d',
            'replacement_shift' => 'required|in:Sáng,Chiều',
            'reason'            => 'nullable|string|max:255',
        ]);

        try {
            $offSchedule = Schedule::with(['class', 'subject'])->findOrFail($request->schedule_id);
            $classId = $offSchedule->class_id;
            $subjectId = $offSchedule->subject_id;
            $userId = $offSchedule->user_id;

            $oldDate  = $offSchedule->teaching_date->format('Y-m-d');
            $oldShift = $offSchedule->session_shift;
            $newDate  = $request->replacement_date;
            $newShift = $request->replacement_shift;

            // 1. Kiểm tra ngày bù không được là Chủ Nhật
            $replacementCarbon = Carbon::parse($newDate);
            if ($replacementCarbon->isSunday()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quy định: Không được chọn ngày thay thế vào Chủ Nhật!',
                ], 422);
            }

            // 2. Kiểm tra xung đột cho ngày bù (Collision Checking)
            // 2a. Giảng viên đã dạy lớp khác ca đó ngày đó chưa?
            if ($userId) {
                $teacherConflict = Schedule::where('user_id', $userId)
                    ->where('teaching_date', $newDate)
                    ->where('session_shift', $newShift)
                    ->where('id', '!=', $offSchedule->id)
                    ->with(['class', 'subject'])
                    ->first();

                if ($teacherConflict) {
                    return response()->json([
                        'success' => false,
                        'message' => "Trùng lịch Giảng viên vào ngày bù! Thầy/Cô đã có lịch dạy lớp [{$teacherConflict->class?->name}] môn [{$teacherConflict->subject?->name}] vào ca {$newShift} ngày {$newDate}.",
                    ], 422);
                }
            }

            // 2b. Lớp học đó đã học ca đó ngày đó chưa?
            $classConflict = Schedule::where('class_id', $classId)
                ->where('teaching_date', $newDate)
                ->where('session_shift', $newShift)
                ->where('id', '!=', $offSchedule->id)
                ->with(['subject'])
                ->first();

            if ($classConflict) {
                return response()->json([
                    'success' => false,
                    'message' => "Trùng lịch Lớp học vào ngày bù! Lớp [{$offSchedule->class?->name}] đã có lịch học môn [{$classConflict->subject?->name}] vào ca {$newShift} ngày {$newDate}.",
                ], 422);
            }

            DB::beginTransaction();

            // Cập nhật ngày và ca cho record bị hoãn
            $offSchedule->teaching_date = $newDate;
            $offSchedule->session_shift = $newShift;
            $offSchedule->save();

            // Lấy tất cả schedules của class_id và subject_id này
            // Sắp xếp lại theo teaching_date ASC, (session_shift = 'Sáng' ? 0 : 1) ASC
            $allSchedules = Schedule::where('class_id', $classId)
                ->where('subject_id', $subjectId)
                ->get()
                ->sort(function ($a, $b) {
                    $cmp = strcmp($a->teaching_date->format('Y-m-d'), $b->teaching_date->format('Y-m-d'));
                    if ($cmp !== 0) return $cmp;
                    $shiftVal = fn($s) => $s === 'Sáng' ? 0 : 1;
                    return $shiftVal($a->session_shift) <=> $shiftVal($b->session_shift);
                })
                ->values();

            // Gán lại session_number = 1, 2, ..., N theo thứ tự thời gian tăng dần
            $changedCount = 0;
            foreach ($allSchedules as $index => $item) {
                $newSessionNumber = $index + 1;
                $isDirty = false;

                if ($item->session_number !== $newSessionNumber) {
                    $item->session_number = $newSessionNumber;
                    $isDirty = true;
                }

                if ($isDirty || $item->isDirty()) {
                    if ($item->sync_status === 'synced') {
                        $item->sync_status = 'modified';
                    }
                    $item->save();
                    $changedCount++;
                }
            }

            DB::commit();

            $dateVnOld = Carbon::parse($oldDate)->format('d/m/Y');
            $dateVnNew = Carbon::parse($newDate)->format('d/m/Y');

            return response()->json([
                'success' => true,
                'message' => "Đã báo nghỉ ca {$oldShift} ngày {$dateVnOld}, dời sang ca {$newShift} ngày {$dateVnNew} và tự động sắp xếp/đôn lại {$allSchedules->count()} buổi học theo đúng tiến trình bài giảng!",
                'class_id' => $classId,
                'subject_id' => $subjectId,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Lỗi khi đôn lịch: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi đôn lịch: ' . $e->getMessage(),
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
