<?php
require_once '../config.php';

// توليد أرقام من 1 إلى 100 مع مسارات الصور
for ($i = 1; $i <= 100; $i++) {
  //توليد اسم ملف الصورة لكل رقم
  $image_path = "number_$i.png"; // أو أي تنسيق آخر لصور الأرقام
//إدخال الرقم والصورة في جدول numbers.
//إذا كان الرقم موجودًا بالفعل يتم فقط تحديث مسار الصورة.
  $conn->query("INSERT INTO numbers (number_value, image_path) 
                 VALUES ($i, '$image_path')
                 ON DUPLICATE KEY UPDATE image_path = '$image_path'");// انو الرقم موجود
}

echo "تم توليد الأرقام بنجاح!";