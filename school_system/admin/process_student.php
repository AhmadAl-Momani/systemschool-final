<?php
require_once '../config.php';
checkAuth(['admin']);

/* إنشاء مجلد الصور */
if (!file_exists('../uploads/students')) {
    mkdir('../uploads/students',0777,true);
}

/* ==============================
   ADD / EDIT / ASSIGN CLASS
============================== */

if($_SERVER['REQUEST_METHOD']==='POST'){

$action=$_POST['action']??'';


/* التحقق من الحقول */

if($action=='add' || $action=='edit'){

if(empty($_POST['stuname']) || empty($_POST['gender'])){

$_SESSION['error_message']="الاسم والجنس مطلوبين";
header("Location: students.php");
exit();

}

}


/* رفع الصورة */

$photo_name=$_POST['current_photo']??'';

if(isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error']==UPLOAD_ERR_OK){

$file=$_FILES['profile_pic'];

$allowed=['image/jpeg','image/png','image/gif'];

if(!in_array($file['type'],$allowed)){

$_SESSION['error_message']="نوع الصورة غير مسموح";
header("Location: students.php");
exit();

}

if($file['size']>2097152){

$_SESSION['error_message']="الصورة أكبر من 2MB";
header("Location: students.php");
exit();

}

$ext=pathinfo($file['name'],PATHINFO_EXTENSION);

$photo_name=uniqid().".".$ext;

move_uploaded_file(
$file['tmp_name'],
"../uploads/students/".$photo_name
);

}


/* بيانات الطالب */

$stuname=$_POST['stuname'];
$natnum=$_POST['natnum']??NULL;
$gender=$_POST['gender'];
$class_id=$_POST['class_id']??NULL;
$parent_email=$_POST['parent_email']??NULL;



/* ADD */

if($action=='add'){

$sql="INSERT INTO student
(stuname,natnum,gender,class_id,parent_email,profile_pic)
VALUES (?,?,?,?,?,?)";

$stmt=$conn->prepare($sql);

$stmt->bind_param(
"ssssss",
$stuname,
$natnum,
$gender,
$class_id,
$parent_email,
$photo_name
);

$stmt->execute();

$_SESSION['success_message']="تم إضافة الطالب";

}



/* EDIT */

elseif($action=='edit'){

$id=(int)$_POST['student_id'];

$sql="UPDATE student SET
stuname=?,
natnum=?,
gender=?,
class_id=?,
parent_email=?,
profile_pic=?
WHERE student_id=?";

$stmt=$conn->prepare($sql);

$stmt->bind_param(
"ssssssi",
$stuname,
$natnum,
$gender,
$class_id,
$parent_email,
$photo_name,
$id
);

$stmt->execute();

$_SESSION['success_message']="تم التعديل";

}


/* ASSIGN CLASS */

elseif($action=='assign_class'){

$stmt=$conn->prepare(
"UPDATE student SET class_id=? WHERE student_id=?"
);

$stmt->bind_param(
"ii",
$_POST['class_id'],
$_POST['student_id']
);

$stmt->execute();

$_SESSION['success_message']="تم تعيين الصف";

}


header("Location: students.php");
exit();

}



/* ==============================
   DELETE STUDENT (FIXED)
============================== */

if(isset($_GET['action']) &&
$_GET['action']=="delete"){

$student_id=(int)$_GET['id'];


/* جلب الصورة */

$stmt=$conn->prepare(
"SELECT profile_pic FROM student WHERE student_id=?"
);

$stmt->bind_param("i",$student_id);

$stmt->execute();

$res=$stmt->get_result();

$student=$res->fetch_assoc();

if(!$student){

$_SESSION['error_message']="الطالب غير موجود";

header("Location: students.php");
exit();

}



/* Transaction */

$conn->begin_transaction();

try{


/* random exams */

$stmt=$conn->prepare(
"DELETE FROM random_exams
WHERE student_id=?"
);

$stmt->bind_param("i",$student_id);
$stmt->execute();



/* worksheet views */

$stmt=$conn->prepare(
"DELETE FROM worksheet_views
WHERE student_id=?"
);

$stmt->bind_param("i",$student_id);
$stmt->execute();



/* exam answers */

$stmt=$conn->prepare("
DELETE ea FROM exam_answers ea
JOIN exam_results er
ON ea.result_id=er.result_id
WHERE er.student_id=?
");

$stmt->bind_param("i",$student_id);
$stmt->execute();



/* exam results */

$stmt=$conn->prepare(
"DELETE FROM exam_results
WHERE student_id=?"
);

$stmt->bind_param("i",$student_id);
$stmt->execute();



/* parent student */

$stmt=$conn->prepare(
"DELETE FROM parent_student
WHERE student_id=?"
);

$stmt->bind_param("i",$student_id);
$stmt->execute();



/* delete student */

$stmt=$conn->prepare(
"DELETE FROM student
WHERE student_id=?"
);

$stmt->bind_param("i",$student_id);

$stmt->execute();



$conn->commit();


/* حذف الصورة */

if(!empty($student['profile_pic'])){

@unlink("../uploads/students/".$student['profile_pic']);

}


$_SESSION['success_message']="تم حذف الطالب بنجاح";


}catch(Exception $e){

$conn->rollback();

$_SESSION['error_message']=$e->getMessage();

}


header("Location: students.php");
exit();

}



/* Unknown Request */

$_SESSION['error_message']="طلب غير صحيح";

header("Location: students.php");
exit();

?>
