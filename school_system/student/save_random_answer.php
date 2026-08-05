<?php
//حفظ إجابة الطالب على سؤال من "امتحان عشوائي" تم عبر واجهة JavaScript أو AJAX.
require_once '../config.php';
//ياخد الملفات المرسلة من الجافا على شكل جيسون عادة عبر فيتش ويفك ترميزها الىphp
$data = json_decode(file_get_contents('php://input'), true);
//هون بتم استخراج القيم من جيسون
$exam_id = $data['exam_id'] ?? 0;
$question_index = $data['question_index'] ?? 0;//استخدم القيمة 0 لضمان ان القيمة بتكون 0 ازا ما انرسلت
$number_id = $data['number_id'] ?? 0;
$selected_number = $data['selected_number'] ?? 0;
$is_correct = $data['is_correct'] ?? 0;

// حفظ الإجابة
//ينفذ امر ادخال انسيرت في الجدول بالداتابيس
//ويقوم بتخزين بيانات الاجابة الي ارسلها الطالب في الداتابيس
$conn->query("
    INSERT INTO random_exam_answers 
    (exam_id, question_index, number_id, selected_number, is_correct)
    VALUES ($exam_id, $question_index, $number_id, $selected_number, $is_correct)
");
//برجع رد بصيغة جيسون الى جافا سكريبت ليخبره انو العملية تمت بنحاح
echo json_encode(['success' => true]);