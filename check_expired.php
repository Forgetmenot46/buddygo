<?php
// ตรวจสอบว่ามีการเรียกใช้จาก CLI หรือไม่
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

require_once 'includes/auto_delete_posts.php';

// เรียกใช้ฟังก์ชันลบโพสต์ที่หมดอายุ
if (deleteExpiredPosts()) {
    echo "Successfully processed expired posts\n";
} else {
    echo "Error processing expired posts\n";
} 