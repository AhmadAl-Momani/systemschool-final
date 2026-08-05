<?php
require_once '../config.php';
checkAuth(['parent']);

$parent_id = $_SESSION['user']['id'];

// جلب معلومات ولي الأمر
$parent = $conn->query("
    SELECT * FROM parent 
    WHERE parent_id = $parent_id
")->fetch_assoc();

// جلب عدد الأولاد
$children_count = $conn->query("
    SELECT COUNT(*) as count FROM parent_student 
    WHERE parent_id = $parent_id
")->fetch_assoc()['count'];

include '../header.php';
?>

<div class="container-fluid">
  <div class="row">
    <div class="col-md-3 p-3 bg-light">
      <?php include 'sidebar.php'; ?>
    </div>

    <div class="col-md-9 p-4">
      <h2><i class="fas fa-user"></i> الملف الشخصي</h2>
      <hr>

      <div class="row">


        <div class="col-md-12">
          <div class="card mb-4">
            <div class="card-header bg-info text-white">
              <h5 class="mb-0">المعلومات الشخصية</h5>
            </div>
            <div class="card-body">
              <form>
                <div class="row mb-3">
                  <div class="col-md-6">
                    <label class="form-label">الاسم الكامل</label>
                    <input type="text" class="form-control" value="<?= $parent['pname'] ?>" readonly>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">البريد الإلكتروني</label>
                    <input type="email" class="form-control" value="<?= $parent['email'] ?>" readonly>
                  </div>
                </div>
                <div class="row mb-3">
                  <div class="col-md-6">
                    <label class="form-label">رقم الهاتف</label>
                    <input type="text" class="form-control" value="<?= $parent['phone_num'] ?>" readonly>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">رقم الهوية</label>
                    <input type="text" class="form-control" value="<?= $parent['natnum'] ?>" readonly>
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label">عدد الأولاد</label>
                  <input type="text" class="form-control" value="<?= $children_count ?>" readonly>
                </div>
                <div class="d-flex justify-content-end">
                  <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                    data-bs-target="#editProfileModal">
                    <i class="fas fa-edit"></i> تعديل المعلومات
                  </button>
                </div>
              </form>
            </div>
          </div>

          <div class="card">
            <div class="card-header bg-warning text-white">
              <h5 class="mb-0">تغيير كلمة المرور</h5>
            </div>
            <div class="card-body">
              <form>
                <div class="mb-3">
                  <label class="form-label">كلمة المرور الحالية</label>
                  <input type="password" class="form-control">
                </div>
                <div class="mb-3">
                  <label class="form-label">كلمة المرور الجديدة</label>
                  <input type="password" class="form-control">
                </div>
                <div class="mb-3">
                  <label class="form-label">تأكيد كلمة المرور الجديدة</label>
                  <input type="password" class="form-control">
                </div>
                <div class="d-flex justify-content-end">
                  <button type="submit" class="btn btn-warning">
                    <i class="fas fa-save"></i> حفظ التغييرات
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal تعديل المعلومات -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">تعديل المعلومات الشخصية</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form>
          <div class="mb-3">
            <label class="form-label">الاسم الكامل</label>
            <input type="text" class="form-control" value="<?= $parent['pname'] ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">البريد الإلكتروني</label>
            <input type="email" class="form-control" value="<?= $parent['email'] ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">رقم الهاتف</label>
            <input type="text" class="form-control" value="<?= $parent['phone_num'] ?>">
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
        <button type="button" class="btn btn-primary">حفظ التغييرات</button>
      </div>
    </div>
  </div>
</div>

<?php include '../footer.php'; ?>