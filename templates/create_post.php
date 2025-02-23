<div class="container mt-4">
    <h1>สร้างโพสต์ใหม่</h1>
    
    <form action="community.php?action=create" method="POST" class="mt-4">
        <div class="mb-3">
            <label for="title" class="form-label">หัวข้อ</label>
            <input type="text" class="form-control" id="title" name="title" required 
                   placeholder="เช่น หาเพื่อนไปเที่ยวเชียงใหม่">
        </div>
        
        <div class="mb-3">
            <label for="destination" class="form-label">สถานที่</label>
            <input type="text" class="form-control" id="destination" name="destination" required
                   placeholder="เช่น เชียงใหม่, ภูเก็ต">
        </div>
        
        <div class="mb-3">
            <label for="description" class="form-label">รายละเอียด</label>
            <textarea class="form-control" id="description" name="description" rows="4" required
                      placeholder="รายละเอียดเพิ่มเติม เช่น แผนการเดินทาง งบประมาณ"></textarea>
        </div>
        
        <div class="mb-3">
            <label for="max_members" class="form-label">จำนวนคนที่ต้องการ</label>
            <input type="number" class="form-control" id="max_members" name="max_members" min="2" value="2">
        </div>
        
        <div class="mb-3">
            <label for="travel_date" class="form-label">วันที่เดินทาง</label>
            <input type="date" class="form-control" id="travel_date" name="travel_date" required>
        </div>
        
        <button type="submit" class="btn btn-primary">สร้างโพสต์</button>
        <a href="community.php" class="btn btn-secondary">ยกเลิก</a>
    </form>
</div>
