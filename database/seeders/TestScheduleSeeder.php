<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Classes;
use App\Models\Subject;
use App\Models\SubjectContent;
use App\Models\Schedule;
use Carbon\Carbon;

class TestScheduleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Khởi tạo danh sách Giảng viên (Teachers)
        $teachersData = [
            [
                'name' => 'ThS. Hoàng Văn Nhân',
                'email' => 'nhan.hv@university.edu.vn',
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'TS. Nguyễn Văn An',
                'email' => 'an.nv@university.edu.vn',
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'ThS. Trần Thị Mai',
                'email' => 'mai.tt@university.edu.vn',
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'ThS. Lê Văn Bình',
                'email' => 'binh.lv@university.edu.vn',
                'password' => Hash::make('password123'),
            ],
        ];

        $teachers = [];
        foreach ($teachersData as $tData) {
            $teachers[$tData['email']] = User::updateOrCreate(
                ['email' => $tData['email']],
                [
                    'name' => $tData['name'],
                    'password' => $tData['password'],
                ]
            );
        }

        // 2. Khởi tạo 5 Lớp học
        $classNames = [
            'K20-CNTT1',
            'K20-CNTT2',
            'K20-KTPM1',
            'K20-KTPM2',
            'K20-HTTT1'
        ];

        $classes = [];
        foreach ($classNames as $name) {
            $classes[$name] = Classes::firstOrCreate(['name' => $name]);
        }

        // 3. Khởi tạo 5 Môn học và gán Giảng viên phụ trách
        $subjectsData = [
            // 3 Môn 45h (9 buổi, mỗi buổi 5 tiết)
            [
                'code' => 'IT4501',
                'name' => 'Lập trình Web với Laravel',
                'teacher_email' => 'nhan.hv@university.edu.vn',
                'total_hours' => 45,
                'total_sessions' => 9,
                'sessions' => [
                    ['Buổi 1: Tổng quan Kiến trúc MVC, Cài đặt Laravel & Composer', 3, 2],
                    ['Buổi 2: Routing, Controller, Middleware & Request Lifecycle', 2, 3],
                    ['Buổi 3: Blade Template Engine & Thiết kế giao diện Responsive', 2, 3],
                    ['Buổi 4: Migration, Schema Builder & Eloquent ORM Quan hệ', 2, 3],
                    ['Buổi 5: Xử lý Form, Validation & File Upload (CSV/Excel)', 2, 3],
                    ['Buổi 6: AJAX CRUD, RESTful API & JSON Response', 2, 3],
                    ['Buổi 7: Tích hợp thư viện thứ ba: PHPWord, Google Client API', 1, 4],
                    ['Buổi 8: Authentication, Authorization & Phân quyền người dùng', 2, 3],
                    ['Buổi 9: Báo cáo dự án, Kiểm thử phần mềm & Đóng gói triển khai', 1, 4],
                ]
            ],
            [
                'code' => 'IT4502',
                'name' => 'Cơ sở dữ liệu nâng cao',
                'teacher_email' => 'an.nv@university.edu.vn',
                'total_hours' => 45,
                'total_sessions' => 9,
                'sessions' => [
                    ['Buổi 1: Mô hình thực thể ERD nâng cao và Chuẩn hóa dữ liệu', 3, 2],
                    ['Buổi 2: Thiết kế chỉ mục Indexing và Tối ưu hóa truy vấn Query', 2, 3],
                    ['Buổi 3: Stored Procedure, Function và Xử lý ngoại lệ trong MySQL', 2, 3],
                    ['Buổi 4: Trigger và ràng buộc toàn vẹn tự động', 2, 3],
                    ['Buổi 5: Quản lý Transaction và Khóa dữ liệu (ACID)', 3, 2],
                    ['Buổi 6: Phân vùng dữ liệu (Partitioning) và Views nâng cao', 2, 3],
                    ['Buổi 7: Tối ưu hiệu năng Database (Explain, Slow Query Log)', 2, 3],
                    ['Buổi 8: Sao lưu (Backup), Khôi phục (Restore) & Replication', 2, 3],
                    ['Buổi 9: Đánh giá bài tập lớn và Kiểm tra kết thúc học phần', 1, 4],
                ]
            ],
            [
                'code' => 'IT4503',
                'name' => 'Mạng máy tính & An toàn thông tin',
                'teacher_email' => 'mai.tt@university.edu.vn',
                'total_hours' => 45,
                'total_sessions' => 9,
                'sessions' => [
                    ['Buổi 1: Mô hình OSI, TCP/IP và Các giao thức cốt lõi', 3, 2],
                    ['Buổi 2: Cấu hình phân tầng mạng, Subnetting IPv4 và IPv6', 2, 3],
                    ['Buổi 3: Giao thức định tuyến tĩnh và động (RIP, OSPF)', 2, 3],
                    ['Buổi 4: Chuyển mạch VLAN, Trunking và Spanning Tree', 2, 3],
                    ['Buổi 5: Các mối đe dọa an ninh mạng phổ biến và Phòng thủ cơ bản', 3, 2],
                    ['Buổi 6: Mã hóa dữ liệu (Symmetric, Asymmetric) và Chữ ký số', 2, 3],
                    ['Buổi 7: Cấu hình Firewall, Access Control List (ACL) và NAT', 2, 3],
                    ['Buổi 8: Kiểm thử xâm nhập Web căn bản (SQL Injection, XSS, CSRF)', 2, 3],
                    ['Buổi 9: Báo cáo bài tập lớn thực hành bảo mật mạng', 1, 4],
                ]
            ],
            // 2 Môn 75h (15 buổi, mỗi buổi 5 tiết)
            [
                'code' => 'IT7501',
                'name' => 'Lập trình Di động với Flutter',
                'teacher_email' => 'nhan.hv@university.edu.vn',
                'total_hours' => 75,
                'total_sessions' => 15,
                'sessions' => [
                    ['Buổi 1: Cài đặt Flutter SDK, Android Studio & Ngôn ngữ Dart cơ bản', 3, 2],
                    ['Buổi 2: Lập trình hướng đối tượng OOP trong Dart & Collections', 2, 3],
                    ['Buổi 3: Widget cơ bản: Text, Image, Button, Row, Column, Container', 2, 3],
                    ['Buổi 4: Xây dựng Layout đáp ứng (Responsive) & Scrollable Widgets', 2, 3],
                    ['Buổi 5: Quản lý trạng thái State: StatefulWidget & setState', 2, 3],
                    ['Buổi 6: State Management nâng cao: Provider & Riverpod', 2, 3],
                    ['Buổi 7: Điều hướng Navigation 2.0 & Truyền dữ liệu giữa các màn hình', 2, 3],
                    ['Buổi 8: Form Validation, Custom Theme & Dark/Light Mode', 2, 3],
                    ['Buổi 9: Gọi REST API với thư viện Dio/Http và Xử lý JSON', 2, 3],
                    ['Buổi 10: Lưu trữ dữ liệu cục bộ với SharedPreferences & SQLite/Hive', 2, 3],
                    ['Buổi 11: Tích hợp Firebase Authentication & Cloud Firestore', 2, 3],
                    ['Buổi 12: Tích hợp Google Maps & Định vị GPS thiết bị', 1, 4],
                    ['Buổi 13: Xử lý thông báo đẩy Firebase Cloud Messaging (FCM)', 2, 3],
                    ['Buổi 14: Tối ưu hiệu năng, Debugging & Đóng gói file APK/AAB', 2, 3],
                    ['Buổi 15: Bảo vệ đồ án kết thúc môn Lập trình ứng dụng di động', 1, 4],
                ]
            ],
            [
                'code' => 'IT7502',
                'name' => 'Phân tích thiết kế hệ thống phần mềm',
                'teacher_email' => 'an.nv@university.edu.vn',
                'total_hours' => 75,
                'total_sessions' => 15,
                'sessions' => [
                    ['Buổi 1: Giới thiệu quy trình phát triển phần mềm (SDLC, Agile, Scrum)', 3, 2],
                    ['Buổi 2: Kỹ thuật khảo sát, thu thập và đặc tả yêu cầu người dùng', 3, 2],
                    ['Buổi 3: Mô hình hóa Use Case Diagram & Đặc tả kịch bản Use Case', 2, 3],
                    ['Buổi 4: Thiết kế sơ đồ hoạt động (Activity Diagram)', 2, 3],
                    ['Buổi 5: Thiết kế sơ đồ lớp (Class Diagram) & Kiến trúc phần mềm', 2, 3],
                    ['Buổi 6: Sơ đồ tương tác: Sequence Diagram và Communication Diagram', 2, 3],
                    ['Buổi 7: Sơ đồ trạng thái (State Machine Diagram) & Chuyển dịch thực thể', 2, 3],
                    ['Buổi 8: Sơ đồ triển khai hệ thống phần cứng (Deployment Diagram)', 3, 2],
                    ['Buổi 9: Áp dụng các Design Patterns thông dụng (Singleton, Factory, Observer)', 2, 3],
                    ['Buổi 10: Thiết kế giao diện người dùng (UI/UX) và Prototype Figma', 2, 3],
                    ['Buổi 11: Chuyển đổi mô hình phân tích sang mô hình dữ liệu quan hệ (RDBMS)', 2, 3],
                    ['Buổi 12: Thiết kế kiến trúc Microservices và API Gateway', 3, 2],
                    ['Buổi 13: Lập kế hoạch kiểm thử hệ thống phần mềm và Viết Test Case', 2, 3],
                    ['Buổi 14: Viết tài liệu bàn giao phần mềm & Hướng dẫn sử dụng', 2, 3],
                    ['Buổi 15: Báo cáo bảo vệ tài liệu phân tích thiết kế hệ thống hoàn chỉnh', 1, 4],
                ]
            ],
        ];

        $subjects = [];
        foreach ($subjectsData as $sData) {
            $teacher = $teachers[$sData['teacher_email']] ?? null;

            $subject = Subject::updateOrCreate(
                ['code' => $sData['code']],
                [
                    'user_id' => $teacher?->id,
                    'name' => $sData['name'],
                    'total_sessions' => $sData['total_sessions'],
                ]
            );
            $subjects[$sData['code']] = $subject;

            // Thêm các buổi học vào subject_contents
            foreach ($sData['sessions'] as $idx => $sess) {
                SubjectContent::updateOrCreate(
                    [
                        'subject_id' => $subject->id,
                        'session_number' => $idx + 1,
                    ],
                    [
                        'content' => $sess[0],
                        'theory_time' => $sess[1],
                        'practice_time' => $sess[2],
                    ]
                );
            }
        }

        // 4. Phân công mỗi lớp học 3 môn
        $classSubjectMapping = [
            'K20-CNTT1' => ['IT4501', 'IT4502', 'IT7501'], // 45h, 45h, 75h
            'K20-CNTT2' => ['IT4501', 'IT4503', 'IT7501'], // 45h, 45h, 75h
            'K20-KTPM1' => ['IT4502', 'IT7501', 'IT7502'], // 45h, 75h, 75h
            'K20-KTPM2' => ['IT4503', 'IT7501', 'IT7502'], // 45h, 75h, 75h
            'K20-HTTT1' => ['IT4501', 'IT4502', 'IT7502'], // 45h, 45h, 75h
        ];

        // 5. Tạo Lịch giảng dạy (Schedules) gắn với từng Giảng viên
        $startDate = Carbon::create(2026, 10, 5, 0, 0, 0, 'Asia/Ho_Chi_Minh');
        $shifts = ['Sáng', 'Chiều'];

        foreach ($classSubjectMapping as $className => $subjectCodes) {
            $classObj = $classes[$className];

            foreach ($subjectCodes as $sIndex => $sCode) {
                $subjectObj = $subjects[$sCode];
                $totalSessions = $subjectObj->total_sessions;
                $teacherId = $subjectObj->user_id;

                $currentDate = $startDate->copy()->addDays($sIndex * 2);

                for ($sessNum = 1; $sessNum <= $totalSessions; $sessNum++) {
                    // Nếu rơi vào Chủ Nhật, chuyển sang Thứ 2 tiếp theo
                    if ($currentDate->isSunday()) {
                        $currentDate->addDay();
                    }

                    $shift = $shifts[($sessNum + $sIndex) % 2];

                    Schedule::updateOrCreate(
                        [
                            'class_id' => $classObj->id,
                            'subject_id' => $subjectObj->id,
                            'session_number' => $sessNum,
                        ],
                        [
                            'user_id' => $teacherId,
                            'teaching_date' => $currentDate->format('Y-m-d'),
                            'session_shift' => $shift,
                            'sync_status' => 'pending',
                            'google_event_id' => null,
                        ]
                    );

                    $currentDate->addDays(2);
                }
            }
        }
    }
}
