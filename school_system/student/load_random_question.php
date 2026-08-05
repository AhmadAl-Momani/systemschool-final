<?php
//يعرض سؤال عشوائي على شكل: "ما هو هذا الرقم؟" بحيث يظهر صورة لرقم، ويطلب من الطالب أن يختار الإجابة الصحيحة من بين 4 خيارات أرقام.

require_once '../config.php';
session_start();
//ياخذ القيمة المرسلة عبر بوست مثل مستوى السوال والصف
//إذا لم يتم إرسالها، يستخدم القيم الافتراضية.
$question_index = $_POST['question_index'] ?? 0;
$level = $_POST['level'] ?? 'easy';
$grade_level = $_POST['grade_level'] ?? 'الصف الأول';

// تحديد نطاق الأرقام حسب المستوى
$range = [
  'easy' => ['min' => 1, 'max' => 20],
  'medium' => ['min' => 1, 'max' => 50],
  'hard' => ['min' => 1, 'max' => 100]
][$level];

// جلب رقم عشوائي من الداتا بيس
//يبحث عن رقم داخل جدول النمبرز في حدود المين والماكس
//ياخد قيمة ولاحدة عشوائية باستخدام اوردر باي راند
//الناتج يحتوي على رقم وصورة له 
$number = $conn->query("
    SELECT * FROM numbers 
    WHERE number_value BETWEEN {$range['min']} AND {$range['max']}
    ORDER BY RAND() 
    LIMIT 1
")->fetch_assoc();

if ($number): ?>
  <h3>ما هو هذا الرقم؟</h3>
  <img src="<?= BASE_URL ?>uploads/numbers/<?= $number['image_path'] ?>" class="img-fluid my-4"
    style="max-height: 200px;">

  <div class="row justify-content-center">
    <?php
    // إنشاء خيارات عشوائية
    //إنشاء 4 خيارات (إجابة صحيحة + 3 عشوائية)
    $options = [$number['number_value']];
    while (count($options) < 4) {
      $rand_num = rand($range['min'], $range['max']);
      if (!in_array($rand_num, $options)) {
        $options[] = $rand_num;
      }
    }
    shuffle($options);//عشان يخلط الاجابات

    //لكل خيار، يظهر زر كبير.


    foreach ($options as $option): ?>
      <div class="col-md-3 mb-3">
        <button class="btn btn-outline-primary btn-lg w-100 answer-btn" data-number="<?= $option ?>">
          <?= $option ?>
        </button>
      </div>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="alert alert-danger">لا توجد أسئلة متاحة</div>
<?php endif; ?>