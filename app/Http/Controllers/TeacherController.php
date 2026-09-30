<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Subject;
use App\Models\Classes;
use App\Models\Schedule;
use Carbon\Carbon;
use Exception;

class TeacherController extends Controller
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
                'name'           => 'Hoàng Văn Nhân',
                'academic_title' => 'ThS',
                'email'          => 'nhan.hv@university.edu.vn',
                'password'       => Hash::make('password123'),
                'department'     => 'Khoa Công Nghệ Thông Tin',
                'status'         => 'active',
            ]);
            session(['active_teacher_id' => $teacher->id]);
        }

        return $teacher;
    }

    /**
     * Danh sách Giáo viên bộ môn (KPI, Tìm kiếm, Lọc, Quản lý)
     */
    public function index(Request $request)
    {
        $currentTeacher = $this->getCurrentTeacher();

        $search = trim($request->input('search', ''));
        $statusFilter = $request->input('status');
        $titleFilter = $request->input('title');

        $query = User::withCount(['subjects', 'schedules'])->orderBy('name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%");
            });
        }

        if ($statusFilter && in_array($statusFilter, ['active', 'inactive'])) {
            $query->where('status', $statusFilter);
        }

        if ($titleFilter) {
            $query->where('academic_title', $titleFilter);
        }

        $teachers = $query->paginate(15)->withQueryString();

        // Thống kê KPI tổng thể
        $totalTeachers = User::count();
        $activeTeachers = User::where('status', 'active')->count();
        $inactiveTeachers = User::where('status', 'inactive')->count();
        $totalSchedulesCount = Schedule::count();

        // Danh mục môn học và lớp học dùng cho modal phân công nhanh
        $allSubjects = Subject::withCount('contents')->orderBy('name')->get();
        $allClasses = Classes::orderBy('name')->get();

        // Danh sách các học hàm / học vị hiện có để lọc
        $availableTitles = User::whereNotNull('academic_title')
            ->where('academic_title', '!=', '')
            ->distinct()
            ->pluck('academic_title');

        return view('teachers.index', compact(
            'currentTeacher',
            'teachers',
            'totalTeachers',
            'activeTeachers',
            'inactiveTeachers',
            'totalSchedulesCount',
            'allSubjects',
            'allClasses',
            'availableTitles',
            'search',
            'statusFilter',
            'titleFilter'
        ));
    }

    /**
     * Thêm mới một Giáo viên bộ môn
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'academic_title' => 'nullable|string|max:50',
            'email'          => 'required|email|max:255|unique:users,email',
            'phone'          => 'nullable|string|max:20',
            'department'     => 'nullable|string|max:255',
            'status'         => 'required|in:active,inactive',
            'password'       => 'nullable|string|min:6',
            'notes'          => 'nullable|string|max:1000',
        ], [
            'name.required'  => 'Vui lòng nhập họ và tên giáo viên.',
            'email.required' => 'Vui lòng nhập địa chỉ email.',
            'email.email'    => 'Email không đúng định dạng hợp lệ.',
            'email.unique'   => 'Địa chỉ email này đã được sử dụng bởi giáo viên khác.',
        ]);

        DB::beginTransaction();
        try {
            $cleanName = trim($validated['name']);
            $title = trim($validated['academic_title'] ?? '');

            // Nếu người dùng nhập kèm học vị trong tên (ví dụ: "ThS. Hoàng Văn Nhân"), tự tách
            if ($title === '' && preg_match('/^(ThS|TS|PGS|GS|KS|CN)\.?\s+(.+)$/iu', $cleanName, $m)) {
                $title = $m[1];
                $cleanName = $m[2];
            }

            $teacher = User::create([
                'name'           => $cleanName,
                'academic_title' => $title ?: 'ThS',
                'email'          => trim(strtolower($validated['email'])),
                'phone'          => trim($validated['phone'] ?? ''),
                'department'     => trim($validated['department'] ?? 'Khoa Công Nghệ Thông Tin'),
                'status'         => $validated['status'],
                'password'       => Hash::make($validated['password'] ?: 'password123'),
                'notes'          => trim($validated['notes'] ?? ''),
            ]);

            // Nếu chọn chuyển sang làm việc ngay sau khi tạo
            if ($request->boolean('switch_now')) {
                session(['active_teacher_id' => $teacher->id]);
            }

            DB::commit();

            return redirect()->route('teachers.index')
                ->with('success', "Đã thêm mới giáo viên '{$teacher->full_name_with_title}' thành công!");
        } catch (Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Lỗi khi thêm giáo viên: ' . $e->getMessage());
        }
    }

    /**
     * Cập nhật thông tin Giáo viên
     */
    public function update(Request $request, $id)
    {
        $teacher = User::findOrFail($id);

        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'academic_title' => 'nullable|string|max:50',
            'email'          => 'required|email|max:255|unique:users,email,' . $teacher->id,
            'phone'          => 'nullable|string|max:20',
            'department'     => 'nullable|string|max:255',
            'status'         => 'required|in:active,inactive',
            'password'       => 'nullable|string|min:6',
            'notes'          => 'nullable|string|max:1000',
        ], [
            'name.required'  => 'Vui lòng nhập họ và tên giáo viên.',
            'email.required' => 'Vui lòng nhập địa chỉ email.',
            'email.unique'   => 'Địa chỉ email này đã tồn tại trong hệ thống.',
        ]);

        DB::beginTransaction();
        try {
            $data = [
                'name'           => trim($validated['name']),
                'academic_title' => trim($validated['academic_title'] ?? 'ThS'),
                'email'          => trim(strtolower($validated['email'])),
                'phone'          => trim($validated['phone'] ?? ''),
                'department'     => trim($validated['department'] ?? 'Khoa Công Nghệ Thông Tin'),
                'status'         => $validated['status'],
                'notes'          => trim($validated['notes'] ?? ''),
            ];

            if (!empty($validated['password'])) {
                $data['password'] = Hash::make($validated['password']);
            }

            $teacher->update($data);

            DB::commit();

            return redirect()->route('teachers.index')
                ->with('success', "Đã cập nhật hồ sơ giáo viên '{$teacher->full_name_with_title}' thành công!");
        } catch (Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Lỗi khi cập nhật giáo viên: ' . $e->getMessage());
        }
    }

    /**
     * Xóa an toàn Giáo viên bộ môn (Chặn xóa nếu có ràng buộc dữ liệu)
     */
    public function destroy(Request $request, $id)
    {
        $teacher = User::withCount(['schedules', 'subjects'])->findOrFail($id);

        // Quy tắc an toàn: Không cho xóa nếu đang có môn học hoặc lịch dạy
        if ($teacher->schedules_count > 0 || $teacher->subjects_count > 0) {
            $reason = [];
            if ($teacher->subjects_count > 0) {
                $reason[] = "{$teacher->subjects_count} môn học phụ trách";
            }
            if ($teacher->schedules_count > 0) {
                $reason[] = "{$teacher->schedules_count} buổi giảng dạy";
            }
            $reasonStr = implode(' và ', $reason);

            return back()->with('error', "Không thể xóa giáo viên [{$teacher->full_name_with_title}] vì đang liên kết với {$reasonStr}. Vui lòng chuyển trạng thái sang 'Tạm nghỉ / Đã khóa' hoặc bàn giao môn học/lịch dạy trước khi xóa.");
        }

        // Kiểm tra không để hệ thống không còn giáo viên nào
        if (User::count() <= 1) {
            return back()->with('error', 'Hệ thống bắt buộc phải có ít nhất một giáo viên bộ môn.');
        }

        DB::beginTransaction();
        try {
            $name = $teacher->full_name_with_title;
            $deletedId = $teacher->id;

            $teacher->delete();

            // Nếu vừa xóa giáo viên đang active trong session, tự động chuyển về giáo viên đầu tiên
            if (session('active_teacher_id') == $deletedId) {
                $firstTeacher = User::first();
                session(['active_teacher_id' => $firstTeacher?->id]);
            }

            DB::commit();

            return redirect()->route('teachers.index')
                ->with('success', "Đã xóa hoàn toàn hồ sơ giáo viên '{$name}' khỏi hệ thống.");
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi khi xóa giáo viên: ' . $e->getMessage());
        }
    }

    /**
     * Đổi nhanh trạng thái Hoạt động / Tạm nghỉ qua AJAX
     */
    public function toggleStatus(Request $request, $id): JsonResponse
    {
        $teacher = User::findOrFail($id);
        $newStatus = $teacher->status === 'active' ? 'inactive' : 'active';
        $teacher->update(['status' => $newStatus]);

        return response()->json([
            'success'     => true,
            'status'      => $newStatus,
            'label'       => $teacher->status_label,
            'badge_class' => $teacher->status_badge_class,
            'message'     => "Đã chuyển trạng thái giáo viên '{$teacher->name}' sang: {$teacher->status_label}",
        ]);
    }

    /**
     * Chuyển đổi không gian làm việc sang giáo viên này và về Dashboard
     */
    public function switchWorkspace($id)
    {
        $teacher = User::findOrFail($id);
        session(['active_teacher_id' => $teacher->id]);

        return redirect()->route('schedules.index')
            ->with('success', "Đã chuyển sang không gian làm việc của Giảng viên: {$teacher->full_name_with_title}");
    }

    /**
     * Phân công nhanh Môn học & Lập lịch dạy cho Giáo viên
     * (Sử dụng môn học sẵn có của Khoa/Bộ môn hoặc môn mới)
     */
    public function assignPlan(Request $request, $teacherId)
    {
        $teacher = User::findOrFail($teacherId);

        $validated = $request->validate([
            'class_id'      => 'required|exists:classes,id',
            'subject_id'    => 'required|exists:subjects,id',
            'start_date'    => 'required|date_format:Y-m-d',
            'session_shift' => 'required|in:Sáng,Chiều',
            'days_of_week'  => 'required|array|min:1',
            'days_of_week.*'=> 'integer|between:1,6', // 1=Thứ 2, 6=Thứ 7
        ], [
            'class_id.required'   => 'Vui lòng chọn lớp học.',
            'subject_id.required' => 'Vui lòng chọn môn học sẵn có.',
            'start_date.required' => 'Vui lòng chọn ngày bắt đầu.',
            'days_of_week.min'    => 'Vui lòng chọn ít nhất một ngày học trong tuần.',
        ]);

        $class = Classes::findOrFail($validated['class_id']);
        $subject = Subject::with('contents')->findOrFail($validated['subject_id']);
        $totalSessions = $subject->total_sessions ?: $subject->contents->count();

        if ($totalSessions <= 0) {
            return back()->with('error', "Môn học '{$subject->name}' chưa có cấu hình số buổi học.");
        }

        DB::beginTransaction();
        try {
            $startDate = Carbon::parse($validated['start_date']);
            $shift = $validated['session_shift'];
            $selectedDays = array_map('intval', $validated['days_of_week']);

            $currentDate = $startDate->copy();
            $sessionsScheduled = 0;
            $maxSearchDays = 365;
            $daysCount = 0;

            while ($sessionsScheduled < $totalSessions && $daysCount < $maxSearchDays) {
                // Không xếp vào Chủ Nhật (0 trong Carbon)
                if (!$currentDate->isSunday()) {
                    $dayOfWeek = $currentDate->dayOfWeekIso; // 1 (Mon) - 7 (Sun)
                    if (in_array($dayOfWeek, $selectedDays)) {
                        $sessionsScheduled++;

                        // Tạo hoặc cập nhật lịch dạy gắn trực tiếp với giáo viên này
                        Schedule::updateOrCreate(
                            [
                                'class_id'       => $class->id,
                                'subject_id'     => $subject->id,
                                'session_number' => $sessionsScheduled,
                            ],
                            [
                                'user_id'       => $teacher->id,
                                'teaching_date' => $currentDate->format('Y-m-d'),
                                'session_shift' => $shift,
                                'sync_status'   => 'pending',
                            ]
                        );
                    }
                }
                $currentDate->addDay();
                $daysCount++;
            }

            DB::commit();

            // Tự động chuyển không gian làm việc sang giáo viên này để xem kết quả
            session(['active_teacher_id' => $teacher->id]);

            return redirect()->route('schedules.preview', ['class_id' => $class->id, 'subject_id' => $subject->id])
                ->with('success', "Đã lập thành công {$sessionsScheduled} buổi dạy môn '{$subject->name}' cho lớp {$class->name} phân công cho giảng viên {$teacher->full_name_with_title}!");
        } catch (Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Lỗi khi lập kế hoạch giảng dạy: ' . $e->getMessage());
        }
    }
}
