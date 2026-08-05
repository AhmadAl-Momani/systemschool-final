// دالة لتسجيل وقت التحميل
function recordDownload(worksheetId) {
    fetch(`${BASE_URL}api/record_download.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            worksheet_id: worksheetId,
            student_id: <?= $_SESSION['user_id'] ?? 0 ?>
        })
    });
}

// دالة للعد التنازلي للامتحان
function startExamTimer(durationInMinutes, examId) {
    let timer = durationInMinutes * 60;
    const timerElement = document.getElementById('examTimer');
    
    const interval = setInterval(() => {
        const minutes = Math.floor(timer / 60);
        const seconds = timer % 60;
        
        timerElement.innerHTML = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
        
        if (--timer < 0) {
            clearInterval(interval);
            submitExam(examId);
        }
    }, 1000);
}

// دالة لإرسال الامتحان
function submitExam(examId) {
    const formData = new FormData(document.getElementById('examForm'));
    
    fetch(`${BASE_URL}submit_exam.php`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = `exam_result.php?id=${examId}`;
        } else {
            alert('حدث خطأ أثناء إرسال الامتحان: ' + data.error);
        }
    });
}