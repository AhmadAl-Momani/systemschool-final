<?php
require_once '../config.php';
checkAuth(['student']);
//الكود هاد ما بشتغل الا ازا تم ارساله عبر نموذج ادخال زي مثلا ابدا عبر بوست
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $exam_id = (int) $_POST['exam_id'];
  $password = $_POST['password'];
  $student_id = $_SESSION['user']['id'];
//أخذ رقم الامتحان من النموذج.
//اخد الباس
//ووقتو
  $exam = $conn->query("
        SELECT * FROM exams 
        WHERE exam_id = $exam_id
        AND password = '$password'
        AND start_time <= NOW() 
        AND end_time >= NOW()
    ")->fetch_assoc();

  if ($exam) {//ازا الامتحان موجود وما خلص
    $result = $conn->query("
            SELECT * FROM exam_results 
            WHERE exam_id = $exam_id 
            AND student_id = $student_id
        ")->fetch_assoc();//ازا اه معناها حلو منقبل

    if (!$result) {//ازا ما حل الامتحان
      // جلب الأسئلة فقط دون الإجابات الصحيحة
      $questions = $conn->query("
                SELECT q.question_id, q.question_text, q.question_type, q.points
                FROM exam_questions q
                WHERE q.exam_id = $exam_id
                ORDER BY q.question_id
            ");

      $questions_data = [];//كل سؤال بتخزن في اراي هاي حس رقمه
      while ($row = $questions->fetch_assoc()) {
        $questions_data[$row['question_id']] = [
          'question_text' => $row['question_text'],
          'question_type' => $row['question_type'],
          'points' => $row['points']
        ];

        // جلب الخيارات فقط دون تحديد الصحيح منها
        //بنروح على جدول كوسشن اوبشن وبجيب الدوائر تبعت كل سوال
        //بنضيفهم للارااي داخل السوال الخاص فيه
        //ما بنرسل الاجابه الصح للطالب
        if ($row['question_type'] == 'multiple_choice') {
          $options = $conn->query("
                        SELECT option_id, option_text 
                        FROM question_options 
                        WHERE question_id = {$row['question_id']}
                    ");

          while ($option = $options->fetch_assoc()) {
            $questions_data[$row['question_id']]['options'][$option['option_id']] = [
              'text' => $option['option_text']
            ];
          }
        }
      }

      $_SESSION['current_exam'] = [//نخزن بيانات الامتحان في الجلسة:
        'exam_id' => $exam_id,
        'title' => $exam['title'],
        'start_time' => time(),
        'duration' => $exam['duration'] * 60,
        'questions' => $questions_data
      ];

      header("Location: take_exam.php");
      exit();
    } else {// ازا كان عندو تيجة
      $_SESSION['error_message'] = "لقد أكملت هذا الامتحان من قبل";
      header("Location: exams.php");
      exit();
    }
  } else {
    $_SESSION['exam_error'] = "كلمة المرور غير صحيحة أو الامتحان غير متاح حالياً";
    $_SESSION['exam_id'] = $exam_id;
    header("Location: exams.php");
    exit();
  }
} else {
  header("Location: exams.php");
  exit();
}
?>