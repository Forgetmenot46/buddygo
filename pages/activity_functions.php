<?php
// ตัวแปรที่จำเป็นจะถูกส่งมาจาก post_detail.php
$post_id = $_GET['post_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;
?>

<script>
// ฟังก์ชันสำหรับยืนยันการเข้าร่วม
function confirmJoin(postId, userId) {
    fetch('join_activity_ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            post_id: postId,
            user_id: userId,
            status: 'confirmed',
            action: 'confirm'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('ยืนยันการเข้าร่วมกิจกรรมเรียบร้อยแล้ว!');
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
    });
}

// เพิ่ม Event Listener เมื่อโหลดหน้าเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    // สำหรับปุ่มเข้าร่วมกิจกรรม
    const joinButton = document.getElementById('joinButton');
    if (joinButton) {
        joinButton.addEventListener('click', function(e) {
            e.preventDefault();
            var modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
            modal.show();
        });
    }

    // สำหรับ radio button ในป็อปอัพ
    const yesOption = document.getElementById('yesOption');
    const confirmBtn = document.getElementById('confirmBtn');
    if (yesOption && confirmBtn) {
        yesOption.addEventListener('change', function() {
            confirmBtn.disabled = !this.checked;
        });
    }

    // สำหรับปุ่มยืนยันในป็อปอัพ
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            if (yesOption.checked) {
                handleJoinActivity();
            }
        });
    }
});

// ฟังก์ชันจัดการการเข้าร่วมกิจกรรม
function handleJoinActivity() {
    fetch('join_activity_ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            post_id: <?php echo $post_id; ?>,
            user_id: <?php echo $user_id; ?>,
            status: 'interested'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("บันทึกการเข้าร่วมกิจกรรมเรียบร้อยแล้ว!");
            location.reload();
        } else {
            alert("เกิดข้อผิดพลาด: " + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert("เกิดข้อผิดพลาดในการเชื่อมต่อ");
    });
}

// เพิ่มฟังก์ชันยกเลิกการเข้าร่วม
function cancelJoin(postId, userId) {
    fetch('join_activity_ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            post_id: postId,
            user_id: userId,
            status: 'interested',
            action: 'cancel'
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert('ยกเลิกการเข้าร่วมกิจกรรมเรียบร้อยแล้ว');
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด: ' + (data.message || 'ไม่สามารถยกเลิกการเข้าร่วมได้'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + error.message);
    });
}
</script> 