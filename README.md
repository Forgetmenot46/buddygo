# BuddyGo Project

BuddyGo เป็นแพลตฟอร์มเว็บที่ช่วยให้ผู้ใช้สามารถเชื่อมต่อ แชร์ความสนใจ และภาษาของตน โปรเจกต์นี้เป็นการสร้างระบบโซเชียลที่ง่ายในการใช้งาน

## ฟีเจอร์

- **การสมัครสมาชิก**: ผู้ใช้สามารถสร้างบัญชีและจัดการโปรไฟล์ของตนได้
- **ความสนใจของผู้ใช้**: ผู้ใช้สามารถระบุความสนใจของตนและค้นหาผู้ใช้ที่มีความสนใจเหมือนกัน
- **ภาษา**: ผู้ใช้สามารถเพิ่มภาษาที่สามารถพูดได้ในโปรไฟล์และหาคู่ภาษาที่ต้องการ
- **รูปโปรไฟล์**: อัพโหลดและจัดการรูปโปรไฟล์

## เทคโนโลยีที่ใช้

- **Frontend**: HTML, CSS, JavaScript
- **Backend**: PHP
- **Database**: MySQL/MariaDB

## ขั้นตอนการติดตั้ง

1. **Clone repository:**

    ```bash
    git clone https://github.com/Forgetmenot46/buddygo
    ```

2. **ตั้งค่าบัญชีท้องถิ่น:**

    ตรวจสอบให้แน่ใจว่าคุณติดตั้ง [XAMPP](https://www.apachefriends.org/index.html) หรือ LAMP/WAMP stack อื่นๆ ที่สามารถรัน PHP และ MySQL ได้

3. **สร้างฐานข้อมูล:**

    สร้างฐานข้อมูลใน MySQL/MariaDB ชื่อว่า `buddygodatabase`

4. **ตั้งค่าการเชื่อมต่อฐานข้อมูล:**

    เปิดไฟล์ `includes/db_config.php` และตรวจสอบให้แน่ใจว่าใช้ข้อมูลการเชื่อมต่อที่ถูกต้อง เช่น ชื่อฐานข้อมูล, ผู้ใช้, และรหัสผ่าน

5. **เริ่มต้นเซิร์ฟเวอร์:**

    เปิด XAMPP หรือ WAMP และเริ่ม Apache และ MySQL

6. **เรียกใช้เว็บไซต์:**

    เปิดเบราว์เซอร์และเข้าไปที่ `http://localhost/buddygo/` เพื่อดูเว็บไซต์ของคุณได้

## การใช้งาน

- ผู้ใช้สามารถสมัครสมาชิก, เข้าสู่ระบบ, และกรอกโปรไฟล์
- ผู้ใช้สามารถเลือกความสนใจ และภาษาที่สามารถพูดได้
- ผู้ใช้สามารถอัพโหลดรูปโปรไฟล์และตั้งค่าบัญชีของตนได้
